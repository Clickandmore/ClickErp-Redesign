<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

/**
 * فحص تغطية الفروع في البرنامج كله (تعميم branch_id).
 *
 * يجيب على أربعة أسئلة:
 *  1) الجداول: أي جدول تشغيلي/مالي ما زال بلا عمود branch_id؟ (تسريب على مستوى القاعدة)
 *  2) الموديلات: أي موديل جدوله يحمل branch_id لكنه لا يستعمل AssignsBranch؟ (كتابة بلا ختم فرع)
 *  3) المتحكمات: أي كنترولر يقرأ موديلاً مربوطاً بفرع بلا أي فلترة فرع؟ (قراءة تعبر الفروع)
 *  4) البيانات: كم صفاً ما زال branch_id فيه NULL؟ (فجوة backfill)
 *
 * الفحص المنطقي للصلاحيات في: permissions:doctor — هذا الأمر للفروع فقط.
 */
class BranchesAuditCommand extends Command
{
    protected $signature = 'branches:audit
        {--json : Output the report as JSON}
        {--details : Show the complete detail lists}
        {--section= : Limit the audit to one section: tables|models|controllers|data|raw}
        {--limit=40 : Maximum rows per list in text output}';

    protected $description = 'Audit branch coverage across tables, models, controllers, raw queries, and data.';

    /** جداول لا معنى لربطها بفرع (مرجعية، ترجمات، بنية النظام). */
    private const IGNORED_TABLE_PATTERNS = [
        '*_translations', 'migrations', 'password_resets', 'password_reset_tokens', 'failed_jobs',
        'jobs', 'job_batches', 'cache', 'cache_locks', 'sessions', 'personal_access_tokens',
        'permissions', 'roles', 'model_has_permissions', 'model_has_roles', 'role_has_permissions',
        'permission_role', 'branches', 'role_branch_permissions', 'role_branch_permission_scopes',
        'countries', 'cities', 'nationalities', 'languages', 'religions', 'social_status', 'ages',
        'skills', 'job_titles', 'job_types', 'jop_type_offices', 'experience', 'airports',
        'settings', 'new_settings', 'develop_settings', 'dev_superadmin_settings',
        'system_module_settings', 'sms_templates', 'cookies', 'about_us', 'sliders', 'statistics',
        'blogs', 'fast_news', 'services', 'packages', 'contacts', 'app_visits',
        'accommodation_types', 'maid_job_titles', 'maid_jobs', 'telescope_*', 'activity_log',
        'notifications', 'admin_notifications', 'hr_countries', 'support_knowledge_articles',
        'fn_accounts', 'fn_years', 'fn_items', 'fn_units', 'fn_banks', 'fn_taxes',
        'fn_confirm_settings', 'fn_product_num_settings', 'fn_stock_settings', 'fn_tax_settings',
        'zatca_settings', 'fn_categories', 'fn_supplier_types', 'fn_client_categories',
    ];

    /** أعمدة تدل على أن الصف يخص عملية/كياناً يملكه فرع. */
    private const LINK_COLUMNS = [
        'order_id', 'contract_id', 'rental_contract_id', 'recruitment_contract_id', 'maid_id',
        'client_id', 'user_id', 'customer_id', 'admin_id', 'created_by_id', 'employee_id',
        'complaint_id', 'invoice_id', 'financial_request_id', 'marketer_id', 'bond_id',
        'constraint_id', 'sponsorship_transfer_id', 'cost_center_id', 'account_id',
    ];

