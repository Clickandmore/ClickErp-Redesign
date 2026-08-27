<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ingaz_delegation_stages')->updateOrInsert(
            ['key' => 'cancelled'],
            [
                'name_ar' => 'ملغي',
                'name_en' => 'Cancelled',
                'sort_order' => 110,
                'is_active' => true,
                'is_system' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        if (!Schema::hasColumn('ingaz_delegations', 'active_ingaz_number_key')) {
            Schema::table('ingaz_delegations', function (Blueprint $table) {
                $table->string('active_ingaz_number_key')->nullable()->after('ingaz_number');
                $table->string('active_msaned_contract_number_key')->nullable()->after('msaned_contract_number');
                $table->string('active_visa_number_key')->nullable()->after('visa_number');
            });
        }
        foreach ([
            'ingaz_delegations_active_ingaz_unique' => 'active_ingaz_number_key',
            'ingaz_delegations_active_msaned_unique' => 'active_msaned_contract_number_key',
            'ingaz_delegations_active_visa_unique' => 'active_visa_number_key',
        ] as $index => $column) {
            if (!$this->indexExists('ingaz_delegations', $index)) {
                Schema::table('ingaz_delegations', fn (Blueprint $table) => $table->unique($column, $index));
            }
        }

        if (!Schema::hasColumn('commission_batch_items', 'commission_reversal_transaction_id')) {
            Schema::table('commission_batch_items', function (Blueprint $table) {
                $table->unsignedBigInteger('commission_reversal_transaction_id')->nullable()->after('commission_amount');
                $table->timestamp('commission_reversed_at')->nullable()->after('commission_reversal_transaction_id');
                $table->string('commission_reversal_reason', 1000)->nullable()->after('commission_reversed_at');
            });
        }
        if (!$this->indexExists('commission_batch_items', 'commission_items_reversal_transaction_unique')) {
            Schema::table('commission_batch_items', function (Blueprint $table) {
                $table->unique('commission_reversal_transaction_id', 'commission_items_reversal_transaction_unique');
            });
        }
        if (!$this->foreignKeyExists('commission_batch_items', 'commission_items_reversal_tx_fk')) {
            Schema::table('commission_batch_items', function (Blueprint $table) {
                $table->foreign('commission_reversal_transaction_id', 'commission_items_reversal_tx_fk')
                    ->references('id')
                    ->on('commission_balance_transactions')
                    ->nullOnDelete();
            });
        }

        if (!$this->indexExists('commission_batch_items', 'commission_items_source_unique')) {
            Schema::table('commission_batch_items', function (Blueprint $table) {
                $table->unique(['source_type', 'source_id'], 'commission_items_source_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('commission_batch_items', function (Blueprint $table) {
            $table->dropForeign('commission_items_reversal_tx_fk');
            $table->dropUnique('commission_items_reversal_transaction_unique');
            $table->dropColumn([
                'commission_reversal_transaction_id',
                'commission_reversed_at',
                'commission_reversal_reason',
            ]);
        });

        Schema::table('ingaz_delegations', function (Blueprint $table) {
            $table->dropUnique('ingaz_delegations_active_ingaz_unique');
            $table->dropUnique('ingaz_delegations_active_msaned_unique');
            $table->dropUnique('ingaz_delegations_active_visa_unique');
            $table->dropColumn([
                'active_ingaz_number_key',
                'active_msaned_contract_number_key',
                'active_visa_number_key',
            ]);
        });

        DB::table('ingaz_delegation_stages')->where('key', 'cancelled')->delete();
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
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
