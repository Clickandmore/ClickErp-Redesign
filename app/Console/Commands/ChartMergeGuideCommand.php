<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\AccountTypesEnum;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * يولّد للمحاسب ملف إكسل توجيهياً لترتيب الشجرة الحالية على شجرة البرنامج القياسية
 * (بذرتا AccountSeeder وRecruitmentRentalAccountsSeeder): ما يُعاد تسميته، ما يُنشأ،
 * ما يُنقل، ما يُدمج، وما يحتاج قرار المحاسب. الأمر يقرأ فقط ولا يعدّل أي بيانات.
 */
class ChartMergeGuideCommand extends Command
{
    protected $signature = 'finance:chart-merge-guide
        {--output= : مسار ملف الإكسل الناتج (افتراضياً finance_tree/chart-merge-guide-<تاريخ>.xlsx)}';

    protected $description = 'إعداد ملف إكسل توجيهي للمحاسب لترتيب شجرة الحسابات على شجرة البرنامج (قراءة فقط)';

    /**
     * قرارات صريحة لحسابات الشجرة الحالية التي لا يكفي اسمها للمطابقة.
     * المفتاح: كود الحساب الحالي. القيمة: [كود الهدف في البذرة أو اسم الهدف أو null, الإجراء, ملاحظة].
     * الإجراءات: rename, move, merge:<كود حالي>, delete, keep, decide.
     */
    private const OVERRIDES = [
        12 => ['الأصول المتداولة قصيرة الأجل', 'rename', 'يبقى هذا الحساب ويُدمج فيه 102 (الاسم نفسه)'],
        102 => [null, 'merge:12', 'مكرر لـ 12؛ يُنقل فرعه الوحيد 10201 إلى 12 ثم يُحذف'],
        14 => [null, 'merge:22', 'مكرر لـ 22 (الاسم نفسه)؛ تُنقل فروعه الستة تحت 22 ثم يُحذف'],
        22 => ['الخصوم قصيرة الأجل', 'rename', 'يبقى ويستقبل فروع 14'],
        100 => ['السيارات', 'move', 'أصل ثابت مسجل بالمستوى الثاني بلا قيود'],
        101 => ['السيارات', 'move', 'أصل ثابت مسجل بالمستوى الثاني بلا قيود'],
        116 => [null, 'keep', 'يبقى تحت الأصول الثابتة (لا مقابل في البرنامج)'],
        117 => [null, 'keep', 'يبقى تحت الأصول الثابتة (لا مقابل في البرنامج)'],
        129 => [null, 'delete', 'اسم فارغ «...» بلا قيود وبلا فروع'],
        1210 => [null, 'delete', 'اسم فارغ «...» بلا قيود وبلا فروع'],
        13 => ['الأصول غير الملموسة', 'rename', 'فرق إملائي فقط'],
        111 => ['اجهزة كمبيوتر وطابعات', 'rename', 'فرق إملائي فقط؛ فرعه 1111 يحمل الاسم نفسه ويبقى تحته'],
        113 => ['اثاث ومفروشات', 'rename', 'فرق إملائي فقط'],
        131 => [null, 'keep', 'يبقى تحت الأصول غير الملموسة'],
        5401 => [null, 'keep', 'يبقى بالاسم نفسه: البرنامج يبحث عنه حرفياً لإنشاء مصروفات الجوب أوردر والسفارة لكل جنسية'],
        132 => ['برامج محاسبية ونظم الاستخدام', 'rename', ''],
        141 => ['الوكلاء الخارجيين', 'rename', 'يبقى بنوع «الموردين» لأن البرنامج يبحث عن المكاتب بالنوع؛ يستقبل مكاتب 221'],
        221 => [null, 'merge:141', 'نسخ فارغة بأسماء مكاتب 141 نفسها (روشا مارك، شريف ادم، لينكينج...)؛ تُدمج كل نسخة في المكتب الأصلي'],
        142 => [null, 'keep', 'يبقى تحت الخصوم قصيرة الأجل'],
        144 => [null, 'keep', 'الاسم «المسوقين» يجب أن يبقى حرفياً: شاشة العمولات تبحث عنه بالاسم'],
        145 => ['شركات التأمين', 'rename', ''],
        146 => [null, 'keep', 'يبقى تحت الخصوم قصيرة الأجل'],
        211 => ['مخصص منافع الموظفين', 'rename', ''],
        212 => [null, 'decide', 'في البرنامج «اطراف ذات العلاقة» أصل مدين تحت الأصول المتداولة؛ هنا خصم دائن. المحاسب يقرر الطبيعة'],
        33 => ['رأس المال', 'move', 'يصبح مستوى ثالثاً تحت رأس المال بالاسم نفسه'],
        311 => [311, 'rename', 'الاسم في البرنامج «رأس المال» بالمستوى الثالث'],
        312 => ['الارباح والخسائر', 'rename', ''],
        331 => [null, 'keep', 'يتبع 33 عند نقله'],
        41 => ['إيراد مبيعات الاستقدام', 'rename', ''],
        42 => ['إيراد الخدمات', 'rename', 'يستقبل إيرادات التفاويض والتأجير من 421'],
        421 => [null, 'decide', 'تُوزَّع فروعه: 4214 تأجير خدم ← إيراد خدمات تأجير العمالة (4611)، 4212 تفاويض ← إيراد خدمات العقود الرجالية، 4213 نقل كفالات ← ايرادات نقل خدمات، 4211 ايرادات اخرى ← إيرادات اخرى (45)'],
        422 => ['إيرادات تشغيلية اخرى', 'move', 'إيراد تأمين العمالة مع فروعه الـ17 تحت الإيرادات التشغيلية الأخرى'],
        44 => ['إيراد نقل الخدمات', 'rename', 'الاسم مطابق؛ الكود في البرنامج 43'],
        4111 => [null, 'keep', 'حساب مبيعات عام تاريخي (118 سطراً) يبقى تحت مبيعات الاستقدام'],
        41116 => [null, 'decide', 'تكاليف حكومية مسجلة كإيراد (773 سطراً): المحاسب يقرر بقاءها كإيراد أو نقلها'],
        41119 => ['مرتد مبيعات الاستقدام', 'rename', 'يرتفع إلى المستوى الثالث تحت إيراد مبيعات الاستقدام مع فروعه الست'],
        51 => ['تكاليف مبيعات الاستقدام', 'rename', ''],
        512 => ['مصروفات تشغيلية مباشرة', 'move', 'مصروف تأمين العمالة مع فروعه تحت المصروفات التشغيلية المباشرة'],
        522 => ['مصروفات تشغيلية مباشرة', 'rename', 'يرتفع إلى المستوى الثاني بفروعه الـ32 (عمولات مساند، تفاويض انجاز...)'],
        51121 => ['مردودات تكلفة الاستقدام', 'rename', 'يرتفع إلى المستوى الثالث تحت تكاليف مبيعات الاستقدام'],
        52 => ['اجمالي المصروفات عمومية وادارية', 'rename', 'الاسم القديم «اجمالى مصاريف التشغيل» يُضاف بديلاً في الكود'],
        521 => [null, 'keep', 'يبقى بالاسم نفسه تحت 52 بعد تسميته؛ تحته خصومات مبيعات الجنسيات كما يتوقع البرنامج'],
        523 => ['مصروفات ايواء التشغيل', 'rename', 'ينتقل تحت مصروفات تشغيلية اخرى (58)'],
        54 => ['مصروفات تشغيلية اخرى', 'rename', 'الاسم مطابق؛ الكود في البرنامج 58'],
        1216 => [null, 'merge:126', 'عهد وذمم على الموظفين مكرر لعهد الموظفين؛ تُنقل فروعه الـ16 تحت 126'],
        22201 => [null, 'keep', 'يبقى تحت 222؛ البرنامج ينشئ التزامات كل جنسية تحته'],
    ];

