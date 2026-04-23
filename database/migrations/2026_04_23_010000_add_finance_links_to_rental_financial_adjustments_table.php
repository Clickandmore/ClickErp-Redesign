<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rental_financial_adjustments')) {
            return;
        }

        $hasAccountId = Schema::hasColumn('rental_financial_adjustments', 'account_id');
        $hasConstraintId = Schema::hasColumn('rental_financial_adjustments', 'constraint_id');
        $hasCostCenterId = Schema::hasColumn('rental_financial_adjustments', 'cost_center_id');

        Schema::table('rental_financial_adjustments', function (Blueprint $table) use ($hasAccountId, $hasConstraintId, $hasCostCenterId) {
            if (!$hasAccountId) {
                $table->foreignId('account_id')
                    ->nullable()
                    ->after('maid_id')
                    ->constrained('fn_accounts')
                    ->nullOnDelete();
            }

            if (!$hasConstraintId) {
                $table->foreignId('constraint_id')
                    ->nullable()
                    ->after('account_id')
                    ->constrained('fn_constraints')
                    ->nullOnDelete();
            }

            if (!$hasCostCenterId) {
                $table->foreignId('cost_center_id')
                    ->nullable()
                    ->after('constraint_id')
                    ->constrained('fn_cost_centers')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('rental_financial_adjustments')) {
            return;
        }

        $hasAccountId = Schema::hasColumn('rental_financial_adjustments', 'account_id');
        $hasConstraintId = Schema::hasColumn('rental_financial_adjustments', 'constraint_id');
        $hasCostCenterId = Schema::hasColumn('rental_financial_adjustments', 'cost_center_id');

        Schema::table('rental_financial_adjustments', function (Blueprint $table) use ($hasAccountId, $hasConstraintId, $hasCostCenterId) {
            if ($hasCostCenterId) {
                $table->dropForeign(['cost_center_id']);
                $table->dropColumn('cost_center_id');
            }

            if ($hasConstraintId) {
                $table->dropForeign(['constraint_id']);
                $table->dropColumn('constraint_id');
            }

            if ($hasAccountId) {
                $table->dropForeign(['account_id']);
                $table->dropColumn('account_id');
            }
        });
    }
};