    /**
     * موديلات بلا عزل قراءة تلقائي بقرار موثّق، فلا تُحسب ثغرات.
     * انظر docs/BRANCHES_TECHNICAL_SPEC.md §3.
     */
    private const DELIBERATELY_UNSCOPED = [
        'App\\Models\\auth\\Admin' => 'The admin guard reads the same table; scoping it would break login',
        'App\\Models\\Nami\\Admin' => 'Uses the same admins table',
        'App\\Models\\ingaz\\User' => 'Has complete custom branch logic in UserController',
        'Nami\\Finance\\Models\\Account' => 'Codes are tree-wide and generated from max(code) in 10 locations',
        'Nami\\Finance\\Models\\CostCenter' => 'Shared master data',
        'Nami\\Finance\\Models\\Client' => 'Shared master data',
        'Nami\\Finance\\Models\\Supplier' => 'Shared master data',
        'Nami\\Finance\\Models\\Box' => 'Shared master data',
    ];

    /**
     * موديلات لا تُختَم بالفرع تلقائياً بقرار موثّق.
     * الفرع يُسنَد لها بإجراء صريح من المستخدم، لا باشتقاقه من منشئ السجل.
     */
    private const DELIBERATELY_UNSTAMPED = [
        'App\\Models\\auth\\Admin' => 'The employee branch is selected on the employee screen',
        'App\\Models\\Nami\\Admin' => 'Uses the same admins table',
        'App\\Models\\Maid' => 'The maid branch is moved using the maids_change_branch permission',
        'App\\Models\\ingaz\\Maid' => 'The maid branch is moved using the maids_change_branch permission',
        'App\\Models\\ingaz\\RecruitmentRequestMaid' => 'The recruitment_requests table is stamped through RecruitmentRequest',
    ];

    /**
     * ملفات تقرأ جداول معزولة باستعلام خام بقرار موثّق، فلا تُحسب تسريباً.
     * القاعدة العامة: الاستعلام المرتبط بمستند محدّد لا يُعزل — المستند نفسه
     * محمي، وعزل سطوره قد يُصفّر قيمة فاتورة قديمة بلا فرع.
     */
    private const RAW_QUERY_EXCEPTIONS = [
        'app/Console/Commands/' => 'Artisan and cron commands operate company-wide',
        'app/Http/Controllers/site/' => 'Web guard; the customer route has its own filtering',
        'app/Http/Controllers/Api/' => 'API guard',
        'app/Http/Controllers/APPAPIControllers/' => 'Mobile application API guard',
        'packages/finance/src/Services/AccountService.php' => 'Account code generation must see the complete tree',
        'packages/finance/src/Services/ConstraintNumberService.php' => 'Journal entry numbering is company-wide',
        'packages/finance/src/Services/PurchaseService.php' => 'The scoped Purchase model is the query root',
        'packages/finance/src/Services/PurchaseItemService.php' => 'The scoped PurchaseItem model is the query root',
        'packages/finance/src/Services/BillSalesService.php' => 'The scoped Sale model is the query root',
        'packages/finance/src/Services/BillSalesItemService.php' => 'The scoped SaleItem model is the query root',
        'packages/finance/src/Traits/ZatcaInvoicesTrait.php' => 'Match occurs inside a text comment',
        'app/Services/ingaz/RentalContractFinanceService.php' => 'Linked to a specific invoice entry by ID',
        'app/Services/ingaz/RentalClosureCorrectionService.php' => 'Linked to a specific invoice entry by ID',
        'app/Http/Controllers/ingaz/ShelterContractController.php' => 'Nested query inside an already scoped query',
        'app/Services/ingaz/NationalityFinanceAccountReconciliationService.php' => 'Company-wide chart-of-accounts reconciliation tool',
        'app/Services/commissions/CommissionProfileService.php' => 'Marketer employee list; Admin is deliberately unscoped',
        'app/Http/Controllers/admin/office/OfficeController.php' => 'External offices are company-wide entities',
    ];

    /** استدعاءات تُعتبر فلترة فرع صحيحة داخل الكنترولر. */
    private const FILTER_MARKERS = [
        'applyRequestBranchFilter', 'applyToQuery', 'applyBranchIdToQuery',
        'assertCanAccessBranchModel', 'assertCanAccessModel', 'BranchAccessService',
        'branchSelectorData', 'selectedBranchId', 'BranchReportFilter',
    ];