    /** حسابات في البرنامج لا يُطلب إنشاؤها لأن لها مقابلاً بديلاً هنا أو لأنها فروع تلقائية. */
    private const SKIP_CREATE_NAMES = [];

    public function handle(): int
    {
        $target = $this->targetTree();
        $current = $this->currentTree();

        // عند تكرار الاسم على مستويين تُحفظ كل النسخ ويُختار الأقرب مستوى للحساب الحالي
        $targetByNorm = $target->groupBy(fn ($t) => $this->norm($t['name']));
        $this->newNameByCode = [];
        $targetByCode = $target->keyBy('code');
        $currentByCode = $current->keyBy('code');

        $rename = [];
        $move = [];
        $merge = [];
        $delete = [];
        $decide = [];
        $keep = [];
        $matchedTargetCodes = [];

        foreach ($current as $acc) {
            if ($acc['type'] === AccountTypesEnum::Client->value) {
                continue; // حسابات العملاء تتبع أباها ولا تُدرج
            }
            if ($this->isAutoNationalityLeaf($acc['name'])) {
                continue; // فروع الجنسيات يولّدها البرنامج ويتحقق منها بنفسه
            }

            $override = self::OVERRIDES[$acc['code']] ?? null;
            $targetNode = null;

            if ($override) {
                [$to, $action, $note] = $override;
                $targetNode = $to !== null ? $this->findTarget($to, $targetByNorm, $targetByCode, $action === 'move' ? $acc['level'] - 1 : $acc['level']) : null;
                if ($to !== null && !$targetNode) {
                    $this->warn("الهدف «{$to}» للحساب {$acc['code']} غير موجود في البذرة؛ أُحيل إلى قرار المحاسب.");
                    $action = 'decide';
                    $note = trim("الهدف المقترح «{$to}» غير موجود في شجرة البرنامج. " . $note);
                }
            } else {
                $targetNode = $this->closest($targetByNorm->get($this->norm($acc['name'])), $acc['level']);
                $parentAcc = $acc['parent_code'] !== null ? $currentByCode->get($acc['parent_code']) : null;
                $parentNewName = $parentAcc ? ($this->newNameByCode[$parentAcc['code']] ?? $parentAcc['name']) : '';
                if ($targetNode && $parentAcc && $this->norm($parentNewName) === $this->norm($acc['name'])) {
                    $keep[] = $this->row($acc, null, 'يحمل اسم أبيه نفسه؛ يبقى تحته كحساب فرعي');
                    continue;
                }
                if (!$targetNode) {
                    if ($acc['level'] <= 3) {
                        $decide[] = $this->row($acc, null, 'لا مقابل بالاسم في شجرة البرنامج؛ يحدد المحاسب الأب المناسب أو الإبقاء');
                    }
                    continue;
                }
                $action = 'auto';
                $note = '';
            }

            if ($targetNode) {
                $matchedTargetCodes[$targetNode['code']] = $acc['code'];
            }

            switch (true) {
                case $action === 'delete':
                    $delete[] = $this->row($acc, null, $note);
                    break;
                case $action === 'decide':
                    $decide[] = $this->row($acc, $targetNode, $note);
                    break;
                case $action === 'keep':
                    $keep[] = $this->row($acc, null, $note);
                    break;
                case str_starts_with($action, 'merge:'):
                    $into = $currentByCode->get((int) substr($action, 6));
                    $merge[] = [
                        $acc['code'], $acc['name'], $acc['entries'], $acc['children'],
                        $into['code'] ?? '', $into['name'] ?? '', $note,
                    ];
                    break;
                case $action === 'move':
                    $move[] = $this->moveRow($acc, $targetNode, $currentByCode, $note, true);
                    break;
                default: // rename أو auto
                    $nameChanges = trim($acc['name']) !== trim($targetNode['name']);
                    $parentChanges = $this->parentChanges($acc, $targetNode, $currentByCode, $targetByCode);
                    if ($nameChanges) {
                        $this->newNameByCode[$acc['code']] = $targetNode['name'];
                        $rename[] = [
                            $acc['code'], $acc['name'], $targetNode['name'], $targetNode['code'],
                            $parentChanges ? $this->targetParentLabel($targetNode, $targetByCode) : $this->resolvedParentLabel($acc, $currentByCode),
                            $acc['entries'], $note,
                        ];
                    }
                    if ($parentChanges) {
                        $move[] = $this->moveRow($acc, $targetNode, $currentByCode, $note, false);
                    }
                    if (!$nameChanges && !$parentChanges) {
                        $keep[] = $this->row($acc, $targetNode, $note ?: 'مطابق للبرنامج اسماً وموقعاً');
                    }
            }
        }

        // ما يُنشأ: كل حساب في شجرة البرنامج لم يُطابقه أي حساب حالي
        $create = [];
        foreach ($target as $t) {
            if (isset($matchedTargetCodes[$t['code']]) || $this->isAutoNationalityLeaf($t['name'])) {
                continue;
            }
            if ($this->currentHasName($current, $t['name'])) {
                continue;
            }
            $create[] = [
                $t['code'], $t['name'], $t['level'], $t['parent_code'] ?? '',
                $t['parent_code'] ? ($targetByCode->get($t['parent_code'])['name'] ?? '') : 'بدون',
                $t['status'] === 'debtor' ? 'مدين' : 'دائن', $t['type_label'],
                $t['source'],
            ];
        }
        usort($create, fn ($a, $b) => [$a[2], (string) $a[0]] <=> [$b[2], (string) $b[0]]);

        $path = $this->option('output') ?: base_path('finance_tree/chart-merge-guide-' . now()->format('Ymd') . '.xlsx');
        $this->writeWorkbook($path, $target, $targetByCode, $matchedTargetCodes, $rename, $create, $move, $merge, $delete, $decide, $keep);

        $this->table(['الورقة', 'عدد الصفوف'], [
            ['إعادة تسمية', count($rename)],
            ['إنشاء', count($create)],
            ['نقل', count($move)],
            ['دمج', count($merge)],
            ['حذف (فارغة)', count($delete)],
            ['قرار المحاسب', count($decide)],
            ['بلا تغيير', count($keep)],
        ]);
        $this->info('تم إنشاء الملف: ' . $path);

        return self::SUCCESS;
    }

