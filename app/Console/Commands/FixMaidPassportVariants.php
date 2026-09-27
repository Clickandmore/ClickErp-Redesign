<?php

namespace App\Console\Commands;

use App\Models\ingaz\Maid;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * معالجة السير الذاتية المكررة الناتجة عن تحايل الموظفين على قيد تفرّد رقم الجواز
 * (إضافة 0 أو حرف في بداية/نهاية الرقم لإنشاء سيرة ثانية لنفس العاملة).
 */
class FixMaidPassportVariants extends Command
{
    protected $signature = 'maids:passport-variants
        {--report= : مسار ملف CSV لكتابة التقرير}
        {--apply : تنفيذ الدمج فعلياً (بدونه تشغيل تجريبي)}
        {--include-manual : ادمج أيضاً المجموعات المعلّمة MANUAL (أكثر من سيرة مرتبطة ببيانات)}
        {--fix-orphans : صحّح أرقام الجوازات المشوهة التي لا يوجد لها سيرة توأم}
        {--group= : اقتصر على مجموعة واحدة، بالرقم الصحيح للجواز}';

    protected $description = 'تقرير ودمج السير الذاتية المكررة بجوازات متشابهة (0/حرف زائد)';

    /** الحد الأدنى لطول الجواز حتى يُعتد به كأساس للمجموعة. */
    private const MIN_CANON_LENGTH = 6;

    public function handle(): int
    {
        $maids = $this->loadMaids();
        $groups = $this->buildGroups($maids);

        if ($only = $this->option('group')) {
            $groups = array_values(array_filter($groups, fn ($g) => $g['correct'] === strtoupper($only)));
        }

        $safe = array_values(array_filter($groups, fn ($g) => $g['severity'] === 'SAFE'));
        $manual = array_values(array_filter($groups, fn ($g) => $g['severity'] === 'MANUAL'));

        $this->info('مجموعات قابلة للدمج الآلي (SAFE): ' . count($safe));
        $this->warn('مجموعات تحتاج مراجعة يدوية (MANUAL): ' . count($manual));

        if ($path = $this->option('report')) {
            $this->writeReport($path, $groups);
            $this->info("تم كتابة التقرير: {$path}");
        }

        $this->renderTable($groups);

        if ($this->option('fix-orphans')) {
            $this->fixOrphans($maids, $groups);
        }

        if (!$this->option('apply')) {
            $this->warn('تشغيل تجريبي — لم يتغير أي سجل. أضف --apply للتنفيذ.');
            return self::SUCCESS;
        }

        $targets = $this->option('include-manual') ? $groups : $safe;
        $merged = 0;

        foreach ($targets as $group) {
            DB::transaction(function () use ($group, &$merged) {
                $merged += $this->mergeGroup($group);
            });
        }

        $this->info("عدد السير التي تم دمجها وحذفها نهائياً: {$merged}");

        return self::SUCCESS;
    }

    /** @return array<int,Maid> */
    private function loadMaids(): array
    {
        return Maid::withTrashed()->get()->all();
    }

