<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * إسناد السجلات القديمة التي branch_id فيها NULL إلى فرعها الصحيح.
 *
 * مهم قبل تفعيل العزل التلقائي: السجل بلا فرع يبقى مرئياً للفرع الرئيسي وحده،
 * فأي سجل يخص فرعاً آخر ويبقى NULL يختفي عن موظفي ذلك الفرع.
 *
 * الترتيب مقصود — كل جدول يرث من جدول أُسند قبله.
 */
class BranchesBackfillCommand extends Command
{
    /** NULL مقصود في هذه الجداول: إدارة عامة أو بيانات مرجعية مشتركة. */
    private const FALLBACK_MAIN_EXCLUSIONS = [
        'admins',
        'maids',
        'fn_accounts',
        'fn_cost_centers',
        'fn_clients',
        'fn_suppliers',
        'fn_boxes',
    ];

    protected $signature = 'branches:backfill
        {--dry-run : Show counts without applying updates}
        {--table= : Limit processing to one table}
        {--fallback-main : Assign unresolved rows to the main branch instead of leaving them NULL}
        {--passes=2 : Number of passes; dependent rules may require another pass}';

    protected $description = 'Backfill branch_id on existing rows from linked employees, orders, or contracts.';

    /**
     * كل قاعدة: الجدول الهدف، الجدول المصدر، عمود الربط، وعمود الفرع في المصدر.
     *
     * @var array<int, array{table: string, from: string, key: string, source_key?: string, source_branch?: string}>
     */
    private array $rules = [
        // 1) الموظفون والعملاء أولاً — كل ما بعدهم يرث منهم.
        ['table' => 'orders', 'from' => 'admins', 'key' => 'customer_id'],
        ['table' => 'users', 'from' => 'admins', 'key' => 'created_by'],

        // 2) العقود من الطلب ثم من المسوّق.
        ['table' => 'contracts', 'from' => 'orders', 'key' => 'order_id'],
        ['table' => 'contracts', 'from' => 'admins', 'key' => 'marketer_id'],
        ['table' => 'rental_contracts', 'from' => 'orders', 'key' => 'order_id'],

        // 3) الفواتير والمستندات التابعة للعقود.
        ['table' => 'invoices', 'from' => 'contracts', 'key' => 'contract_id'],
        ['table' => 'contract_notes', 'from' => 'contracts', 'key' => 'contract_id'],
        ['table' => 'contract_status_logs', 'from' => 'contracts', 'key' => 'contract_id'],
        ['table' => 'contract_payments', 'from' => 'contracts', 'key' => 'contract_id'],
        ['table' => 'contract_external_office_dispatches', 'from' => 'contracts', 'key' => 'contract_id'],
        ['table' => 'contract_return_requests', 'from' => 'contracts', 'key' => 'contract_id'],
        ['table' => 'recruitment_contract_attachments', 'from' => 'contracts', 'key' => 'contract_id'],
        ['table' => 'recruitment_contract_refunds', 'from' => 'contracts', 'key' => 'contract_id'],
        ['table' => 'maid_recruitment_procedures', 'from' => 'contracts', 'key' => 'contract_id'],
        ['table' => 'sponsorship_transfers', 'from' => 'contracts', 'key' => 'contract_id'],
        ['table' => 'transfer_service_contracts', 'from' => 'contracts', 'key' => 'contract_id'],

        // 4) التأجير.
        ['table' => 'rental_contract_invoices', 'from' => 'rental_contracts', 'key' => 'rental_contract_id'],
        ['table' => 'rental_contract_extensions', 'from' => 'rental_contracts', 'key' => 'rental_contract_id'],
        ['table' => 'rental_financial_adjustments', 'from' => 'rental_contracts', 'key' => 'rental_contract_id'],

        // 5) الشكاوى وتوابعها.
        ['table' => 'complaints', 'from' => 'users', 'key' => 'user_id'],
        ['table' => 'complaint_comments', 'from' => 'complaints', 'key' => 'complaint_id'],
        ['table' => 'complaint_timelines', 'from' => 'complaints', 'key' => 'complaint_id'],
        ['table' => 'complaint_feedbacks', 'from' => 'complaints', 'key' => 'complaint_id'],

        // 6) بقية العمليات.
        ['table' => 'order_transfers', 'from' => 'orders', 'key' => 'order_id'],
        ['table' => 'ingaz_delegations', 'from' => 'contracts', 'key' => 'contract_id'],
        ['table' => 'musaned_transfers', 'from' => 'contracts', 'key' => 'contract_id'],
        ['table' => 'musaned_transfer_items', 'from' => 'musaned_transfers', 'key' => 'musaned_transfer_id'],
        ['table' => 'financial_requests', 'from' => 'admins', 'key' => 'admin_id'],
        ['table' => 'financial_request_attachments', 'from' => 'financial_requests', 'key' => 'financial_request_id'],
        ['table' => 'client_attachments', 'from' => 'users', 'key' => 'user_id'],
        ['table' => 'maid_attachments', 'from' => 'maids', 'key' => 'maid_id'],

        // 7) السير الذاتية من آخر عقد ارتبطت به.
        ['table' => 'maids', 'from' => 'contracts', 'key' => 'id', 'source_key' => 'maid_id'],

        // 8) ما لا يصله طلب: العقد من عميله، وعقد التأجير من مسوّقه ثم عميله.
        ['table' => 'contracts', 'from' => 'users', 'key' => 'user_id'],
        ['table' => 'rental_contracts', 'from' => 'admins', 'key' => 'marketer_id'],
        ['table' => 'rental_contracts', 'from' => 'users', 'key' => 'user_id'],

        // 9) إعادة تمرير على توابع العقود بعد أن أُسندت العقود أعلاه.
        ['table' => 'invoices', 'from' => 'contracts', 'key' => 'contract_id'],
        ['table' => 'contract_status_logs', 'from' => 'contracts', 'key' => 'contract_id'],
        ['table' => 'ingaz_delegations', 'from' => 'contracts', 'key' => 'contract_id'],
        ['table' => 'rental_contract_invoices', 'from' => 'rental_contracts', 'key' => 'rental_contract_id'],
        ['table' => 'rental_contract_extensions', 'from' => 'rental_contracts', 'key' => 'rental_contract_id'],
        ['table' => 'rental_financial_adjustments', 'from' => 'rental_contracts', 'key' => 'rental_contract_id'],

        // 10) توابع المالية ترث من مستندها الأب.
        ['table' => 'fn_constraint_details', 'from' => 'fn_constraints', 'key' => 'constraint_id'],
        ['table' => 'fn_constraint_logs', 'from' => 'fn_constraints', 'key' => 'constraint_id'],
        ['table' => 'fn_constraint_files', 'from' => 'fn_constraints', 'key' => 'constraint_id'],
        ['table' => 'fn_fiscal_year_closures', 'from' => 'fn_constraints', 'key' => 'constraint_id'],
        ['table' => 'fn_bond_checks', 'from' => 'fn_bonds', 'key' => 'bond_id'],
        ['table' => 'fn_bonds_taxs', 'from' => 'fn_bonds', 'key' => 'bond_id'],
        ['table' => 'fn_sale_items', 'from' => 'fn_sales', 'key' => 'sale_id'],
        ['table' => 'fn_purchase_items', 'from' => 'fn_purchases', 'key' => 'purchase_id'],
        ['table' => 'fn_client_attachments', 'from' => 'fn_clients', 'key' => 'client_id'],
        ['table' => 'musaned_transfers', 'from' => 'admins', 'key' => 'created_by_id'],
        ['table' => 'musaned_transfer_items', 'from' => 'musaned_transfers', 'key' => 'musaned_transfer_id'],

        // 11) العمولات تتبع فرع المسوّق.
        ['table' => 'commission_batches', 'from' => 'admins', 'key' => 'marketer_id'],
        ['table' => 'commission_batch_items', 'from' => 'commission_batches', 'key' => 'batch_id'],
        ['table' => 'commission_payments', 'from' => 'admins', 'key' => 'marketer_id'],
        ['table' => 'commission_balance_transactions', 'from' => 'admins', 'key' => 'marketer_id'],
        ['table' => 'commission_marketer_overrides', 'from' => 'admins', 'key' => 'admin_id'],

        // 12) الموارد البشرية: الموظف هو المصدر، وكل حركة ترث فرعه.
        ['table' => 'hr_employee', 'from' => 'admins', 'key' => 'admin_id'],
        ['table' => 'hr_employee', 'from' => 'maids', 'key' => 'maid_id'],
        ['table' => 'admins_employees', 'from' => 'hr_employee', 'key' => 'employee_id'],
        ['table' => 'hr_employee_shift', 'from' => 'hr_employee', 'key' => 'employee_id'],
        ['table' => 'hr_employee_files', 'from' => 'hr_employee', 'key' => 'employee_id'],
        ['table' => 'hr_advances', 'from' => 'hr_employee', 'key' => 'employee_id'],
        ['table' => 'hr_complaints', 'from' => 'hr_employee', 'key' => 'employee_id'],
        ['table' => 'hr_daily_work_reports', 'from' => 'hr_employee', 'key' => 'employee_id'],
        ['table' => 'hr_employee_allowances', 'from' => 'hr_employee', 'key' => 'employee_id'],
        ['table' => 'hr_employee_attendances', 'from' => 'hr_employee', 'key' => 'employee_id'],
        ['table' => 'hr_employee_custodies', 'from' => 'hr_employee', 'key' => 'employee_id'],
        ['table' => 'hr_employee_delays', 'from' => 'hr_employee', 'key' => 'employee_id'],
        ['table' => 'hr_employee_insurances', 'from' => 'hr_employee', 'key' => 'employee_id'],
        ['table' => 'hr_employee_permissions', 'from' => 'hr_employee', 'key' => 'employee_id'],
        ['table' => 'hr_employee_responsible', 'from' => 'hr_employee', 'key' => 'employee_id'],
        ['table' => 'hr_employee_salary', 'from' => 'hr_employee', 'key' => 'employee_id'],
        ['table' => 'hr_employee_sanctions', 'from' => 'hr_employee', 'key' => 'employee_id'],
        ['table' => 'hr_employee_tax', 'from' => 'hr_employee', 'key' => 'employee_id'],
        ['table' => 'hr_employee_vacation_balance', 'from' => 'hr_employee', 'key' => 'employee_id'],
        ['table' => 'hr_employee_vacations', 'from' => 'hr_employee', 'key' => 'employee_id'],
        ['table' => 'hr_reward_employee', 'from' => 'hr_employee', 'key' => 'employee_id'],

        // رؤوس التشغيل أولاً، ثم التفاصيل؛ لا نخمّن فرع رأس قديم متعدد الفروع.
        ['table' => 'hr_payroll_items', 'from' => 'hr_payrolls', 'key' => 'payroll_id'],
        ['table' => 'hr_employee_tasks', 'from' => 'hr_tasks', 'key' => 'task_id'],
        ['table' => 'hr_task_replies', 'from' => 'hr_tasks', 'key' => 'task_id'],
        ['table' => 'hr_complaint_replies', 'from' => 'hr_complaints', 'key' => 'complaint_id'],
    ];