    // ---------------------------------------------------------------- data

    private function targetTree(): Collection
    {
        $rows = collect();
        $typeLabels = $this->typeLabels();

        // AccountSeeder: [code, 'name', 'status', level, parent|null, statementType]
        $seeder = file_get_contents(base_path('packages/finance/database/seeders/AccountSeeder.php'));
        preg_match_all("/\[(\d+),\s*'([^']+)',\s*'(debtor|creditor)',\s*(\d+),\s*(null|\d+),/u", $seeder, $m, PREG_SET_ORDER);
        $overrides = [];
        preg_match_all('/(\d+)\s*=>\s*AccountTypesEnum::(\w+)->value/', $seeder, $o, PREG_SET_ORDER);
        foreach ($o as $ov) {
            $overrides[(int) $ov[1]] = constant(AccountTypesEnum::class . '::' . $ov[2])->value;
        }
        foreach ($m as $r) {
            $type = $overrides[(int) $r[1]] ?? AccountTypesEnum::NormalAccount->value;
            $rows->push([
                'code' => (int) $r[1], 'name' => trim($r[2]), 'status' => $r[3], 'level' => (int) $r[4],
                'parent_code' => $r[5] === 'null' ? null : (int) $r[5],
                'type' => $type, 'type_label' => $typeLabels[$type] ?? 'حساب عادي', 'source' => 'الشجرة الأساسية',
            ]);
        }

        // RecruitmentRentalAccountsSeeder: مصفوفات مفاتيح
        $rental = file_get_contents(base_path('packages/finance/database/seeders/RecruitmentRentalAccountsSeeder.php'));
        preg_match_all("/'code'\s*=>\s*(\d+),\s*'name'\s*=>\s*'([^']+)',\s*'status'\s*=>\s*'(debtor|creditor)',\s*'level'\s*=>\s*(\d+),\s*'parent_code'\s*=>\s*(null|\d+)/u", $rental, $m2, PREG_SET_ORDER);
        foreach ($m2 as $r) {
            if ($rows->firstWhere('code', (int) $r[1])) {
                continue;
            }
            $rows->push([
                'code' => (int) $r[1], 'name' => trim($r[2]), 'status' => $r[3], 'level' => (int) $r[4],
                'parent_code' => $r[5] === 'null' ? null : (int) $r[5],
                'type' => AccountTypesEnum::NormalAccount->value, 'type_label' => 'حساب عادي', 'source' => 'حسابات التأجير',
            ]);
        }

        return $rows->sortBy([['level', 'asc'], ['code', 'asc']])->values();
    }

