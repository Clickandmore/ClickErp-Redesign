<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Restore identifiers that may lose AUTO_INCREMENT after a legacy import.
     */
    public function up(): void
    {
        $tables = [
            'invoices',
            'fn_constraints',
            'fn_constraint_details',
        ];

        $tablesToRepair = collect($tables)
            ->filter(fn (string $table) => Schema::hasTable($table))
            ->filter(function (string $table) {
                $column = DB::selectOne(
                    'SELECT EXTRA
                     FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = ?
                       AND COLUMN_NAME = ?',
                    [$table, 'id']
                );

                return $column
                    && stripos((string) ($column->EXTRA ?? ''), 'auto_increment') === false;
            })
            ->values();

        if ($tablesToRepair->isEmpty()) {
            return;
        }

        $foreignKeyChecks = (int) (DB::selectOne(
            'SELECT @@SESSION.FOREIGN_KEY_CHECKS AS enabled'
        )->enabled ?? 1);

        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        try {
            foreach ($tablesToRepair as $table) {
                DB::statement(
                    "ALTER TABLE `{$table}` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT"
                );
            }
        } finally {
            DB::statement("SET FOREIGN_KEY_CHECKS = {$foreignKeyChecks}");
        }
    }

    /**
     * This is a corrective migration; rolling it back must not recreate the fault.
     */
    public function down(): void
    {
        // Intentionally left blank.
    }
};
