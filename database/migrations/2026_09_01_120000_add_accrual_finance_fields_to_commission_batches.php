<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commission_batches', function (Blueprint $table) {
            $table->unsignedBigInteger('accrual_constraint_id')->nullable()->after('total_commission');
            $table->unsignedBigInteger('expense_account_id')->nullable()->after('accrual_constraint_id');
            $table->unsignedBigInteger('payable_account_id')->nullable()->after('expense_account_id');
            $table->unsignedBigInteger('cost_center_id')->nullable()->after('payable_account_id');

            $table->foreign('accrual_constraint_id', 'commission_batches_accrual_constraint_fk')
                ->references('id')->on('fn_constraints')->nullOnDelete();
            $table->foreign('expense_account_id', 'commission_batches_expense_account_fk')
                ->references('id')->on('fn_accounts')->restrictOnDelete();
            $table->foreign('payable_account_id', 'commission_batches_payable_account_fk')
                ->references('id')->on('fn_accounts')->restrictOnDelete();
            $table->foreign('cost_center_id', 'commission_batches_cost_center_fk')
                ->references('id')->on('fn_cost_centers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('commission_batches', function (Blueprint $table) {
            $table->dropForeign('commission_batches_accrual_constraint_fk');
            $table->dropForeign('commission_batches_expense_account_fk');
            $table->dropForeign('commission_batches_payable_account_fk');
            $table->dropForeign('commission_batches_cost_center_fk');
            $table->dropColumn([
                'accrual_constraint_id',
                'expense_account_id',
                'payable_account_id',
                'cost_center_id',
            ]);
        });
    }
};