    private function currentTree(): Collection
    {
        return collect(DB::select(
            'SELECT a.id, a.code, a.name, a.level, a.type, a.status, a.parent_id, p.code AS parent_code, p.name AS parent_name,
                    (SELECT COUNT(*) FROM fn_constraint_details d WHERE d.account_id = a.id) AS entries,
                    (SELECT COUNT(*) FROM fn_accounts c WHERE c.parent_id = a.id) AS children
             FROM fn_accounts a LEFT JOIN fn_accounts p ON p.id = a.parent_id
             ORDER BY a.level, a.code'
        ))->map(fn ($r) => (array) $r)->map(function ($r) {
            $r['code'] = (int) $r['code'];
            $r['type'] = (int) $r['type'];
            $r['level'] = (int) $r['level'];
            $r['parent_code'] = $r['parent_code'] === null ? null : (int) $r['parent_code'];
            return $r;
        });
    }

    private function typeLabels(): array
    {
        $labels = [];
        foreach (AccountTypesEnum::cases() as $case) {
            $labels[$case->value] = __('finance::finance.' . $case->name, [], 'ar');
        }
        return $labels;
    }

    // ---------------------------------------------------------------- matching helpers

    private function norm(string $name): string
    {
        $name = preg_replace('/[إأآ]/u', 'ا', $name);
        $name = str_replace(['ى', 'ة'], ['ي', 'ه'], $name);
        $name = preg_replace('/\s*-\s*/u', ' - ', $name);
        $name = preg_replace('/\s+/u', ' ', $name);
        return trim($name);
    }

