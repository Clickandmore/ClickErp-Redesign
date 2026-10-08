<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('commission_batch_items', 'commission_reversal_constraint_id')) {
            Schema::table('commission_batch_items', function (Blueprint $table) {
                $table->unsignedBigInteger('commission_reversal_constraint_id')
                    ->nullable()
                    ->after('commission_reversal_transaction_id');
                $table->unique(
                    'commission_reversal_constraint_id',
                    'commission_items_reversal_constraint_unique'
                );
                $table->foreign(
                    'commission_reversal_constraint_id',
                    'commission_items_reversal_constraint_fk'
                )->references('id')->on('fn_constraints')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Financial audit links are intentionally retained by this repair migration.
    }
};
