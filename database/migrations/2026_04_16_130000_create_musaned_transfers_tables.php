<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('musaned_transfers')) {
            Schema::create('musaned_transfers', function (Blueprint $table) {
                $table->id();
                $table->string('reference_number')->nullable()->comment('مرجع الحوالة من مساند');
                $table->date('transfer_date')->comment('تاريخ حوالة مساند');
                $table->text('notes')->nullable();
                $table->unsignedInteger('contracts_count')->default(0);
                $table->decimal('customer_paid_total', 12, 2)->default(0);
                $table->decimal('transferred_total', 12, 2)->default(0);
                $table->decimal('commission_total', 12, 2)->default(0);
                $table->decimal('expense_tax_total', 12, 2)->default(0);
                $table->decimal('commission_net_total', 12, 2)->default(0);
                $table->decimal('expense_tax_rate', 8, 4)->default(15);
                $table->unsignedBigInteger('bank_account_id')->nullable();
                $table->unsignedBigInteger('intermediate_account_id')->nullable();
                $table->unsignedBigInteger('commission_account_id')->nullable();
                $table->unsignedBigInteger('expense_tax_account_id')->nullable();
                $table->unsignedBigInteger('settlement_constraint_id')->nullable();
                $table->unsignedBigInteger('bank_transfer_constraint_id')->nullable();
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->timestamps();

                $table->foreign('bank_account_id')->references('id')->on('fn_accounts')->nullOnDelete();
                $table->foreign('intermediate_account_id')->references('id')->on('fn_accounts')->nullOnDelete();
                $table->foreign('commission_account_id')->references('id')->on('fn_accounts')->nullOnDelete();
                $table->foreign('expense_tax_account_id')->references('id')->on('fn_accounts')->nullOnDelete();
                $table->foreign('settlement_constraint_id')->references('id')->on('fn_constraints')->nullOnDelete();
                $table->foreign('bank_transfer_constraint_id')->references('id')->on('fn_constraints')->nullOnDelete();
                $table->foreign('created_by_id')->references('id')->on('admins')->nullOnDelete();

                $table->index('transfer_date');
                $table->index('reference_number');
            });
        }

        if (!Schema::hasTable('musaned_transfer_items')) {
            Schema::create('musaned_transfer_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('musaned_transfer_id');
                $table->unsignedBigInteger('contract_id');
                $table->unsignedBigInteger('invoice_id');
                $table->unsignedBigInteger('cost_center_id')->nullable();
                $table->unsignedBigInteger('source_account_id')->nullable();
                $table->decimal('customer_paid_amount', 12, 2)->default(0);
                $table->decimal('transferred_amount', 12, 2)->default(0);
                $table->decimal('commission_amount', 12, 2)->default(0);
                $table->decimal('expense_tax_amount', 12, 2)->default(0);
                $table->decimal('commission_net_amount', 12, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('musaned_transfer_id')->references('id')->on('musaned_transfers')->cascadeOnDelete();
                $table->foreign('contract_id')->references('id')->on('contracts')->cascadeOnDelete();
                $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
                $table->foreign('cost_center_id')->references('id')->on('fn_cost_centers')->nullOnDelete();
                $table->foreign('source_account_id')->references('id')->on('fn_accounts')->nullOnDelete();

                $table->unique('invoice_id');
                $table->index('contract_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('musaned_transfer_items');
        Schema::dropIfExists('musaned_transfers');
    }
};
