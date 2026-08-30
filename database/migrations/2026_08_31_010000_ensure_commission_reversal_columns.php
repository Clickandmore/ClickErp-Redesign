<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('commission_batch_items', 'commission_reversal_transaction_id')) {
            Schema::table('commission_batch_items', function (Blueprint $table) {
                $table->unsignedBigInteger('commission_reversal_transaction_id')->nullable()->after('commission_amount');
                $table->timestamp('commission_reversed_at')->nullable()->after('commission_reversal_transaction_id');
                $table->string('commission_reversal_reason', 1000)->nullable()->after('commission_reversed_at');
            });
        }

        if (! $this->indexExists('commission_batch_items', 'commission_items_reversal_transaction_unique')) {
            Schema::table('commission_batch_items', function (Blueprint $table) {
                $table->unique('commission_reversal_transaction_id', 'commission_items_reversal_transaction_unique');
            });
        }

        if (! $this->foreignKeyExists('commission_batch_items', 'commission_items_reversal_tx_fk')) {
            Schema::table('commission_batch_items', function (Blueprint $table) {
                $table->foreign('commission_reversal_transaction_id', 'commission_items_reversal_tx_fk')
                    ->references('id')
                    ->on('commission_balance_transactions')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Repair migration: intentionally non-destructive.
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        return DB::table('information_schema.table_constraints')
            ->where('constraint_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('constraint_name', $constraint)
            ->exists();
    }
};