    public function handle(): int
    {
        $only = $this->option('table');
        $dryRun = (bool) $this->option('dry-run');
        $fallbackMain = (bool) $this->option('fallback-main');
        $passes = $dryRun ? 1 : max(1, (int) $this->option('passes'));
        $mainBranchId = $fallbackMain ? $this->mainBranchId() : null;

        if ($fallbackMain && ! $mainBranchId) {
            $this->error('Unable to determine the main branch. No updates were applied.');

            return 1;
        }

        for ($pass = 1; $pass <= $passes; $pass++) {
            $rows = $this->runPass($only, $dryRun, $mainBranchId);

            if ($rows === []) {
                if ($pass === 1) {
                    $this->info('No rows without a branch were found.');
                }

                break;
            }

            if ($passes > 1) {
                $this->newLine();
                $this->info("Pass {$pass} of {$passes}");
            }

            $this->renderRows($rows, $dryRun);
        }

        if ($dryRun) {
            $this->warn('Dry run — no updates were applied.');
        }

        return 0;
    }

    /** @return array<int, array<int, string>> */
    private function runPass(?string $only, bool $dryRun, ?int $mainBranchId): array
    {
        $rows = [];

        foreach ($this->rules as $rule) {
            if ($only && $rule['table'] !== $only) {
                continue;
            }

            if (! $this->rulePossible($rule)) {
                continue;
            }

            $before = DB::table($rule['table'])->whereNull('branch_id')->count();

            if ($before === 0) {
                continue;
            }

            if ($dryRun) {
                $resolvable = $this->resolvableCount($rule);

                $rows[] = [
                    $rule['table'],
                    $rule['from'] . '.' . ($rule['source_key'] ?? 'id'),
                    number_format($before),
                    number_format($resolvable),
                    number_format($before - $resolvable),
                ];

                continue;
            }

            DB::statement($this->updateStatement($rule));

            $after = DB::table($rule['table'])->whereNull('branch_id')->count();

            $rows[] = [
                $rule['table'],
                $rule['from'] . '.' . ($rule['source_key'] ?? 'id'),
                number_format($before),
                number_format($before - $after),
                number_format($after),
            ];
        }

        if ($mainBranchId) {
            $rows = array_merge($rows, $this->fallbackToMainBranch($only, $dryRun, $mainBranchId));
        }

        return $rows;
    }

