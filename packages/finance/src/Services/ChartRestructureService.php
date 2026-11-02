<?php

namespace Nami\Finance\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Support\AccountCodeAliases;
use Nami\Finance\Support\ChartSettings;
use RuntimeException;

/**
 * يطبّق خطة ترتيب دليل (ملف في database/chart_plans) على قاعدة الشركة الحالية داخل معاملة واحدة.
 * الخطة تذكر الحسابات بكودها الأصلي، والأكواد النهائية تُحسب وقت التنفيذ، فتبقى صالحة بعد إضافة حسابات جديدة.
 * أي فحص يفشل يلغي كل شيء. المعاينة تنفّذ الخطة كاملة ثم تلغيها، فتطابق نتيجتها نتيجة التنفيذ.
 *
 * شكل الخطة: digits، type_display، renames [كود => اسم]، moves [كود => مرجع أب]، merges [كود => كود الهدف]،
 * deletes [أكواد]، creates [مفتاح => name,type,status,parent,legacy_code]، retype_children [كود => اسم نوع]، expected_names [كود => اسم].
 * مرجع الأب: 'c:كود' لحساب موجود، أو 'n:مفتاح' لحساب جديد.
 *
 * خطة تحديث على دليل مرتب (keep_codes: true): الحساب الذي لا يتحرك يبقى بكوده، والمنقول يأخذ أول كود حر تحت أبيه
 * بطول مستواه المحفوظ في الإعدادات. مفاتيح إضافية: requires [خطط يجب أن تكون نُفذت]، strict_names (اختلاف الاسم يوقف الخطة)،
 * codes [كود => كود مطلوب]، type_labels [كود => النوع المعروض]، settings [عنوان إعداد => مرجع حساب].
 */
class ChartRestructureService
{
    /** أكواد يبحث عنها البرنامج مباشرة ولا ترد في PROTECTED_CODES. */
    private const EXTRA_ALIASED_CODES = ['2', '22', '126', '121'];

    private array $accounts = [];
    private array $warnings = [];