    public function handle(): int
    {
        $section = $this->option('section');
        $branchTables = $this->branchTables();

        $report = ['branch_tables' => $branchTables->count()];

        if (!$section || $section === 'tables') {
            $report['tables'] = $this->auditTables($branchTables);
        }

        if (!$section || $section === 'models') {
            $report['models'] = $this->auditModels($branchTables);
        }

        if (!$section || $section === 'controllers') {
            $report['controllers'] = $this->auditControllers($branchTables);
        }

        if (!$section || $section === 'data') {
            $report['data'] = $this->auditData($branchTables);
        }

        if (!$section || $section === 'raw') {
            $report['raw'] = $this->auditRawQueries();
        }

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

            return $this->exitCode($report);
        }

        $this->renderReport($report);

        return $this->exitCode($report);
    }

    /* ---------------------------------------------------------------- الجداول */

    /** أسماء الجداول التي تحمل عمود branch_id فعلياً. */
    private function branchTables(): Collection
    {
        return collect(DB::select(
            'SELECT table_name AS name FROM information_schema.columns
             WHERE table_schema = ? AND column_name = ? ORDER BY table_name',
            [DB::getDatabaseName(), 'branch_id']
        ))->pluck('name')->map(fn ($name) => (string) $name)->values();
    }

    private function allTables(): Collection
    {
        return collect(DB::select(
            'SELECT table_name AS name, table_rows AS row_count FROM information_schema.tables
             WHERE table_schema = ? AND table_type = ? ORDER BY table_name',
            [DB::getDatabaseName(), 'BASE TABLE']
        ))->map(fn ($row) => ['name' => (string) $row->name, 'rows' => (int) $row->row_count]);
    }

    private function auditTables(Collection $branchTables): array
    {
        $covered = $branchTables->flip();
        $missing = [];

        foreach ($this->allTables() as $table) {
            $name = $table['name'];

            if ($covered->has($name) || $this->isIgnoredTable($name)) {
                continue;
            }

            $links = $this->linkColumns($name);
            if ($links === []) {
                continue;
            }

            $missing[] = [
                'table' => $name,
                'rows' => $table['rows'],
                'links' => $links,
                'group' => $this->tableGroup($name),
            ];
        }

        usort($missing, fn ($a, $b) => $b['rows'] <=> $a['rows']);

        return [
            'covered' => $branchTables->count(),
            'missing_count' => count($missing),
            'missing' => $missing,
            'by_group' => collect($missing)->groupBy('group')->map->count()->sortDesc()->all(),
        ];
    }

    private function isIgnoredTable(string $table): bool
    {
        foreach (self::IGNORED_TABLE_PATTERNS as $pattern) {
            if (Str::is($pattern, $table)) {
                return true;
            }
        }

        return Str::endsWith($table, ['_translations', '_translation']);
    }

    private function linkColumns(string $table): array
    {
        $columns = collect(Schema::getColumnListing($table));

        return $columns->intersect(self::LINK_COLUMNS)->values()->all();
    }

    private function tableGroup(string $table): string
    {
        return match (true) {
            Str::startsWith($table, 'fn_') => 'Finance',
            Str::startsWith($table, 'hr_') => 'Human Resources',
            Str::startsWith($table, 'crm_') => 'CRM',
            Str::startsWith($table, 'commission') => 'Commissions',
            Str::startsWith($table, 'maid') => 'Maid Profiles',
            Str::startsWith($table, ['contract', 'rental', 'recruitment', 'order']) => 'Contracts and Orders',
            Str::startsWith($table, ['travel', 'deportation', 'shelter']) => 'Travel and Shelter',
            default => 'Other',
        };
    }

    /* -------------------------------------------------------------- الموديلات */

    private function modelClasses(): Collection
    {
        $roots = array_filter([
            app_path('Models'),
            base_path('packages/finance/src/Models'),
            base_path('packages/hr/src/Models'),
            base_path('packages/crm/src/Models'),
            base_path('packages/access-control/src/Models'),
        ], fn ($path) => is_dir($path));

        $classes = collect();

        foreach (Finder::create()->files()->in($roots)->name('*.php') as $file) {
            $class = $this->classFromFile($file);

            if (!$class) {
                continue;
            }

            // بعض موديلات الحزم ترث من عميل خارجي غير محمّل، فـ class_exists نفسه قد يرمي.
            try {
                if (!class_exists($class)) {
                    continue;
                }

                $reflection = new ReflectionClass($class);
            } catch (\Throwable) {
                continue;
            }

            if ($reflection->isAbstract() || !$reflection->isSubclassOf(\Illuminate\Database\Eloquent\Model::class)) {
                continue;
            }

            $classes->push($class);
        }

        return $classes->unique()->values();
    }

    private function classFromFile(SplFileInfo $file): ?string
    {
        $contents = file_get_contents($file->getRealPath()) ?: '';

        if (!preg_match('/^namespace\s+([^;]+);/m', $contents, $namespace)) {
            return null;
        }

        if (!preg_match('/^(?:final\s+|abstract\s+)?class\s+(\w+)/m', $contents, $class)) {
            return null;
        }

        return trim($namespace[1]) . '\\' . $class[1];
    }

    private function auditModels(Collection $branchTables): array
    {
        $covered = $branchTables->flip();
        $unstamped = [];
        $orphanTrait = [];
        $stamped = 0;

        foreach ($this->modelClasses() as $class) {
            try {
                $model = new $class;
                $table = $model->getTable();
            } catch (\Throwable) {
                continue;
            }

            $traits = $this->traitsOf($class);
            $usesTrait = in_array(\App\Models\Concerns\AssignsBranch::class, $traits, true)
                || in_array(\App\Models\Concerns\AssignsOrderBranch::class, $traits, true)
                || $this->stampsBranchManually($class);
            $hasColumn = $covered->has($table);

            if ($hasColumn && !$usesTrait) {
                if (! array_key_exists($class, self::DELIBERATELY_UNSTAMPED)) {
                    $unstamped[] = ['model' => $class, 'table' => $table];
                }

                continue;
            }

            if (!$hasColumn && $usesTrait) {
                $orphanTrait[] = ['model' => $class, 'table' => $table];
                continue;
            }

            if ($hasColumn && $usesTrait) {
                $stamped++;
            }
        }

        return [
            'scoped' => $this->scopedModelCount($branchTables),
            'stamped' => $stamped,
            'unstamped_count' => count($unstamped),
            'unstamped' => $unstamped,
            'orphan_trait_count' => count($orphanTrait),
            'orphan_trait' => $orphanTrait,
        ];
    }

    /** موديلات تختم الفرع بنفسها داخل booted() بدل استعمال التريت. */
    private function stampsBranchManually(string $class): bool
    {
        try {
            $file = (new ReflectionClass($class))->getFileName();
        } catch (\Throwable) {
            return false;
        }

        if (!$file || !is_file($file)) {
            return false;
        }

        $contents = file_get_contents($file) ?: '';

        return (bool) preg_match('/->branch_id\s*=\s*\$branchId/', $contents);
    }

    /** كم موديلاً يحمل عزل القراءة التلقائي. */
    private function scopedModelCount(Collection $branchTables): int
    {
        $covered = $branchTables->flip();
        $count = 0;

        foreach ($this->modelClasses() as $class) {
            try {
                $table = (new $class)->getTable();
            } catch (\Throwable) {
                continue;
            }

            if ($covered->has($table) && in_array(\App\Models\Concerns\BranchScoped::class, $this->traitsOf($class), true)) {
                $count++;
            }
        }

        return $count;
    }

    private function traitsOf(string $class): array
    {
        $traits = [];

        for ($current = $class; $current; $current = get_parent_class($current)) {
            foreach (class_uses($current) ?: [] as $trait) {
                $traits[] = $trait;
                foreach (class_uses($trait) ?: [] as $nested) {
                    $traits[] = $nested;
                }
            }
        }

        return array_unique($traits);
    }

    /* ------------------------------------------------------------- المتحكمات */

    private function auditControllers(Collection $branchTables): array
    {
        $branchModels = $this->branchModelShortNames($branchTables);
        $filtered = [];
        $leaking = [];

        $roots = array_filter([
            app_path('Http/Controllers'),
            base_path('packages/finance/src/Http/Controllers'),
            base_path('packages/hr/src/Http/Controllers'),
            base_path('packages/crm/src/Http/Controllers'),
        ], fn ($path) => is_dir($path));

        foreach (Finder::create()->files()->in($roots)->name('*Controller.php') as $file) {
            $contents = file_get_contents($file->getRealPath()) ?: '';
            $relative = Str::after($file->getRealPath(), base_path() . '/');

            $used = $branchModels->filter(fn ($_, $short) => (bool) preg_match('/\b' . preg_quote($short, '/') . '::/', $contents));

            if ($used->isEmpty()) {
                continue;
            }

            $hasFilter = Str::contains($contents, self::FILTER_MARKERS);

            if ($hasFilter) {
                $filtered[] = $relative;
                continue;
            }

            $leaking[] = [
                'controller' => $relative,
                'area' => $this->controllerArea($relative),
                'models' => $used->keys()->values()->all(),
            ];
        }

        usort($leaking, fn ($a, $b) => count($b['models']) <=> count($a['models']));

        $backOffice = array_values(array_filter($leaking, fn ($row) => $row['area'] === 'back_office'));
        $external = array_values(array_filter($leaking, fn ($row) => $row['area'] !== 'back_office'));

        return [
            'filtered_count' => count($filtered),
            'filtered' => $filtered,
            'leaking_count' => count($leaking),
            'leaking' => $leaking,
            'back_office_count' => count($backOffice),
            'back_office' => $backOffice,
            'external_count' => count($external),
            'external' => $external,
        ];
    }

    /** شاشات الموظفين (back office) هي ما يهم عزل الفروع؛ واجهات العميل والتطبيق مسار مختلف. */
    private function controllerArea(string $relative): string
    {
        return match (true) {
            Str::contains($relative, ['Controllers/site/', 'Controllers/Api/', 'Controllers/APPAPIControllers/']) => 'external',
            Str::contains($relative, 'Controllers/Agent/') => 'agent',
            default => 'back_office',
        };
    }

    /**
     * الاسم القصير لكل موديل جدوله يحمل branch_id ولا يحمل عزلاً تلقائياً،
     * لمطابقته داخل ملفات المتحكمات.
     *
     * الموديلات التي تستعمل BranchScoped محميّة على مستوى الاستعلام نفسه،
     * فالكنترولر الذي يقرأها لا يحتاج فلترة يدوية ولا يُعتبر تسريباً.
     */
    private function branchModelShortNames(Collection $branchTables): Collection
    {
        $covered = $branchTables->flip();
        $names = collect();

        foreach ($this->modelClasses() as $class) {
            try {
                $table = (new $class)->getTable();
            } catch (\Throwable) {
                continue;
            }

            if (! $covered->has($table)) {
                continue;
            }

            if (in_array(\App\Models\Concerns\BranchScoped::class, $this->traitsOf($class), true)) {
                continue;
            }

            if (array_key_exists($class, self::DELIBERATELY_UNSCOPED)) {
                continue;
            }

            $names->put(class_basename($class), $class);
        }

        return $names;
    }

    /* ---------------------------------------------------------------- البيانات */

    private function auditData(Collection $branchTables): array
    {
        $gaps = [];
        $clean = 0;

        foreach ($branchTables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $total = (int) DB::table($table)->count();

            if ($total === 0) {
                continue;
            }

            $null = (int) DB::table($table)->whereNull('branch_id')->count();

            if ($null === 0) {
                $clean++;
                continue;
            }

            $gaps[] = [
                'table' => $table,
                'rows' => $total,
                'null_branch' => $null,
                'percent' => round($null / $total * 100, 1),
            ];
        }

        usort($gaps, fn ($a, $b) => $b['null_branch'] <=> $a['null_branch']);

        return [
            'clean_tables' => $clean,
            'gap_count' => count($gaps),
            'gaps' => $gaps,
            'null_rows' => array_sum(array_column($gaps, 'null_branch')),
        ];
    }

    /* ------------------------------------------------- الاستعلامات الخام */

    /**
     * الاستعلامات التي تقرأ جداول معزولة بـ Query Builder أو SQL خام.
     *
     * الـ global scope يعمل على Eloquent فقط. أي `DB::table('fn_constraints')`
     * أو `->join('fn_bonds', ...)` أو SQL نصي يتجاوز العزل تماماً — وهذا هو سبب
     * بقاء ميزان المراجعة وكشف الحساب يجمعان كل الفروع رغم عزل الموديلات.
     */
    private function auditRawQueries(): array
    {
        $scopedTables = $this->scopedTables();

        if ($scopedTables->isEmpty()) {
            return ['scoped_tables' => [], 'hits' => [], 'unfiltered_count' => 0, 'filtered_count' => 0];
        }

        $pattern = $scopedTables->map(fn ($table) => preg_quote($table, '/'))->implode('|');
        $hits = [];

        $roots = array_filter([
            app_path(),
            base_path('packages/finance/src'),
            base_path('packages/hr/src'),
            base_path('packages/crm/src'),
        ], fn ($path) => is_dir($path));

        foreach (Finder::create()->files()->in($roots)->name('*.php') as $file) {
            $relative = Str::after($file->getRealPath(), base_path() . '/');

            // الأمر نفسه وطبقة الفروع يقرآن هذه الجداول عمداً.
            if (Str::contains($relative, ['Console/Commands/Branches', 'Services/BranchAccessService', 'Support/BranchQueryContext'])) {
                continue;
            }

            $exception = $this->rawQueryException($relative);

            $lines = file($file->getRealPath()) ?: [];
            $fileMentionsBranch = Str::contains(implode('', $lines), [
                'branch_id', 'BranchAccessService', 'BranchQueryContext', 'BranchReportFilter',
            ]);

            foreach ($lines as $index => $line) {
                if (! preg_match('/(DB::table\(|->join\(|->leftJoin\(|FROM\s+|JOIN\s+)[\x27"\s]*(' . $pattern . ')\b/i', $line, $match)) {
                    continue;
                }

                $hits[] = [
                    'file' => $relative,
                    'line' => $index + 1,
                    'table' => strtolower($match[2]),
                    'status' => $exception !== null
                        ? 'intentional'
                        : $this->classifyRawHit($lines, $index, $fileMentionsBranch),
                    'reason' => $exception,
                    'snippet' => trim($line),
                ];
            }
        }

        $grouped = collect($hits)->groupBy('status');

        return [
            'scoped_tables' => $scopedTables->all(),
            'total' => count($hits),
            'unfiltered' => $grouped->get('unfiltered', collect())->values()->all(),
            'unfiltered_count' => $grouped->get('unfiltered', collect())->count(),
            'review' => $grouped->get('review', collect())->values()->all(),
            'review_count' => $grouped->get('review', collect())->count(),
            'filtered_count' => $grouped->get('filtered', collect())->count(),
            'intentional_count' => $grouped->get('intentional', collect())->count(),
            'by_file' => $grouped->get('unfiltered', collect())->groupBy('file')->map->count()->sortDesc()->all(),
        ];
    }

    /**
     * ثلاث حالات، لأن التصنيف الثنائي ينتج إيجابيات كاذبة كثيرة:
     *  filtered   — شرط الفرع قريب من الاستعلام.
     *  review     — الملف يفلتر بالفرع في مكان ما، لكن ليس قرب هذا السطر (مراجعة يدوية).
     *  unfiltered — الملف كله لا يعرف الفروع إطلاقاً؛ هذا تسريب مؤكد.
     */
    /** سبب استثناء الملف من فحص الاستعلامات الخام، أو null إن لم يكن مستثنى. */
    private function rawQueryException(string $relative): ?string
    {
        foreach (self::RAW_QUERY_EXCEPTIONS as $needle => $reason) {
            if (Str::startsWith($relative, $needle) || $relative === $needle) {
                return $reason;
            }
        }

        return null;
    }

    private function classifyRawHit(array $lines, int $index, bool $fileMentionsBranch): string
    {
        $near = implode('', array_slice($lines, max(0, $index - 25), 60));

        if (str_contains($near, 'branch_id') || str_contains($near, 'BranchReportFilter')) {
            return 'filtered';
        }

        return $fileMentionsBranch ? 'review' : 'unfiltered';
    }

    /** جداول الموديلات التي تحمل عزل قراءة تلقائي. */
    private function scopedTables(): Collection
    {
        $tables = collect();

        foreach ($this->modelClasses() as $class) {
            try {
                $model = new $class;
            } catch (\Throwable) {
                continue;
            }

            if (in_array(\App\Models\Concerns\BranchScoped::class, $this->traitsOf($class), true)) {
                $tables->push($model->getTable());
            }
        }

        return $tables->unique()->sort()->values();
    }

    /* ----------------------------------------------------------------- العرض */

    private function renderReport(array $report): void
    {
        $this->info('=== Branch Coverage Audit ===');
        $this->line('Tables currently containing branch_id: ' . $report['branch_tables']);
        $this->newLine();

        $rows = [];

        if (isset($report['tables'])) {
            $rows[] = ['Operational tables without branch_id', $report['tables']['missing_count'], 'Deferred coverage'];
        }

        if (isset($report['models'])) {
            $rows[] = ['Automatically scoped models (BranchScoped)', $report['models']['scoped'], 'OK'];
            $rows[] = ['Models deliberately left unscoped', count(self::DELIBERATELY_UNSCOPED), 'Intentional'];
            $rows[] = ['Models deliberately left unstamped', count(self::DELIBERATELY_UNSTAMPED), 'Intentional'];
            $rows[] = ['Branch-stamped models (AssignsBranch)', $report['models']['stamped'], 'OK'];
            $rows[] = ['Models with branch_id but without AssignsBranch', $report['models']['unstamped_count'], $this->flag($report['models']['unstamped_count'], 'Unstamped writes')];
            $rows[] = ['Models using AssignsBranch without branch_id', $report['models']['orphan_trait_count'], $this->flag($report['models']['orphan_trait_count'])];
        }

        if (isset($report['controllers'])) {
            $rows[] = ['Controllers applying branch filters', $report['controllers']['filtered_count'], 'OK'];
            $rows[] = ['Back-office screens reading without branch filters', $report['controllers']['back_office_count'], $this->flag($report['controllers']['back_office_count'], 'Read leak')];
            $rows[] = ['Customer/app/agent endpoints without branch filters', $report['controllers']['external_count'], 'Review'];
        }

        if (isset($report['raw'])) {
            $rows[] = ['Raw queries in files with no branch awareness', $report['raw']['unfiltered_count'], $this->flag($report['raw']['unfiltered_count'], 'Confirmed leak')];
            $rows[] = ['Raw queries requiring manual review', $report['raw']['review_count'], 'Review'];
            $rows[] = ['Raw queries deliberately left unscoped', $report['raw']['intentional_count'], 'Intentional'];
        }

        if (isset($report['data'])) {
            $rows[] = ['Tables containing rows with branch_id = NULL', $report['data']['gap_count'], $this->flag($report['data']['gap_count'], 'Backfill required')];
            $rows[] = ['Total rows without a branch', $report['data']['null_rows'], $this->flag($report['data']['null_rows'], 'Backfill required')];
        }

        $this->table(['Check', 'Count', 'Status'], $rows);

        if (isset($report['tables']['by_group']) && $report['tables']['by_group'] !== []) {
            $this->newLine();
            $this->warn('Missing tables by module');
            foreach ($report['tables']['by_group'] as $group => $count) {
                $this->line('  - ' . $group . ': ' . $count);
            }
        }

        if (!$this->option('details')) {
            $this->newLine();
            $this->line('Use --details to show complete lists, or --json to export the report.');

            return;
        }

        $limit = (int) $this->option('limit');

        if (isset($report['tables'])) {
            $this->printList('Tables without branch_id (largest first)', collect($report['tables']['missing'])
                ->map(fn ($row) => sprintf('%s — %s rows — links: %s', $row['table'], number_format($row['rows']), implode(', ', $row['links'])))
                ->all(), $limit);
        }

        if (isset($report['models'])) {
            $this->printList('Models without AssignsBranch despite having branch_id', collect($report['models']['unstamped'])
                ->map(fn ($row) => $row['model'] . ' (' . $row['table'] . ')')->all(), $limit);
            $this->printList('Models using AssignsBranch without a branch_id column', collect($report['models']['orphan_trait'])
                ->map(fn ($row) => $row['model'] . ' (' . $row['table'] . ')')->all(), $limit);
        }

        if (isset($report['controllers'])) {
            $this->printList('Back-office screens reading branch models without filtering', collect($report['controllers']['back_office'])
                ->map(fn ($row) => $row['controller'] . ' => ' . implode(', ', $row['models']))->all(), $limit);
            $this->printList('Customer/app/agent endpoints without branch filters (review)', collect($report['controllers']['external'])
                ->map(fn ($row) => $row['controller'] . ' => ' . implode(', ', $row['models']))->all(), $limit);
        }

        if (isset($report['raw'])) {
            $this->printList('Confirmed leak — raw query in a branch-unaware file', collect($report['raw']['unfiltered'])
                ->map(fn ($hit) => sprintf('%s:%d [%s] %s', $hit['file'], $hit['line'], $hit['table'], Str::limit($hit['snippet'], 90)))
                ->all(), $limit);
            $this->printList('Manual review required — the file filters by branch elsewhere', collect($report['raw']['review'])
                ->map(fn ($hit) => sprintf('%s:%d [%s]', $hit['file'], $hit['line'], $hit['table']))
                ->all(), $limit);
        }

        if (isset($report['data'])) {
            $this->printList('Data gaps (branch_id = NULL)', collect($report['data']['gaps'])
                ->map(fn ($row) => sprintf('%s — %s of %s (%s%%)', $row['table'], number_format($row['null_branch']), number_format($row['rows']), $row['percent']))
                ->all(), $limit);
        }
    }

    private function printList(string $title, array $items, int $limit): void
    {
        $this->newLine();
        $this->warn($title . ' (' . count($items) . ')');

        $shown = $limit > 0 ? array_slice($items, 0, $limit) : $items;

        foreach ($shown as $item) {
            $this->line('  - ' . $item);
        }

        if ($limit > 0 && count($items) > $limit) {
            $this->line('  ... and ' . (count($items) - $limit) . ' more (--limit=0 for all)');
        }
    }

    private function flag(int $count, string $label = 'Action required'): string
    {
        return $count === 0 ? 'OK' : $label;
    }

    private function exitCode(array $report): int
    {
        // الجداول التي لم تُعمَّم عليها الفروع بعد ليست تسريباً — هي اتساع تغطية
        // مؤجّل (جداول HR وCRM فارغة عملياً). لو دخلت في exit code لبقي الحارس
        // أحمر دائماً وفقد قدرته على كشف الجديد.
        $problems = ($report['models']['unstamped_count'] ?? 0)
            + ($report['models']['orphan_trait_count'] ?? 0)
            + ($report['controllers']['back_office_count'] ?? 0)
            + ($report['raw']['unfiltered_count'] ?? 0);

        return $problems > 0 ? 1 : 0;
    }
}