    private array $newNameByCode = [];

    private function findTarget(string|int $to, Collection $byNorm, Collection $byCode, int $level): ?array
    {
        if (is_int($to)) {
            return $byCode->get($to);
        }
        return $this->closest($byNorm->get($this->norm($to)), $level);
    }

    /** من بين حسابات تحمل الاسم نفسه على مستويات مختلفة يُختار الأقرب مستوى. */
    private function closest(?Collection $candidates, int $level): ?array
    {
        if (!$candidates || $candidates->isEmpty()) {
            return null;
        }
        return $candidates->sortBy(fn ($t) => [abs($t['level'] - $level), $t['level']])->first();
    }

    private function isAutoNationalityLeaf(string $name): bool
    {
        $prefixes = [
            'مبيعات ', 'تكلفة مبيعات ', 'خصم مبيعات ', 'التزامات عقود (', 'ايراد نقل خدمات ', 'إيراد نقل خدمات ',
            'مصروفات جوب اوردر ', 'مصروفات انهاء اجراءات سفارة ', 'مردودات تكلفة ', 'مردودات مبيعات استقدام ',
            'مردودات نقل خدمات ', 'مصروفات مدفوعة مقدما ايواء ', 'دفعات مقدمة نقل خدمات ', 'إيراد التأمين ', 'مصروف التأمين ',
        ];
        $roots = ['مبيعات الاستقدام', 'تكلفة مبيعات الاستقدام', 'خصم مبيعات', 'التزامات عقود', 'ايرادات نقل خدمات',
            'مردودات تكلفة الاستقدام', 'مردودات مبيعات الاستقدام', 'دفعات مقدمة نقل خدمات', 'مصروفات مدفوعة مقدما - الايواء'];
        if (in_array(trim($name), $roots, true)) {
            return false;
        }
        foreach ($prefixes as $p) {
            if (str_starts_with(trim($name), $p)) {
                return true;
            }
        }
        return false;
    }

    private function currentHasName(Collection $current, string $name): bool
    {
        $n = $this->norm($name);
        return $current->contains(fn ($a) => $a['type'] !== AccountTypesEnum::Client->value && $this->norm($a['name']) === $n);
    }

    private function parentChanges(array $acc, array $targetNode, Collection $currentByCode, Collection $targetByCode): bool
    {
        if ($targetNode['parent_code'] === null) {
            return $acc['parent_code'] !== null;
        }
        $targetParent = $targetByCode->get($targetNode['parent_code']);
        $currentParent = $acc['parent_code'] !== null ? $currentByCode->get($acc['parent_code']) : null;
        if (!$targetParent || !$currentParent) {
            return true;
        }
        $parentAction = self::OVERRIDES[$currentParent['code']][1] ?? null;
        if (in_array($parentAction, ['keep', 'decide'], true)) {
            return false; // الأب يبقى كطبقة وسيطة؛ فروعه لا تتحرك
        }
        // الأب الحالي بعد إعادة تسميته: نقارن بالاسم المستهدف للأب الحالي
        $currentParentTargetName = $this->newNameByCode[$currentParent['code']] ?? $currentParent['name'];
        return $this->norm($currentParentTargetName) !== $this->norm($targetParent['name']);
    }

