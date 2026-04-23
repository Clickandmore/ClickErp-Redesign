<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rental_contract_invoices')) {
            return;
        }

        Schema::create('rental_contract_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rental_contract_id')->unique();
            $table->unsignedBigInteger('package_id')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('total_due', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2)->default(0);
            $table->string('payment_type')->nullable();
            $table->string('payment_status')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->text('service_description')->nullable();
            $table->unsignedBigInteger('invoice_constraint_id')->nullable();
            $table->unsignedBigInteger('receipt_bond_id')->nullable();
            $table->unsignedBigInteger('receipt_constraint_id')->nullable();
            $table->unsignedBigInteger('receipt_account_id')->nullable();
            $table->timestamps();

            $table->foreign('rental_contract_id', 'rental_contract_invoices_contract_fk')
                ->references('id')
                ->on('rental_contracts')
                ->onDelete('cascade');

            $table->foreign('package_id', 'rental_contract_invoices_package_fk')
                ->references('id')
                ->on('packages')
                ->nullOnDelete();

            $table->foreign('invoice_constraint_id', 'rental_contract_invoices_invoice_constraint_fk')
                ->references('id')
                ->on('fn_constraints')
                ->nullOnDelete();

            $table->foreign('receipt_bond_id', 'rental_contract_invoices_receipt_bond_fk')
                ->references('id')
                ->on('fn_bonds')
                ->nullOnDelete();

            $table->foreign('receipt_constraint_id', 'rental_contract_invoices_receipt_constraint_fk')
                ->references('id')
                ->on('fn_constraints')
                ->nullOnDelete();

            $table->foreign('receipt_account_id', 'rental_contract_invoices_receipt_account_fk')
                ->references('id')
                ->on('fn_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('rental_contract_invoices')) {
            return;
        }

        Schema::table('rental_contract_invoices', function (Blueprint $table) {
            $table->dropForeign('rental_contract_invoices_receipt_account_fk');
            $table->dropForeign('rental_contract_invoices_receipt_constraint_fk');
            $table->dropForeign('rental_contract_invoices_receipt_bond_fk');
            $table->dropForeign('rental_contract_invoices_invoice_constraint_fk');
            $table->dropForeign('rental_contract_invoices_package_fk');
            $table->dropForeign('rental_contract_invoices_contract_fk');
        });

        Schema::dropIfExists('rental_contract_invoices');
    }
};
