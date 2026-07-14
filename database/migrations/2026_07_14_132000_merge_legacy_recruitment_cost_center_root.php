<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fn_cost_centers') || !Schema::hasColumn('fn_cost_centers', 'legacy_source_code')) {
            return;
        }

        $legacy = DB::table('fn_cost_centers')->where('legacy_source_code', '2')->first();
        $canonical = DB::table('fn_cost_centers')
            ->whereNull('parent_id')
            ->where('title', 'عقود الاستقدام')
            ->first();

        if (!$legacy) {
            return;
        }

        if (!$canonical || (int) $canonical->id === (int) $legacy->id) {
            DB::table('fn_cost_centers')->where('id', $legacy->id)->update(['title' => 'عقود الاستقدام']);

            return;
        }

        DB::transaction(function () use ($legacy, $canonical) {
            DB::table('fn_cost_centers')
                ->where('parent_id', $legacy->id)
                ->update(['parent_id' => $canonical->id, 'updated_at' => now()]);

            $database = config('database.connections.mysql.database');
            $references = DB::select(
                "SELECT table_name AS referenced_table FROM information_schema.columns WHERE table_schema = ? AND column_name = 'cost_center_id'",
                [$database]
            );

            foreach ($references as $reference) {
                DB::table($reference->referenced_table)
                    ->where('cost_center_id', $legacy->id)
                    ->update(['cost_center_id' => $canonical->id]);
            }

            DB::table('fn_cost_centers')->where('id', $canonical->id)->update([
                'title' => 'عقود الاستقدام',
                'legacy_source_code' => '2',
                'legacy_level' => $legacy->legacy_level,
                'legacy_full_path' => 'عقود الاستقدام',
                'legacy_debtor_total' => $legacy->legacy_debtor_total,
                'legacy_creditor_total' => $legacy->legacy_creditor_total,
                'legacy_balance' => $legacy->legacy_balance,
                'legacy_balance_type' => $legacy->legacy_balance_type,
                'legacy_opening_balance' => $legacy->legacy_opening_balance,
                'updated_at' => now(),
            ]);

            DB::table('fn_cost_centers')->where('id', $legacy->id)->delete();
        });
    }

    public function down(): void
    {
        // The two records represent the same business cost center. Recreating a
        // duplicate on rollback would make existing constraint links ambiguous.
    }
};