    /** الصيغة المعيارية: حروف وأرقام فقط بحالة كبيرة. */
    private function canon(?string $passport): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $passport));
    }

    /** الرقم الصحيح المقترح: الصيغة المعيارية بعد إزالة صفر بادئ يسبق حرفاً. */
    private function correctForm(string $canon): string
    {
        return preg_replace('/^0(?=[A-Z])/', '', $canon);
    }

    /**
     * يجمع السير التي يكون canon إحداها محتوى داخل canon الأخرى بفارق حرف أو حرفين
     * (يغطي الزيادة في البداية أو النهاية أو كليهما)، إضافة إلى التطابق التام بعد التنظيف.
     */
    private function buildGroups(array $maids): array
    {
        $canon = [];
        foreach ($maids as $maid) {
            $canon[$maid->id] = $this->canon($maid->passport_num);
        }

        $existing = array_flip($canon);

        // لكل سيرة: أقصر canon موجود فعلاً في الجدول ويحتويها — هو أساس المجموعة.
        // بفارق حرف أو حرفين لا توجد سوى خمس صور ممكنة للنص المحتوى، فنولّدها مباشرة.
        $bases = [];
        foreach ($maids as $maid) {
            $mine = $canon[$maid->id];
            $best = $mine;
            foreach ($this->containedCandidates($mine) as $candidate) {
                if (strlen($candidate) < self::MIN_CANON_LENGTH || !isset($existing[$candidate])) {
                    continue;
                }
                if (strlen($candidate) < strlen($best)) {
                    $best = $candidate;
                }
            }
            $bases[$maid->id] = $best;
        }

        $byBase = [];
        foreach ($maids as $maid) {
            $byBase[$bases[$maid->id]][] = $maid;
        }

        $groups = [];
        foreach ($byBase as $base => $members) {
            if (count($members) < 2) {
                continue;
            }

            $correct = $this->correctForm($base);
            $linked = 0;
            foreach ($members as $member) {
                $member->links = $this->countLinks($member->id, $this->businessReferences());
                $member->own = $this->countLinks($member->id, $this->ownReferences());
                $member->form = $member->passport_num === $correct ? 'clean' : 'corrupted';
                $linked += $member->links > 0 ? 1 : 0;
            }

            // الأولوية للسيرة الأكثر ارتباطاً بالبيانات، ثم الأنظف شكلاً، ثم الأقدم.
            usort($members, function ($a, $b) {
                return [$b->links, $b->form === 'clean' ? 1 : 0, -$b->id]
                    <=> [$a->links, $a->form === 'clean' ? 1 : 0, -$a->id];
            });

            $groups[] = [
                'base' => $base,
                'correct' => $correct,
                'severity' => $linked > 1 ? 'MANUAL' : 'SAFE',
                'target' => $members[0],
                'members' => $members,
            ];
        }

        usort($groups, fn ($a, $b) => [$a['severity'], $a['correct']] <=> [$b['severity'], $b['correct']]);

        return $groups;
    }

    /**
     * الصور الممكنة لنص أقصر بحرف أو حرفين ويكون هذا النص محتوياً له.
     *
     * @return array<int,string>
     */
    private function containedCandidates(string $canon): array
    {
        $len = strlen($canon);
        if ($len < 3) {
            return [];
        }

        return array_unique([
            substr($canon, 1),          // حرف زائد في البداية
            substr($canon, 0, -1),      // حرف زائد في النهاية
            substr($canon, 2),          // حرفان زائدان في البداية
            substr($canon, 0, -2),      // حرفان زائدان في النهاية
            substr($canon, 1, -1),      // حرف زائد في كل طرف
        ]);
    }

    private function countLinks(int $maidId, array $references): int
    {
        $total = 0;
        foreach ($references as [$table, $column]) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }
            $total += DB::table($table)->where($column, $maidId)->count();
        }

        return $total;
    }

    private function mergeGroup(array $group): int
    {
        /** @var Maid|null $target */
        $target = Maid::withTrashed()->lockForUpdate()->find($group['target']->id);
        if (!$target) {
            return 0;
        }

        $deleted = 0;

        foreach ($group['members'] as $member) {
            if ($member->id === $target->id) {
                continue;
            }

            $duplicate = Maid::withTrashed()->lockForUpdate()->find($member->id);
            if (!$duplicate) {
                continue;
            }

            $moved = [];
            $this->mergePivot($duplicate->id, $target->id, 'maid_job_titles', 'job_title_id', $moved);
            $this->mergePivot($duplicate->id, $target->id, 'maid_jobs', 'job_type_id', $moved);
            $this->mergeTranslations($duplicate->id, $target->id, $moved);

            foreach ($this->references() as [$table, $column]) {
                if (in_array($table, ['maid_job_titles', 'maid_jobs', 'maid_translations'], true)) {
                    continue;
                }
                if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                    continue;
                }
                $count = DB::table($table)->where($column, $duplicate->id)->count();
                if ($count > 0) {
                    DB::table($table)->where($column, $duplicate->id)->update([$column => $target->id]);
                    $moved[$table . '.' . $column] = ($moved[$table . '.' . $column] ?? 0) + $count;
                }
            }

            if (Schema::hasTable('activity_log')) {
                $count = DB::table('activity_log')
                    ->where('subject_id', $duplicate->id)
                    ->whereIn('subject_type', [Maid::class, 'App\\Models\\Maid'])
                    ->update(['subject_id' => $target->id]);
                if ($count) {
                    $moved['activity_log'] = $count;
                }
            }

            // انقل الملفات الناقصة من النسخة المكررة قبل حذفها نهائياً.
            foreach (['image', 'cv_file', 'cv_preview', 'video', 'video_link', 'office_id'] as $field) {
                if (!$target->{$field} && $duplicate->{$field}) {
                    $target->{$field} = $duplicate->{$field};
                }
            }

            DB::table('maid_duplicate_merge_audits')->insert([
                'passport_key' => $group['correct'],
                'target_maid_id' => $target->id,
                'deleted_maid_id' => $duplicate->id,
                'arabia_worker_number' => $duplicate->arabia_worker_number,
                'deleted_snapshot' => json_encode($duplicate->getRawOriginal(), JSON_UNESCAPED_UNICODE),
                'moved_relationships' => json_encode($moved, JSON_UNESCAPED_UNICODE),
                'merged_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $duplicate->forceDelete();
            $deleted++;
        }

        // السيرة الباقية تحمل الرقم الصحيح، وتعود للحياة إن كانت محذوفة وهي حاملة البيانات.
        $target->passport_num = $group['correct'];
        if ($target->deleted_at && $group['target']->links > 0) {
            $target->deleted_at = null;
        }
        $target->save();

        return $deleted;
    }

    private function fixOrphans(array $maids, array $groups): void
    {
        $grouped = [];
        foreach ($groups as $group) {
            foreach ($group['members'] as $member) {
                $grouped[$member->id] = true;
            }
        }

        $rows = [];
        foreach ($maids as $maid) {
            if (isset($grouped[$maid->id])) {
                continue;
            }
            $correct = $this->correctForm($this->canon($maid->passport_num));
            if ($correct !== '' && $correct !== $maid->passport_num) {
                $rows[] = [$maid->id, $maid->passport_num, $correct, $maid->name];
            }
        }

        $this->line('');
        $this->info('سير بأرقام مشوهة بلا توأم: ' . count($rows));
        $this->table(['id', 'الجواز الحالي', 'الرقم الصحيح', 'الاسم'], $rows);

        if (!$this->option('apply')) {
            return;
        }

        foreach ($rows as [$id, , $correct]) {
            if (Maid::withTrashed()->where('passport_num', $correct)->where('id', '!=', $id)->exists()) {
                $this->warn("تخطّي #{$id}: الرقم {$correct} مستخدم في سيرة أخرى.");
                continue;
            }
            Maid::withTrashed()->where('id', $id)->update(['passport_num' => $correct]);
        }
    }

    private function renderTable(array $groups): void
    {
        $rows = [];
        foreach ($groups as $group) {
            foreach ($group['members'] as $member) {
                $rows[] = [
                    $group['correct'],
                    $group['severity'],
                    $member->id === $group['target']->id ? 'يُبقى' : 'يُدمج',
                    $member->id,
                    $member->passport_num,
                    $member->status,
                    $member->deleted_at ? 'محذوف' : '',
                    $member->links,
                    $member->own,
                ];
            }
        }

        $this->table(
            ['الرقم الصحيح', 'التصنيف', 'الإجراء', 'id', 'الجواز المسجل', 'الحالة', 'محذوف', 'ارتباطات تشغيلية', 'سجلات تابعة'],
            $rows
        );
    }

    private function writeReport(string $path, array $groups): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $handle = fopen($path, 'w');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['correct_passport', 'severity', 'action', 'maid_id', 'passport_stored',
                          'form', 'name', 'status', 'deleted', 'created_at', 'business_links', 'own_rows']);

        foreach ($groups as $group) {
            foreach ($group['members'] as $member) {
                fputcsv($handle, [
                    $group['correct'],
                    $group['severity'],
                    $member->id === $group['target']->id ? 'keep' : 'merge',
                    $member->id,
                    $member->passport_num,
                    $member->form,
                    $member->name,
                    $member->status,
                    $member->deleted_at ? 'deleted' : '',
                    optional($member->created_at)->format('Y-m-d H:i'),
                    $member->links,
                    $member->own,
                ]);
            }
        }

        fclose($handle);
    }

    private function mergePivot(int $source, int $target, string $table, string $related, array &$moved): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $rows = DB::table($table)->where('maid_id', $source)->get();
        foreach ($rows as $row) {
            if (DB::table($table)->where('maid_id', $target)->where($related, $row->{$related})->exists()) {
                DB::table($table)->where('id', $row->id)->delete();
            } else {
                DB::table($table)->where('id', $row->id)->update(['maid_id' => $target]);
            }
        }

        if ($rows->isNotEmpty()) {
            $moved[$table] = $rows->count();
        }
    }

    private function mergeTranslations(int $source, int $target, array &$moved): void
    {
        $rows = DB::table('maid_translations')->where('maid_id', $source)->get();
        foreach ($rows as $row) {
            if (DB::table('maid_translations')->where('maid_id', $target)->where('locale', $row->locale)->exists()) {
                DB::table('maid_translations')->where('id', $row->id)->delete();
            } else {
                DB::table('maid_translations')->where('id', $row->id)->update(['maid_id' => $target]);
            }
        }

        if ($rows->isNotEmpty()) {
            $moved['maid_translations'] = $rows->count();
        }
    }

    /**
     * ارتباطات تشغيلية: وجودها يعني أن السيرة مستخدمة فعلاً في عمل قائم،
     * ووجودها على أكثر من سيرة داخل المجموعة يجعل الدمج قراراً بشرياً.
     *
     * @return array<int,array{0:string,1:string}>
     */
    private function businessReferences(): array
    {
        return [
            ['contracts', 'maid_id'],
            ['rental_contracts', 'maid_id'],
            ['orders', 'maid_id'],
            ['transfer_service_contracts', 'maid_id'],
            ['deportation_procedures', 'maid_id'],
            ['travel_histories', 'maid_id'],
            ['financial_requests', 'maid_id'],
            ['recruitment_requests', 'maid_id'],
            ['return_requests', 'maid_id'],
            ['istiqdam_contracts', 'assigned_maid_id'],
            ['ingaz_delegations', 'maid_id'],
            ['hr_employee', 'maid_id'],
            ['hr_payroll_items', 'maid_id'],
            ['rental_financial_adjustments', 'maid_id'],
            ['shelter_agent_cost_recoveries', 'maid_id'],
            ['shelter_cost_entries', 'maid_id'],
            ['shelter_living_expense_allocations', 'maid_id'],
            ['sponsorship_transfer_maid_replacements', 'old_maid_id'],
            ['sponsorship_transfer_maid_replacements', 'new_maid_id'],
        ];
    }

    /**
     * سجلات تابعة للسيرة نفسها (ترجمات، مهارات، مرفقات…) تنتقل مع الدمج بلا لبس.
     *
     * @return array<int,array{0:string,1:string}>
     */
    private function ownReferences(): array
    {
        $business = $this->businessReferences();

        return array_values(array_filter(
            $this->references(),
            fn ($ref) => !in_array($ref, $business, true)
        ));
    }

    /** @return array<int,array{0:string,1:string}> */
    private function references(): array
    {
        return [
            ['agent_maid_bulk_import_rows', 'maid_id'],
            ['contracts', 'maid_id'],
            ['deportation_procedures', 'maid_id'],
            ['financial_requests', 'maid_id'],
            ['hr_employee', 'maid_id'],
            ['hr_payroll_items', 'maid_id'],
            ['ingaz_delegations', 'maid_id'],
            ['istiqdam_contracts', 'assigned_maid_id'],
            ['maid_attachments', 'maid_id'],
            ['maid_bulk_import_rows', 'maid_id'],
            ['maid_expertises', 'maid_id'],
            ['maid_external_references', 'maid_id'],
            ['maid_jobs', 'maid_id'],
            ['maid_job_titles', 'maid_id'],
            ['maid_offer', 'maid_id'],
            ['maid_recruitment_procedures', 'maid_id'],
            ['maid_status_change_requests', 'maid_id'],
            ['maid_translations', 'maid_id'],
            ['maid_unavailable_records', 'maid_id'],
            ['orders', 'maid_id'],
            ['recruitment_requests', 'maid_id'],
            ['rental_contracts', 'maid_id'],
            ['rental_financial_adjustments', 'maid_id'],
            ['return_requests', 'maid_id'],
            ['shelter_agent_cost_recoveries', 'maid_id'],
            ['shelter_cost_entries', 'maid_id'],
            ['shelter_living_expense_allocations', 'maid_id'],
            ['sponsorship_transfer_maid_replacements', 'old_maid_id'],
            ['sponsorship_transfer_maid_replacements', 'new_maid_id'],
            ['transfer_service_contracts', 'maid_id'],
            ['travel_histories', 'maid_id'],
        ];
    }
}
