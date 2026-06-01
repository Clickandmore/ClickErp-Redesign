<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('fn_constraint_details')
            || !Schema::hasColumn('fn_constraint_details', 'cost_center_id')
        ) {
            return;
        }

        $constraintIds = collect();

        if (
            Schema::hasTable('invoices')
            && Schema::hasColumn('invoices', 'external_office_cost_constraint_id')
        ) {
            $constraintIds = $constraintIds->merge(
                DB::table('invoices')
                    ->whereNotNull('external_office_cost_constraint_id')
                    ->pluck('external_office_cost_constraint_id')
            );
        }

        if (
            Schema::hasTable('fn_constraints')
            && Schema::hasColumn('fn_constraints', 'statement')
        ) {
            $constraintIds = $constraintIds->merge(
                DB::table('fn_constraints')
                    ->where('statement', 'like', 'إغلاق طلب:%')
                    ->pluck('id')
            );
        }

        $constraintIds
            ->filter()
            ->unique()
            ->chunk(500)
            ->each(function ($ids): void {
                DB::table('fn_constraint_details')
                    ->whereIn('constraint_id', $ids->values()->all())
                    ->update(['cost_center_id' => null]);
            });
    }

    public function down(): void
    {
        // Irreversible data correction.
    }
};
