<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Services\Finance\BranchChartOfAccountsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * تجهيز الجانب المالي لكل فرع: شجرة حسابات مستقلة + مركز تكلفة.
 *
 * يُشغَّل بعد كل فرع جديد، وبعد أي deploy يضيف مرساة حساب جديدة في
 * config/branch_finance.php. العملية idempotent فالتكرار آمن.
 */
class BranchesProvisionFinanceCommand extends Command
{
    protected $signature = 'branches:provision-finance
        {--branch= : تجهيز فرع واحد بالمعرف أو الكود}
        {--backfill : إسناد مستندات المالية القديمة بلا فرع إلى فرع منشئها}
        {--dry-run : عرض ما سيتم دون تنفيذ}';

    protected $description = 'تجهيز شجرة حسابات ومركز تكلفة مستقل لكل فرع، مع خيار إسناد المستندات المالية القديمة.';

    public function handle(BranchChartOfAccountsService $service): int
    {
        if (! $service->financeTablesAvailable()) {
            $this->error('جداول المالية أو أعمدة الفروع غير جاهزة — شغّل php artisan migrate أولاً.');

            return 1;
        }

        $branches = $this->targetBranches();

        if ($branches->isEmpty()) {
            $this->error('لا يوجد فرع مطابق.');

            return 1;
        }

        $rows = [];

        foreach ($branches as $branch) {
            if ($this->option('dry-run')) {
                $existing = $service->accountsFor($branch);
                $rows[] = [$branch->id, $branch->name_ar, count($existing) . ' حساب موجود', 'dry-run'];

                continue;
            }

            $result = $service->provision($branch);
            $rows[] = [
                $branch->id,
                $branch->name_ar,
                count($result['accounts']) . ' حساب (' . $result['created'] . ' جديد)',
                $result['cost_center_id'] ? 'مركز تكلفة #' . $result['cost_center_id'] : 'بلا مركز تكلفة',
            ];
        }

        $this->table(['#', 'الفرع', 'الحسابات', 'مركز التكلفة'], $rows);

        if ($this->option('backfill')) {
            $this->backfillFinanceDocuments();
        }

        return 0;
    }

    private function targetBranches()
    {
        $query = Branch::query()->ordered();

        if ($branch = $this->option('branch')) {
            $query->where(function ($inner) use ($branch) {
                $inner->where('id', (int) $branch)->orWhere('code', $branch);
            });
        }

        return $query->get();
    }

    /**
     * المستندات المالية القديمة بلا فرع تُسنَد إلى فرع الموظف الذي أنشأها.
     * ما لا يمكن تحديد فرعه يبقى NULL — أي يظل ضمن رؤية الفرع الرئيسي.
     */
    private function backfillFinanceDocuments(): void
    {
        $tables = [
            'fn_constraints' => 'created_by_id',
            'fn_bonds' => 'created_by_id',
            'fn_sales' => 'created_by_id',
            'fn_purchases' => 'created_by_id',
            'fn_clients' => 'created_by_id',
            'fn_suppliers' => 'created_by_id',
            'fn_boxes' => 'created_by_id',
        ];

        $this->newLine();
        $this->info('إسناد المستندات المالية القديمة حسب فرع منشئها');

        $rows = [];

        foreach ($tables as $table => $adminColumn) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'branch_id') || ! Schema::hasColumn($table, $adminColumn)) {
                continue;
            }

            $before = DB::table($table)->whereNull('branch_id')->count();

            if (! $this->option('dry-run')) {
                DB::statement("
                    UPDATE {$table}
                    INNER JOIN admins ON admins.id = {$table}.{$adminColumn}
                    SET {$table}.branch_id = admins.branch_id
                    WHERE {$table}.branch_id IS NULL
                      AND admins.branch_id IS NOT NULL
                ");
            }

            $after = DB::table($table)->whereNull('branch_id')->count();
            $rows[] = [$table, $before, $before - $after, $after];
        }

        // سطور القيود ترث فرع القيد الأب.
        if (Schema::hasTable('fn_constraint_details') && Schema::hasColumn('fn_constraint_details', 'branch_id')) {
            $before = DB::table('fn_constraint_details')->whereNull('branch_id')->count();

            if (! $this->option('dry-run')) {
                DB::statement('
                    UPDATE fn_constraint_details
                    INNER JOIN fn_constraints ON fn_constraints.id = fn_constraint_details.constraint_id
                    SET fn_constraint_details.branch_id = fn_constraints.branch_id
                    WHERE fn_constraint_details.branch_id IS NULL
                      AND fn_constraints.branch_id IS NOT NULL
                ');
            }

            $after = DB::table('fn_constraint_details')->whereNull('branch_id')->count();
            $rows[] = ['fn_constraint_details', $before, $before - $after, $after];
        }

        $this->table(['الجدول', 'بلا فرع قبل', 'تم إسناده', 'ما زال بلا فرع'], $rows);
    }
}