    public function run(array $plan, string $planName, bool $execute, bool $force = false): array
    {
        $planHash = hash('sha256', json_encode($plan));
        if ($execute && !$force && DB::table('fn_chart_restructure_runs')->where('plan', $planName)->exists()) {
            throw new RuntimeException("الخطة «{$planName}» نُفّذت من قبل على هذه القاعدة.");
        }

        $this->warnings = [];
        $report = ['plan' => $planName, 'plan_hash' => $planHash, 'executed' => false];

        DB::beginTransaction();
        try {
            $report['before'] = $this->snapshot();
            $this->linkDailyCashReference();
            $this->loadAccounts();
            $plan = $this->preparePlan($plan);
            $this->assertPlanMatches($plan);

            $originalCodes = array_column($this->accounts, 'code', 'id');
            $idByOriginalCode = array_flip($originalCodes);
            $tree = $this->buildTargetTree($plan, $idByOriginalCode);
            $this->assignCodes($tree, $plan);
            $this->validateTree($tree, $plan);

            $changes = $this->apply($tree, $planName);
            $changes['settings'] = $this->applySettings($plan, $tree, $idByOriginalCode);
            // في خطة التحديث تبقى الأكواد القديمة المربوطة كما هي: ربطها برقم الحساب لا بكوده
            $aliases = empty($plan['keep_codes']) ? $this->writeAliases($tree, $idByOriginalCode) : [];

            if (empty($plan['keep_codes'])) {
                ChartSettings::put(ChartSettings::LEVEL_DIGITS, json_encode(array_combine(range(1, count($plan['digits'])), $plan['digits'])));
            }
            if (array_key_exists('type_display', $plan)) {
                ChartSettings::put(ChartSettings::TYPE_DISPLAY, $plan['type_display']);
            }
            AccountCodeAliases::reset();

            $report['after'] = $this->snapshot();
            $this->verify($report, $tree);

            $report['changes'] = $changes;
            $report['aliases'] = $aliases;
            $report['warnings'] = $this->warnings;
            $report['rows'] = $this->reportRows($tree);
            $report['summary'] = $this->summary($changes, $report);

            if ($execute) {
                DB::table('fn_chart_restructure_runs')->insert([
                    'plan' => $planName, 'plan_hash' => $planHash, 'summary' => json_encode($report['summary'], JSON_UNESCAPED_UNICODE),
                    'mapping' => json_encode(array_map(fn ($r) => [$r['old_code'], $r['new_code']], $report['rows']), JSON_UNESCAPED_UNICODE),
                    'created_by_id' => auth('admin')->id(), 'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::commit();
                $report['executed'] = true;
            } else {
                DB::rollBack();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            AccountCodeAliases::reset();
            ChartSettings::reset();
            throw $e;
        }
        AccountCodeAliases::reset();
        ChartSettings::reset();

        return $report;
    }

    // ── الإعداد ──────────────────────────────────────────────────────────────

    /** يربط بنود تقرير صندوق اليومية برقم الحساب قبل تغيير الأكواد، إن لم تكن مربوطة. */
    private function linkDailyCashReference(): void
    {
        if (Schema::hasTable('fn_daily_cash_reference_lines') && !DB::table('fn_daily_cash_reference_lines')->exists()) {
            Artisan::call('finance:link-daily-cash-reference');
            $this->warnings[] = 'رُبطت بنود تقرير صندوق اليومية برقم الحساب قبل تغيير الأكواد.';
        }
    }

    private function loadAccounts(): void
    {
        $this->accounts = [];
        foreach (DB::table('fn_accounts')->orderBy('id')->get() as $row) {
            $this->accounts[(int) $row->id] = (array) $row;
        }
    }

    private function preparePlan(array $plan): array
    {
        foreach ($plan['requires'] ?? [] as $required) {
            if (!DB::table('fn_chart_restructure_runs')->where('plan', $required)->exists()) {
                throw new RuntimeException("هذه الخطة تُنفَّذ بعد خطة «{$required}»، ولم تُنفَّذ على هذه القاعدة.");
            }
        }
        if (!empty($plan['keep_codes'])) {
            $plan['digits'] ??= array_values(ChartSettings::levelDigits());
            if (!$plan['digits']) {
                throw new RuntimeException('خطة التحديث تحتاج أطوال المستويات محفوظة في إعدادات الدليل.');
            }
        }

        return $plan;
    }

    private function assertPlanMatches(array $plan): void
    {
        $codes = array_flip(array_map('strval', array_column($this->accounts, 'code')));
        $missing = [];
        foreach (array_merge(array_keys($plan['renames'] ?? []), array_keys($plan['moves'] ?? []), array_keys($plan['merges'] ?? []), $plan['deletes'] ?? [],
            array_keys($plan['codes'] ?? []), array_keys($plan['type_labels'] ?? [])) as $code) {
            if (!isset($codes[(string) $code])) {
                $missing[] = $code;
            }
        }
        if ($missing) {
            throw new RuntimeException('حسابات في الخطة غير موجودة في هذه القاعدة: ' . implode('، ', array_unique($missing)));
        }

        $names = array_column($this->accounts, 'name', 'code');
        foreach ($plan['expected_names'] ?? [] as $code => $name) {
            if (isset($names[$code]) && $this->normalize($names[$code]) !== $this->normalize($name)) {
                $message = "الحساب {$code} اسمه الآن «{$names[$code]}» والخطة كُتبت على «{$name}».";
                if (!empty($plan['strict_names'])) {
                    throw new RuntimeException($message . ' الخطة تتوقف حتى لا تعدّل حساباً غير المقصود.');
                }
                $this->warnings[] = $message;
            }
        }
    }

    // ── الشجرة المستهدفة ─────────────────────────────────────────────────────

    private function buildTargetTree(array $plan, array $idByOriginalCode): array
    {
        $tree = [];
        foreach ($this->accounts as $id => $row) {
            $tree["c:$id"] = [
                'key' => "c:$id", 'id' => $id, 'name' => $row['name'], 'old_name' => $row['name'], 'old_code' => (string) $row['code'], 'code' => null,
                'parent' => $row['parent_id'] ? 'c:' . (int) $row['parent_id'] : null, 'old_parent' => $row['parent_id'] ? 'c:' . (int) $row['parent_id'] : null,
                'type' => (int) $row['type'], 'old_type' => (int) $row['type'], 'status' => $row['status'], 'old_level' => (int) $row['level'], 'removed' => null, 'into' => null, 'new' => false,
                'type_label' => $row['type_label'] ?? null, 'old_type_label' => $row['type_label'] ?? null, 'fixed_code' => null,
            ];
        }
        $ref = function (string $ref) use ($idByOriginalCode) {
            if (str_starts_with($ref, 'n:')) {
                return $ref;
            }
            $id = $idByOriginalCode[substr($ref, 2)] ?? null;
            if (!$id) {
                throw new RuntimeException("مرجع أب غير موجود في القاعدة: {$ref}");
            }

            return "c:$id";
        };

        foreach ($plan['creates'] ?? [] as $key => $new) {
            $type = constant(AccountTypesEnum::class . '::' . ($new['type'] ?? 'NormalAccount'))->value;
            $tree["n:$key"] = [
                'key' => "n:$key", 'id' => null, 'name' => $new['name'], 'old_name' => null, 'old_code' => '', 'code' => null, 'parent' => $ref($new['parent']), 'old_parent' => null,
                'type' => $type, 'old_type' => $type, 'status' => $new['status'], 'old_level' => null, 'removed' => null, 'into' => null, 'new' => true, 'legacy_code' => $new['legacy_code'] ?? null,
                'type_label' => $new['type_label'] ?? null, 'old_type_label' => null, 'fixed_code' => null,
            ];
        }
        foreach ($plan['renames'] ?? [] as $code => $name) {
            $tree['c:' . $idByOriginalCode[(string) $code]]['name'] = $name;
        }
        foreach ($plan['moves'] ?? [] as $code => $parent) {
            $tree['c:' . $idByOriginalCode[(string) $code]]['parent'] = $ref($parent);
        }
        foreach ($plan['merges'] ?? [] as $code => $target) {
            $key = 'c:' . $idByOriginalCode[(string) $code];
            $tree[$key]['removed'] = 'merge';
            $tree[$key]['into'] = $ref('c:' . $target);
        }
        foreach ($plan['deletes'] ?? [] as $code) {
            $tree['c:' . $idByOriginalCode[(string) $code]]['removed'] = 'delete';
        }
        foreach ($plan['codes'] ?? [] as $code => $wanted) {
            $tree['c:' . $idByOriginalCode[(string) $code]]['fixed_code'] = (string) $wanted;
        }
        foreach ($plan['type_labels'] ?? [] as $code => $label) {
            $tree['c:' . $idByOriginalCode[(string) $code]]['type_label'] = $label === '' ? null : $label;
        }

        // فروع حساب يُدمج ولم تذكرها الخطة (أُضيفت بعد كتابتها) تنتقل إلى هدف الدمج.
        foreach ($tree as $key => $node) {
            $parent = $node['parent'] ? $tree[$node['parent']] : null;
            if (!$node['removed'] && $parent && $parent['removed'] === 'merge') {
                $tree[$key]['parent'] = $parent['into'];
                $this->warnings[] = "الحساب {$node['old_code']} «{$node['name']}» غير مذكور في الخطة؛ نُقل مع دمج أبيه.";
            }
        }
        foreach ($plan['retype_children'] ?? [] as $code => $typeName) {
            $parentKey = 'c:' . $idByOriginalCode[(string) $code];
            $type = constant(AccountTypesEnum::class . '::' . $typeName)->value;
            foreach ($tree as $key => $node) {
                if ($node['parent'] === $parentKey && !$node['removed']) {
                    $tree[$key]['type'] = $type;
                }
            }
        }

        return $tree;
    }

    /** يرقّم الشجرة حسب طول المستوى. المستوى الثاني يحتفظ بكوده إن كان صالحاً، وما بعده تسلسل حسب الكود القديم. */
    private function assignCodes(array &$tree, array $plan): void
    {
        $digits = array_combine(range(1, count($plan['digits'])), $plan['digits']);
        $children = [];
        foreach ($tree as $key => $node) {
            if (!$node['removed']) {
                $children[$node['parent'] ?? 'root'][] = $key;
            }
        }
        $sort = function (array $keys) use (&$tree) {
            usort($keys, function ($a, $b) use (&$tree) {
                $ca = $tree[$a]['old_code'] === '' ? PHP_INT_MAX : (int) $tree[$a]['old_code'];
                $cb = $tree[$b]['old_code'] === '' ? PHP_INT_MAX : (int) $tree[$b]['old_code'];

                return $ca <=> $cb ?: strcmp($a, $b);
            });

            return $keys;
        };

        $walk = function (?string $parentKey, int $level) use (&$walk, &$tree, &$children, $digits, $sort, $plan) {
            $keys = $sort($children[$parentKey ?? 'root'] ?? []);
            if (!$keys) {
                return;
            }
            if ($level > count($digits)) {
                throw new RuntimeException("الشجرة تصل إلى المستوى {$level} والخطة لا تحدد طوله.");
            }
            if ($level === 1) {
                foreach ($keys as $key) {
                    if (strlen($tree[$key]['old_code']) !== $digits[1]) {
                        throw new RuntimeException("كود المستوى الأول {$tree[$key]['old_code']} لا يطابق الطول المحدد.");
                    }
                    $tree[$key]['code'] = $tree[$key]['old_code'];
                    $tree[$key]['level'] = 1;
                    $walk($key, 2);
                }

                return;
            }

            $parentCode = $tree[$parentKey]['code'];
            $width = $digits[$level] - $digits[$level - 1];
            $limit = (10 ** $width) - 1;
            $taken = [];
            if (!empty($plan['keep_codes'])) {
                // كود أخلاه حساب نُقل أو أُزيل لا يُعطى لحساب آخر في التنفيذ نفسه، حتى لا يتغير معنى كود قديم
                foreach ($tree as $node) {
                    if ($node['old_parent'] === $parentKey && strlen($node['old_code']) === $digits[$level] && str_starts_with($node['old_code'], $parentCode)) {
                        $taken[(int) substr($node['old_code'], strlen($parentCode))] = true;
                    }
                }
                foreach ($keys as $key) {
                    $node = $tree[$key];
                    $candidate = $node['fixed_code'] ?? (($node['parent'] === $node['old_parent'] && $node['old_code'] !== '') ? $node['old_code'] : null);
                    if ($candidate !== null && strlen($candidate) === $digits[$level] && str_starts_with($candidate, $parentCode)) {
                        $tree[$key]['code'] = $candidate;
                        $taken[(int) substr($candidate, strlen($parentCode))] = true;
                    } elseif ($node['fixed_code'] !== null) {
                        throw new RuntimeException("الكود المطلوب {$node['fixed_code']} لا يصلح تحت {$parentCode} في المستوى {$level}.");
                    }
                }
            } elseif ($level === 2) {
                foreach ($keys as $key) {
                    $old = $tree[$key]['old_code'];
                    if (strlen($old) === $digits[2] && str_starts_with($old, $parentCode)) {
                        $tree[$key]['code'] = $old;
                        $taken[(int) substr($old, strlen($parentCode))] = true;
                    }
                }
            }
            $next = 1;
            foreach ($keys as $key) {
                if ($tree[$key]['code'] !== null) {
                    continue;
                }
                while (isset($taken[$next])) {
                    $next++;
                }
                // في المستوى الثاني تكون خانة الصفر (مثل 50) آخر ما يُستخدم
                $suffix = $next;
                if ($next > $limit) {
                    if ($level === 2 && !isset($taken[0]) && $next === $limit + 1) {
                        $suffix = 0;
                    } else {
                        throw new RuntimeException("الحساب {$parentCode} له فروع أكثر من سعة المستوى {$level} ({$digits[$level]} خانة).");
                    }
                }
                $tree[$key]['code'] = $parentCode . str_pad((string) $suffix, $width, '0', STR_PAD_LEFT);
                $next++;
            }
            foreach ($keys as $key) {
                $tree[$key]['level'] = $level;
                $walk($key, $level + 1);
            }
        };
        $walk(null, 1);
    }

    private function validateTree(array $tree, array $plan): void
    {
        $lines = DB::table('fn_constraint_details')->select('account_id', DB::raw('COUNT(*) n'))->groupBy('account_id')->pluck('n', 'account_id');
        $errors = [];
        $codes = [];
        foreach ($tree as $key => $node) {
            if ($node['removed']) {
                if (!empty($node['into']) && $tree[$node['into']]['removed']) {
                    $errors[] = "هدف دمج {$node['old_code']} يُزال أيضاً.";
                }
                if ($node['removed'] === 'delete' && (int) ($lines[$node['id']] ?? 0) > 0) {
                    $errors[] = "لا يُحذف {$node['old_code']} «{$node['name']}»: عليه قيود. عدّل الخطة.";
                }
                continue;
            }
            if (!isset($node['level'])) {
                $errors[] = "الحساب {$node['old_code']} «{$node['name']}» منفصل عن الشجرة (أبوه يُزال أو في دورة).";
                continue;
            }
            if (isset($codes[$node['code']])) {
                $errors[] = "الكود {$node['code']} مكرر.";
            }
            $codes[$node['code']] = true;
            if (strlen($node['code']) !== (int) $plan['digits'][$node['level'] - 1]) {
                $errors[] = "طول الكود {$node['code']} لا يطابق المستوى {$node['level']}.";
            }
            if ($node['parent'] && $node['parent'] !== $node['old_parent']) {
                $parent = $tree[$node['parent']];
                $wasParent = $parent['id'] && DB::table('fn_accounts')->where('parent_id', $parent['id'])->exists();
                if ($parent['id'] && !$wasParent && (int) ($lines[$parent['id']] ?? 0) > 0) {
                    $errors[] = "الحساب {$parent['old_code']} عليه قيود ولا يصح أن يصبح أباً لـ {$node['old_code']}.";
                }
            }
        }
        if ($errors) {
            throw new RuntimeException("الخطة لا تصلح لهذه القاعدة:\n" . implode("\n", array_unique($errors)));
        }
    }

    // ── التنفيذ ──────────────────────────────────────────────────────────────

    private function apply(array &$tree, string $planName): array
    {
        $reason = "خطة ترتيب الدليل: {$planName}";
        $now = now();
        $changes = ['created' => 0, 'renamed' => 0, 'moved' => 0, 'recoded' => 0, 'retyped' => 0, 'relabeled' => 0, 'merged' => [], 'deleted' => []];

        // كود مؤقت سالب لكل حساب يتغير كوده حتى لا تتصادم الأكواد أثناء التبديل
        foreach ($tree as $node) {
            if (!$node['new'] && !$node['removed'] && $node['code'] !== $node['old_code']) {
                DB::table('fn_accounts')->where('id', $node['id'])->update(['code' => -1 * $node['id']]);
            }
        }

        $temporary = -1000000000;
        $newKeys = array_keys(array_filter($tree, fn ($n) => $n['new']));
        usort($newKeys, fn ($a, $b) => $tree[$a]['level'] <=> $tree[$b]['level']);
        foreach ($newKeys as $key) {
            $node = $tree[$key];
            $parentId = $tree[$node['parent']]['id'];
            $parent = DB::table('fn_accounts')->find($parentId);
            $tree[$key]['id'] = DB::table('fn_accounts')->insertGetId([
                'name' => $node['name'], 'operation_type' => $parent->operation_type ?? 1, 'status' => $node['status'], 'statement_type' => $parent->statement_type ?? null,
                'level' => $node['level'], 'code' => $temporary--, 'type' => $node['type'], 'parent_id' => $parentId, 'created_by_id' => 1, 'created_at' => $now, 'updated_at' => $now,
            ] + ($node['type_label'] !== null ? ['type_label' => $node['type_label']] : []));
            $changes['created']++;
        }

        foreach ($tree as $node) {
            if ($node['new'] || $node['removed']) {
                continue;
            }
            $update = [];
            $parentId = $node['parent'] ? $tree[$node['parent']]['id'] : null;
            if ($node['parent'] !== $node['old_parent']) {
                $update['parent_id'] = $parentId;
                $changes['moved']++;
            }
            if ($node['name'] !== $node['old_name']) {
                $update['name'] = $node['name'];
                $changes['renamed']++;
            }
            if ($node['type'] !== $node['old_type']) {
                $update['type'] = $node['type'];
                $changes['retyped']++;
            }
            if ($node['level'] !== $node['old_level']) {
                $update['level'] = $node['level'];
            }
            if ($node['type_label'] !== $node['old_type_label']) {
                $update['type_label'] = $node['type_label'];
                $changes['relabeled']++;
            }
            if ($update) {
                DB::table('fn_accounts')->where('id', $node['id'])->update($update + ['updated_at' => $now]);
            }
            if ($node['parent'] !== $node['old_parent'] || $node['code'] !== $node['old_code']) {
                DB::table('fn_account_restructure_logs')->insert([
                    'action' => 'move', 'account_id' => $node['id'], 'account_code' => $node['old_code'], 'account_name' => $node['old_name'],
                    'old_parent_id' => $node['old_parent'] ? $tree[$node['old_parent']]['id'] : null, 'new_parent_id' => $parentId, 'reason' => $reason,
                    'snapshot' => json_encode($this->accounts[$node['id']], JSON_UNESCAPED_UNICODE),
                    'details' => json_encode(['old_codes' => [$node['id'] => $node['old_code']], 'new_codes' => [$node['id'] => $node['code']]]),
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        // الدمج أولاً ثم الحذف: قد يصبح حساب فارغاً بعد نقل فروعه
        $removed = array_filter($tree, fn ($n) => $n['removed']);
        uasort($removed, fn ($a, $b) => $b['old_level'] <=> $a['old_level']); // الفروع قبل آبائها
        foreach ($removed as $node) {
            if ($node['removed'] === 'merge') {
                $changes['merged'][] = $this->mergeAccount($node, $tree[$node['into']], $reason, $now);
            }
        }
        foreach ($removed as $node) {
            if ($node['removed'] === 'delete') {
                $this->deleteAccount($node, $reason, $now);
                $changes['deleted'][] = ['code' => $node['old_code'], 'name' => $node['old_name']];
            }
        }

        foreach ($tree as $node) {
            if ($node['removed']) {
                continue;
            }
            if ($node['new'] || $node['code'] !== $node['old_code']) {
                DB::table('fn_accounts')->where('id', $node['id'])->update(['code' => (int) $node['code']]);
                $changes['recoded'] += $node['new'] ? 0 : 1;
            }
        }

        return $changes;
    }

    /** يضبط إعدادات الحسابات (fn_confirm_settings) على حسابات الخطة: [عنوان => 'c:كود' أو 'n:مفتاح']. */
    private function applySettings(array $plan, array $tree, array $idByOriginalCode): array
    {
        $applied = [];
        foreach ($plan['settings'] ?? [] as $title => $ref) {
            $key = str_starts_with($ref, 'n:') ? $ref : 'c:' . ($idByOriginalCode[substr($ref, 2)] ?? '');
            $node = $tree[$key] ?? null;
            if (!$node || $node['removed']) {
                throw new RuntimeException("الإعداد {$title} يشير إلى حساب غير موجود: {$ref}");
            }
            $old = DB::table('fn_confirm_settings')->where('title', $title)->value('value');
            DB::table('fn_confirm_settings')->updateOrInsert(['title' => $title], ['value' => (string) $node['id'], 'created_by_id' => 1, 'updated_at' => now()]);
            $applied[$title] = ['from' => $old, 'to' => $node['id'], 'account' => $node['code'] . ' ' . $node['name']];
        }

        return $applied;
    }

    private function references(): array
    {
        $references = [];
        foreach (AccountRestructureService::REFERENCES as $table => $columns) {
            foreach ($columns as $column => $label) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                    $references[] = [$table, $column, $label];
                }
            }
        }

        return $references;
    }

    private function mergeAccount(array $node, array $target, string $reason, $now): array
    {
        if (DB::table('fn_accounts')->where('parent_id', $node['id'])->exists()) {
            throw new RuntimeException("الحساب {$node['old_code']} ما زال له فروع عند دمجه.");
        }
        $moved = [];
        $movedIds = [];
        foreach ($this->references() as [$table, $column, $label]) {
            $query = DB::table($table)->where($column, $node['id']);
            $ids = Schema::hasColumn($table, 'id') ? (clone $query)->pluck('id')->all() : [];
            $count = $query->update([$column => $target['id']]);
            if ($count) {
                $moved[$label] = ($moved[$label] ?? 0) + $count;
                $movedIds["$table.$column"] = $ids;
            }
        }
        $settings = DB::table('fn_confirm_settings')->where('value', (string) $node['id'])->where('title', 'like', '%account%')->pluck('title')->all();
        if ($settings) {
            DB::table('fn_confirm_settings')->whereIn('title', $settings)->where('value', (string) $node['id'])->update(['value' => (string) $target['id']]);
        }
        DB::table('fn_account_branch')->where('account_id', $node['id'])->delete();
        DB::table('fn_accounts')->where('id', $node['id'])->delete();
        DB::table('fn_account_restructure_logs')->insert([
            'action' => 'merge', 'account_id' => $node['id'], 'account_code' => $node['old_code'], 'account_name' => $node['old_name'], 'target_account_id' => $target['id'], 'reason' => $reason,
            'snapshot' => json_encode($this->accounts[$node['id']], JSON_UNESCAPED_UNICODE),
            'details' => json_encode(['target' => ['id' => $target['id'], 'code' => $target['code'], 'name' => $target['name']], 'moved' => $moved, 'moved_ids' => $movedIds, 'settings' => $settings, 'branch_ids' => []], JSON_UNESCAPED_UNICODE),
            'created_at' => $now, 'updated_at' => $now,
        ]);

        return ['code' => $node['old_code'], 'name' => $node['old_name'], 'into_code' => $target['code'], 'into_name' => $target['name'], 'moved' => $moved];
    }

    private function deleteAccount(array $node, string $reason, $now): void
    {
        if (DB::table('fn_accounts')->where('parent_id', $node['id'])->exists()) {
            throw new RuntimeException("لا يُحذف {$node['old_code']}: له فروع.");
        }
        foreach ($this->references() as [$table, $column, $label]) {
            if (DB::table($table)->where($column, $node['id'])->exists()) {
                throw new RuntimeException("لا يُحذف {$node['old_code']}: مرتبط بـ {$label}.");
            }
        }
        DB::table('fn_account_branch')->where('account_id', $node['id'])->delete();
        DB::table('fn_accounts')->where('id', $node['id'])->delete();
        DB::table('fn_account_restructure_logs')->insert([
            'action' => 'delete', 'account_id' => $node['id'], 'account_code' => $node['old_code'], 'account_name' => $node['old_name'], 'reason' => $reason,
            'snapshot' => json_encode($this->accounts[$node['id']], JSON_UNESCAPED_UNICODE), 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    /** الكود القديم الذي يبحث عنه البرنامج ← الحساب بعد الترتيب، وإن دُمج فهدف الدمج. */
    private function writeAliases(array $tree, array $idByOriginalCode): array
    {
        $now = now();
        $aliases = [];
        $codes = array_unique(array_merge(array_map('strval', AccountRestructureService::PROTECTED_CODES),
            array_map('strval', array_column((array) config('branch_finance.anchors', []), 'code')), self::EXTRA_ALIASED_CODES));

        foreach ($codes as $code) {
            $id = $idByOriginalCode[$code] ?? null;
            if (!$id) {
                continue;
            }
            $node = $tree["c:$id"];
            if ($node['removed'] === 'delete') {
                $this->warnings[] = "الكود {$code} يبحث عنه البرنامج وحسابه حُذف.";
                continue;
            }
            $final = $node['removed'] === 'merge' ? $tree[$node['into']] : $node;
            $aliases[$code] = (int) $final['id'];
        }
        foreach ($tree as $node) {
            if ($node['new'] && !empty($node['legacy_code'])) {
                $aliases[(string) $node['legacy_code']] = (int) $node['id'];
            }
        }
        foreach ($aliases as $code => $accountId) {
            DB::table('fn_account_code_aliases')->updateOrInsert(['legacy_code' => (string) $code], ['account_id' => $accountId, 'updated_at' => $now, 'created_at' => $now]);
        }

        return $aliases;
    }

    // ── التحقق ───────────────────────────────────────────────────────────────

    private function snapshot(): array
    {
        $totals = (array) DB::selectOne('SELECT COUNT(*) n, COALESCE(SUM(CAST(debtor_value AS DECIMAL(24,2))),0) d, COALESCE(SUM(CAST(creditor_value AS DECIMAL(24,2))),0) c FROM fn_constraint_details');
        $balances = [];
        foreach (DB::select('SELECT account_id, COUNT(*) n, COALESCE(SUM(CAST(debtor_value AS DECIMAL(24,2))),0) d, COALESCE(SUM(CAST(creditor_value AS DECIMAL(24,2))),0) c FROM fn_constraint_details GROUP BY account_id') as $row) {
            $balances[(int) $row->account_id] = ['n' => (int) $row->n, 'd' => (string) $row->d, 'c' => (string) $row->c];
        }

        $dangling = (int) DB::selectOne('SELECT COUNT(*) n FROM fn_constraint_details d LEFT JOIN fn_accounts a ON a.id = d.account_id WHERE a.id IS NULL')->n;

        return ['accounts' => DB::table('fn_accounts')->count(), 'totals' => $totals, 'balances' => $balances, 'roots' => $this->rootBalances(), 'dangling_lines' => $dangling];
    }

    /** ميزان الحسابات الرئيسية بنفس أساس شاشة الدليل: القيود المعتمدة وغير المحذوفة. */
    private function rootBalances(): array
    {
        $parents = DB::table('fn_accounts')->pluck('parent_id', 'id')->all();
        $names = DB::table('fn_accounts')->whereNull('parent_id')->pluck('name', 'id')->all();
        $rows = DB::select("SELECT d.account_id, COALESCE(SUM(CAST(d.debtor_value AS DECIMAL(24,2))),0) - COALESCE(SUM(CAST(d.creditor_value AS DECIMAL(24,2))),0) b
            FROM fn_constraint_details d JOIN fn_constraints c ON c.id = d.constraint_id
            WHERE d.deleted_at IS NULL AND c.deleted_at IS NULL AND c.approval_status = 'approved' GROUP BY d.account_id");
        $roots = array_fill_keys(array_keys($names), '0');
        foreach ($rows as $row) {
            $id = (int) $row->account_id;
            if (!array_key_exists($id, $parents)) {
                continue;
            }
            while ($parents[$id]) {
                $id = (int) $parents[$id];
            }
            $roots[$id] = bcadd($roots[$id], (string) $row->b, 2);
        }
        $result = [];
        foreach ($roots as $id => $balance) {
            $result[$id] = ['name' => $names[$id], 'balance' => $balance];
        }

        return $result;
    }

    private function verify(array $report, array $tree): void
    {
        $before = $report['before'];
        $after = $report['after'];
        foreach (['n', 'd', 'c'] as $field) {
            if ((string) $before['totals'][$field] !== (string) $after['totals'][$field]) {
                throw new RuntimeException('مجموع القيود تغيّر؛ أُلغي التنفيذ.');
            }
        }
        $expected = $before['balances'];
        foreach ($tree as $node) {
            if ($node['removed'] !== 'merge' || !isset($expected[$node['id']])) {
                continue;
            }
            $source = $expected[$node['id']];
            unset($expected[$node['id']]);
            $target = $tree[$node['into']]['id'];
            $base = $expected[$target] ?? ['n' => 0, 'd' => '0', 'c' => '0'];
            $expected[$target] = ['n' => $base['n'] + $source['n'], 'd' => bcadd($base['d'], $source['d'], 2), 'c' => bcadd($base['c'], $source['c'], 2)];
        }
        if (count($expected) !== count($after['balances'])) {
            throw new RuntimeException('عدد الحسابات التي عليها قيود تغيّر؛ أُلغي التنفيذ.');
        }
        foreach ($expected as $id => $balance) {
            $current = $after['balances'][$id] ?? null;
            if (!$current || $current['n'] !== $balance['n'] || bccomp($current['d'], $balance['d'], 2) || bccomp($current['c'], $balance['c'], 2)) {
                throw new RuntimeException("رصيد الحساب رقم {$id} تغيّر؛ أُلغي التنفيذ.");
            }
        }

        $rows = DB::table('fn_accounts')->get(['id', 'code', 'level', 'parent_id'])->keyBy('id');
        foreach ($rows as $row) {
            if ((int) $row->code <= 0) {
                throw new RuntimeException('بقي كود مؤقت؛ أُلغي التنفيذ.');
            }
            if ($row->parent_id && (!$rows->has($row->parent_id) || (int) $rows[$row->parent_id]->level + 1 !== (int) $row->level)) {
                throw new RuntimeException("مستوى الحساب {$row->code} لا يتسق مع أبيه؛ أُلغي التنفيذ.");
            }
        }
        if ($after['dangling_lines'] > $before['dangling_lines']) {
            throw new RuntimeException('ظهرت سطور قيود على حسابات غير موجودة؛ أُلغي التنفيذ.');
        }
    }

    // ── التقرير ──────────────────────────────────────────────────────────────

    private function reportRows(array $tree): array
    {
        $rows = [];
        foreach ($tree as $node) {
            $old = $node['old_parent'] ? $tree[$node['old_parent']] : null;
            $parent = $node['parent'] ? $tree[$node['parent']] : null;
            $rows[] = [
                'old_code' => $node['old_code'], 'new_code' => $node['removed'] ? '' : $node['code'], 'name' => $node['name'], 'old_name' => $node['old_name'],
                'old_parent' => $old['name'] ?? '', 'new_parent' => $node['removed'] ? '' : ($parent['name'] ?? ''),
                'level' => $node['level'] ?? '', 'type' => $this->typeLabel($node['type']),
                'display_type' => $node['removed'] ? '' : ($node['type_label'] ?? (in_array($node['level'] ?? 0, [1, 3], true) ? $node['name'] : ($parent['name'] ?? $node['name']))),
                'action' => $this->rowAction($node),
                'into' => $node['into'] ? $tree[$node['into']]['code'] : '',
            ];
        }
        usort($rows, fn ($a, $b) => [$a['new_code'] === '' ? 1 : 0, (int) $a['new_code'], (int) $a['old_code']] <=> [$b['new_code'] === '' ? 1 : 0, (int) $b['new_code'], (int) $b['old_code']]);

        return $rows;
    }

    private function rowAction(array $node): string
    {
        if ($node['new']) {
            return 'إنشاء';
        }
        if ($node['removed']) {
            return $node['removed'] === 'merge' ? 'دمج' : 'حذف';
        }
        $actions = [];
        if ($node['parent'] !== $node['old_parent']) {
            $actions[] = 'نقل';
        }
        if ($node['name'] !== $node['old_name']) {
            $actions[] = 'إعادة تسمية';
        }
        if ($node['type'] !== $node['old_type'] || $node['type_label'] !== $node['old_type_label']) {
            $actions[] = 'تغيير النوع';
        }
        if ($node['code'] !== $node['old_code']) {
            $actions[] = 'كود جديد';
        }

        return implode(' + ', $actions) ?: 'بلا تغيير';
    }

    private function typeLabel(int $type): string
    {
        $case = AccountTypesEnum::tryFrom($type);

        return $case ? $case->lang() : (string) $type;
    }

    private function summary(array $changes, array $report): array
    {
        return [
            'accounts_before' => $report['before']['accounts'], 'accounts_after' => $report['after']['accounts'],
            'created' => $changes['created'], 'renamed' => $changes['renamed'], 'moved' => $changes['moved'], 'recoded' => $changes['recoded'], 'retyped' => $changes['retyped'], 'relabeled' => $changes['relabeled'], 'settings' => count($changes['settings'] ?? []),
            'merged' => count($changes['merged']), 'deleted' => count($changes['deleted']), 'aliases' => count($report['aliases'] ?? []),
            'lines' => (int) $report['after']['totals']['n'], 'debit' => (string) $report['after']['totals']['d'], 'credit' => (string) $report['after']['totals']['c'],
        ];
    }

    private function normalize(string $name): string
    {
        return trim(preg_replace('/\s+/u', ' ', $name));
    }
}
