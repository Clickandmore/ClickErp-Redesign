<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shelter_living_expenses')) {
            Schema::create('shelter_living_expenses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shelter_id')->nullable();
                $table->unsignedBigInteger('expense_account_id');
                $table->unsignedBigInteger('payment_account_id');
                $table->unsignedBigInteger('constraint_id')->nullable();
                $table->date('expense_date');
                $table->string('title')->default('إعاشة الإيواء');
                $table->decimal('total_amount', 12, 2);
                $table->unsignedInteger('worker_count')->default(0);
                $table->decimal('amount_per_worker', 12, 2)->default(0);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('shelter_id', 'sl_exp_shelter_fk')->references('id')->on('shelter_places')->nullOnDelete();
                $table->foreign('expense_account_id', 'sl_exp_exp_acc_fk')->references('id')->on('fn_accounts')->cascadeOnDelete();
                $table->foreign('payment_account_id', 'sl_exp_pay_acc_fk')->references('id')->on('fn_accounts')->cascadeOnDelete();
                $table->foreign('constraint_id', 'sl_exp_constraint_fk')->references('id')->on('fn_constraints')->nullOnDelete();
                $table->foreign('created_by', 'sl_exp_admin_fk')->references('id')->on('admins')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('shelter_living_expense_allocations')) {
            Schema::create('shelter_living_expense_allocations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shelter_living_expense_id');
                $table->unsignedBigInteger('contract_id');
                $table->unsignedBigInteger('maid_id')->nullable();
                $table->unsignedBigInteger('cost_center_id')->nullable();
                $table->decimal('amount', 12, 2);
                $table->timestamps();

                $table->foreign('shelter_living_expense_id', 'sl_alloc_exp_fk')->references('id')->on('shelter_living_expenses')->cascadeOnDelete();
                $table->foreign('contract_id', 'sl_alloc_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
                $table->foreign('maid_id', 'sl_alloc_maid_fk')->references('id')->on('maids')->nullOnDelete();
                $table->foreign('cost_center_id', 'sl_alloc_cc_fk')->references('id')->on('fn_cost_centers')->nullOnDelete();
                $table->unique(['shelter_living_expense_id', 'contract_id'], 'shelter_living_alloc_unique');
            });
        } else {
            Schema::table('shelter_living_expense_allocations', function (Blueprint $table) {
                $table->foreign('shelter_living_expense_id', 'sl_alloc_exp_fk')->references('id')->on('shelter_living_expenses')->cascadeOnDelete();
                $table->foreign('contract_id', 'sl_alloc_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
                $table->foreign('maid_id', 'sl_alloc_maid_fk')->references('id')->on('maids')->nullOnDelete();
                $table->foreign('cost_center_id', 'sl_alloc_cc_fk')->references('id')->on('fn_cost_centers')->nullOnDelete();
                $table->unique(['shelter_living_expense_id', 'contract_id'], 'shelter_living_alloc_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shelter_living_expense_allocations');
        Schema::dropIfExists('shelter_living_expenses');
    }
};
