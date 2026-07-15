<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fn_fiscal_year_closures')) {
            return;
        }

        $adminId = DB::table('admins')->where('id', 1)->value('id');
        $rows = [
            [
                'operation_number' => 1,
                'title' => 'إقفال مالي حتي 31/12/2020',
                'income_balance' => 2451978.97,
                'income_side' => 'creditor',
                'closing_date' => '2020-12-31',
                'created_by_name' => 'مصطفى',
                'created_at' => '2022-09-28',
            ],
            [
                'operation_number' => 2,
                'title' => 'إقفال مالي حتي 31-10-2020',
                'income_balance' => 2477586.65,
                'income_side' => 'creditor',
                'closing_date' => '2020-10-31',
                'created_by_name' => 'System Admin',
                'created_at' => '2022-10-02',
            ],
            [
                'operation_number' => 3,
                'title' => 'إقفال مالي حتي 31-12-2020',
                'income_balance' => 2451978.97,
                'income_side' => 'creditor',
                'closing_date' => '2020-12-31',
                'created_by_name' => 'System Admin',
                'created_at' => '2022-10-02',
            ],
            [
                'operation_number' => 4,
                'title' => 'إقفال مالي حتي 31-12-2020',
                'income_balance' => 2451978.97,
                'income_side' => 'creditor',
                'closing_date' => '2020-12-31',
                'created_by_name' => 'System Admin',
                'created_at' => '2022-10-02',
            ],
            [
                'operation_number' => 5,
                'title' => 'إقفال مالي حتي 31-12-2020',
                'income_balance' => 2451978.97,
                'income_side' => 'creditor',
                'closing_date' => '2020-12-31',
                'created_by_name' => 'System Admin',
                'created_at' => '2022-10-02',
            ],
            [
                'operation_number' => 6,
                'title' => 'إقفال مالي حتي 31-12-2020',
                'income_balance' => 2451978.97,
                'income_side' => 'creditor',
                'closing_date' => '2020-12-31',
                'created_by_name' => 'System Admin',
                'created_at' => '2022-10-02',
            ],
        ];

        foreach ($rows as $row) {
            $wasReversed = $row['operation_number'] !== 1;

            DB::table('fn_fiscal_year_closures')->insertOrIgnore([
                'operation_number' => $row['operation_number'],
                'title' => $row['title'],
                'income_balance' => $row['income_balance'],
                'income_side' => $row['income_side'],
                'closing_date' => $row['closing_date'],
                'constraint_id' => null,
                'legacy_constraint_code' => 0,
                'created_by_id' => $adminId,
                'created_by_name' => $row['created_by_name'],
                'reversed_at' => $wasReversed ? '2022-10-02' : null,
                'reversed_by_id' => $wasReversed ? $adminId : null,
                'reversed_by_name' => $wasReversed ? 'System Admin' : null,
                'source' => 'legacy',
                'created_at' => $row['created_at'],
                'updated_at' => $wasReversed ? '2022-10-02' : $row['created_at'],
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fn_fiscal_year_closures')) {
            DB::table('fn_fiscal_year_closures')
                ->where('source', 'legacy')
                ->whereBetween('operation_number', [1, 6])
                ->delete();
        }
    }
};