    private function moveRow(array $acc, ?array $targetNode, Collection $currentByCode, string $note, bool $targetIsParent): array
    {
        $newParent = $targetIsParent ? ($targetNode['name'] ?? '') : ($targetNode ? $this->targetParentName($targetNode) : '');
        return [
            $acc['code'], $acc['name'], $acc['parent_code'] . ' ' . $acc['parent_name'],
            $newParent, $acc['entries'], $acc['children'], $note,
        ];
    }

    private array $targetParentCache = [];

    private function targetParentName(array $targetNode): string
    {
        static $byCode = null;
        $byCode ??= $this->targetTree()->keyBy('code');
        return $targetNode['parent_code'] ? ($byCode->get($targetNode['parent_code'])['name'] ?? '') : 'بدون';
    }

    /** الأب الحالي باسمه بعد إعادة التسمية إن كانت مقررة له. */
    private function resolvedParentLabel(array $acc, Collection $currentByCode): string
    {
        if ($acc['parent_code'] === null) {
            return 'بدون (رئيسي)';
        }
        $parent = $currentByCode->get($acc['parent_code']);
        return $parent['code'] . ' ' . ($this->newNameByCode[$parent['code']] ?? $parent['name']);
    }

    private function targetParentLabel(array $targetNode, Collection $targetByCode): string
    {
        if (!$targetNode['parent_code']) {
            return 'بدون (رئيسي)';
        }
        $p = $targetByCode->get($targetNode['parent_code']);
        return $p ? $p['code'] . ' ' . $p['name'] : (string) $targetNode['parent_code'];
    }

    private function row(array $acc, ?array $targetNode, string $note): array
    {
        return [
            $acc['code'], $acc['name'], $acc['level'], $acc['parent_code'] . ' ' . $acc['parent_name'],
            $acc['entries'], $acc['children'], $targetNode ? $targetNode['code'] . ' ' . $targetNode['name'] : '', $note,
        ];
    }

    // ---------------------------------------------------------------- workbook

