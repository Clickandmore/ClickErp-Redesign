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

        DB::table('fn_fiscal_year_closures')
            ->where('source', 'legacy')
            ->where('operation_number', 1)
            ->update([
                'reversed_at' => null,
                'reversed_by_id' => null,
                'reversed_by_name' => null,
                'updated_at' => '2022-09-28',
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('fn_fiscal_year_closures')) {
            return;
        }

        $adminId = DB::table('admins')->where('id', 1)->value('id');

        DB::table('fn_fiscal_year_closures')
            ->where('source', 'legacy')
            ->where('operation_number', 1)
            ->update([
                'reversed_at' => '2022-10-02',
                'reversed_by_id' => $adminId,
                'reversed_by_name' => 'System Admin',
                'updated_at' => '2022-10-02',
            ]);
    }
};
