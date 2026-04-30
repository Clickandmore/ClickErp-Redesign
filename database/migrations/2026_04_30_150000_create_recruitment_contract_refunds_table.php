<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('recruitment_contract_refunds')) {
            return;
        }

        Schema::create('recruitment_contract_refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('musaned_transfer_item_id')->nullable();
            $table->unsignedBigInteger('refund_constraint_id')->nullable();
            $table->string('cancelled_by', 30)->nullable()->comment('client, office, external_office, none_before_transfer');
            $table->date('refund_date');
            $table->unsignedInteger('days_from_contract')->default(0);
            $table->decimal('penalty_percent', 8, 4)->default(0);
            $table->decimal('contract_amount', 12, 2)->default(0);
            $table->decimal('penalty_amount', 12, 2)->default(0);
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();

            $table->foreign('contract_id')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
            $table->foreign('musaned_transfer_item_id')->references('id')->on('musaned_transfer_items')->nullOnDelete();
            $table->foreign('refund_constraint_id')->references('id')->on('fn_constraints')->nullOnDelete();
            $table->foreign('created_by_id')->references('id')->on('admins')->nullOnDelete();
            $table->index(['contract_id', 'invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_contract_refunds');
    }
};