    private function writeWorkbook(string $path, Collection $target, Collection $targetByCode, array $matched,
        array $rename, array $create, array $move, array $merge, array $delete, array $decide, array $keep): void
    {
        $book = new Spreadsheet();
        $book->getDefaultStyle()->getFont()->setName('Arial')->setSize(11);

        $steps = $book->getActiveSheet();
        $steps->setTitle('الخطوات');
        $this->fill($steps, ['#', 'الخطوة', 'الورقة', 'عدد الصفوف', 'ملاحظة'], [
            [1, 'أخذ نسخة احتياطية وإيقاف القيود التلقائية من إعدادات النظام', '', '', 'قبل أي تعديل'],
            [2, 'إعادة تسمية الحسابات الموجودة على أسماء البرنامج', 'إعادة تسمية', count($rename), 'لا تؤثر على القيود'],
            [3, 'إنشاء الحسابات الناقصة تحت آبائها المذكورين', 'إنشاء', count($create), 'يُنشأ الأب قبل الابن (الورقة مرتبة بالمستوى)'],
            [4, 'نقل الحسابات إلى آبائها الجدد', 'نقل', count($move), 'النقل يعيد ترقيم كل ما تحت الحساب'],
            [5, 'دمج الحسابات المكررة في الأصلية', 'دمج', count($merge), 'يُنفَّذ برمجياً: يعيد توجيه كل الروابط ثم يحذف المكرر'],
            [6, 'حذف الحسابات الفارغة فقط', 'حذف', count($delete), 'لا يُحذف أي حساب عليه قيود أو فروع'],
            [7, 'البت في الحسابات التي تحتاج قراراً محاسبياً', 'قرار المحاسب', count($decide), 'تُكتب القرارات في عمود «قرار المحاسب»'],
            [8, 'ضبط إعدادات القيود التلقائية على الحسابات الجديدة', '', '', 'حسابات التأجير وسندا القبض والصرف ومصروف الإيواء فارغة حالياً'],
            [9, 'تشغيل تسوية حسابات الجنسيات والتحقق بميزان المراجعة', '', '', 'finance:reconcile-nationality-accounts ثم مقارنة المجاميع قبل وبعد'],
        ], [6, 70, 16, 12, 60]);

        $tree = $book->createSheet();
        $tree->setTitle('الشجرة المستهدفة');
        $rows = [];
        foreach ($target as $t) {
            $parent = $t['parent_code'] ? $targetByCode->get($t['parent_code']) : null;
            $rows[] = [
                $t['code'], str_repeat('    ', $t['level'] - 1) . $t['name'], $t['level'],
                $parent ? $parent['code'] . ' ' . $parent['name'] : 'بدون',
                $t['status'] === 'debtor' ? 'مدين' : 'دائن', $t['type_label'],
                isset($matched[$t['code']]) ? 'موجود (كود حالي ' . $matched[$t['code']] . ')' : 'يُنشأ',
                $t['source'],
            ];
        }
        $this->fill($tree, ['الكود', 'الاسم', 'المستوى', 'الحساب الرئيسي', 'الطبيعة', 'النوع', 'الحالة الآن', 'المصدر'], $rows, [10, 48, 9, 36, 9, 20, 24, 18]);

        $this->fill($book->createSheet()->setTitle('إعادة تسمية'),
            ['الكود الحالي', 'الاسم الحالي', 'الاسم الجديد', 'كود البرنامج', 'الحساب الرئيسي بعد الترتيب', 'عدد القيود', 'ملاحظة'],
            $rename, [12, 42, 42, 12, 36, 10, 60]);

        $this->fill($book->createSheet()->setTitle('إنشاء'),
            ['كود البرنامج', 'الاسم', 'المستوى', 'كود الأب', 'الحساب الرئيسي', 'الطبيعة', 'النوع', 'المصدر'],
            $create, [12, 48, 9, 10, 40, 9, 20, 18]);

        $this->fill($book->createSheet()->setTitle('نقل'),
            ['الكود الحالي', 'الاسم', 'الأب الحالي', 'الأب الجديد', 'عدد القيود', 'عدد الفروع', 'ملاحظة'],
            $move, [12, 42, 34, 40, 10, 10, 60]);

        $this->fill($book->createSheet()->setTitle('دمج'),
            ['الكود المكرر', 'اسم المكرر', 'قيوده', 'فروعه', 'يُدمج في (كود)', 'يُدمج في (اسم)', 'ملاحظة'],
            $merge, [12, 40, 9, 9, 14, 40, 70]);

        $this->fill($book->createSheet()->setTitle('حذف'),
            ['الكود', 'الاسم', 'المستوى', 'الأب', 'عدد القيود', 'عدد الفروع', 'المقابل', 'ملاحظة'],
            $delete, [10, 40, 9, 34, 10, 10, 30, 50]);

        $decideRows = array_map(fn ($r) => array_merge($r, ['']), $decide);
        $this->fill($book->createSheet()->setTitle('قرار المحاسب'),
            ['الكود', 'الاسم', 'المستوى', 'الأب الحالي', 'عدد القيود', 'عدد الفروع', 'المقترح في البرنامج', 'الشرح', 'قرار المحاسب'],
            $decideRows, [10, 40, 9, 34, 10, 10, 34, 70, 30]);

        $this->fill($book->createSheet()->setTitle('بلا تغيير'),
            ['الكود', 'الاسم', 'المستوى', 'الأب', 'عدد القيود', 'عدد الفروع', 'المقابل في البرنامج', 'ملاحظة'],
            $keep, [10, 40, 9, 34, 10, 10, 34, 50]);

        $book->setActiveSheetIndex(0);
        (new Xlsx($book))->save($path);
    }

    private function fill(Worksheet $sheet, array $headers, array $rows, array $widths): void
    {
        $sheet->setRightToLeft(true);
        $sheet->fromArray($headers, null, 'A1');
        if ($rows) {
            $sheet->fromArray($rows, null, 'A2');
        }
        $last = $sheet->getHighestColumn();
        $sheet->getStyle('A1:' . $last . '1')->getFont()->setBold(true);
        $sheet->getStyle('A1:' . $last . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E3F0EA');
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $last . max(1, count($rows) + 1));
        foreach ($widths as $i => $w) {
            $sheet->getColumnDimensionByColumn($i + 1)->setWidth($w);
        }
        $sheet->getStyle('A1:' . $last . (count($rows) + 1))->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
    }
}