    /**
     * السجلات القديمة التي لا يملك أي أبٍ لها branch_id كانت تُعامل فعلياً
     * كسجلات الفرع الرئيسي. هذا الخيار يثبت تلك الدلالة في العمود نفسه.
     */
    private function fallbackToMainBranch(?string $only, bool $dryRun, int $mainBranchId): array
    {
        $rows = [];
        $tables = $this->branchTables()
            ->reject(fn (string $table) => in_array($table, self::FALLBACK_MAIN_EXCLUSIONS, true))
            ->when($only, fn ($items) => $items->filter(fn (string $table) => $table === $only));

        foreach ($tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'branch_id')) {
                continue;
            }

            $before = DB::table($table)->whereNull('branch_id')->count();
            if ($before === 0) {
                continue;
            }

            if (! $dryRun) {
                DB::table($table)->whereNull('branch_id')->update(['branch_id' => $mainBranchId]);
            }

            $rows[] = [
                $table,
                'fallback:main_branch',
                number_format($before),
                number_format($before),
                '0',
            ];
        }

        return $rows;
    }

    /** كل جدول يحمل branch_id، بما فيه الجداول التي أضيفت بعد كتابة قواعد الاشتقاق. */
    private function branchTables(): \Illuminate\Support\Collection
    {
        $database = DB::connection()->getDatabaseName();

        return collect(DB::select(
            'SELECT TABLE_NAME AS table_name
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND COLUMN_NAME = ?
             ORDER BY TABLE_NAME',
            [$database, 'branch_id']
        ))->pluck('table_name')->filter()->values();
    }

    private function mainBranchId(): ?int
    {
        if (! Schema::hasTable('branches')) {
            return null;
        }

        $branchId = DB::table('branches')->where('is_main', true)->value('id')
            ?: DB::table('branches')->where('is_active', true)->orderBy('sort_order')->orderBy('id')->value('id');

        return $branchId ? (int) $branchId : null;
    }

    private function renderRows(array $rows, bool $dryRun): void
    {
        $header = $dryRun
            ? ['Table', 'Source', 'Without branch', 'Will assign', 'Will remain']
            : ['Table', 'Source', 'Before', 'Assigned', 'Remaining'];

        $this->table($header, $rows);
    }

    private function rulePossible(array $rule): bool
    {
        $sourceKey = $rule['source_key'] ?? 'id';

        return Schema::hasTable($rule['table'])
            && Schema::hasTable($rule['from'])
            && Schema::hasColumn($rule['table'], 'branch_id')
            && Schema::hasColumn($rule['table'], $rule['key'])
            && Schema::hasColumn($rule['from'], 'branch_id')
            && Schema::hasColumn($rule['from'], $sourceKey);
    }

    /** كم صفاً سيجد فرعاً فعلاً لو نُفِّذت القاعدة الآن. */
    private function resolvableCount(array $rule): int
    {
        $sourceKey = $rule['source_key'] ?? 'id';

        return (int) DB::table($rule['table'])
            ->join($rule['from'], $rule['from'] . '.' . $sourceKey, '=', $rule['table'] . '.' . $rule['key'])
            ->whereNull($rule['table'] . '.branch_id')
            ->whereNotNull($rule['from'] . '.branch_id')
            ->distinct()
            ->count($rule['table'] . '.id');
    }

    private function updateStatement(array $rule): string
    {
        $table = $rule['table'];
        $from = $rule['from'];
        $key = $rule['key'];
        $sourceKey = $rule['source_key'] ?? 'id';

        return "
            UPDATE {$table}
            INNER JOIN {$from} ON {$from}.{$sourceKey} = {$table}.{$key}
            SET {$table}.branch_id = {$from}.branch_id
            WHERE {$table}.branch_id IS NULL
              AND {$from}.branch_id IS NOT NULL
        ";
    }
}
