<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('moyasar_contract_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('rental_contract_id')->index();
            $t->string('invoice_id')->unique(); $t->string('provider_payment_id')->nullable()->index();
            $t->unsignedBigInteger('reminder_id')->nullable(); $t->decimal('amount',12,2);
            $t->decimal('refunded_amount',12,2)->default(0); $t->timestamp('paid_at')->nullable();
            $t->unsignedBigInteger('receipt_bond_id')->nullable(); $t->timestamps();
        });
        Schema::create('moyasar_contract_refunds', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('moyasar_contract_payment_id');
            $t->decimal('amount',12,2); $t->decimal('cumulative_amount',12,2);
            $t->unsignedBigInteger('bond_id'); $t->timestamps();
            $t->unique(['moyasar_contract_payment_id','cumulative_amount'], 'moyasar_refund_milestone_unique');
        });
        Schema::table('rental_contract_infos', fn(Blueprint $t) => $t->decimal('moyasar_refunded_amount',12,2)->default(0));
        foreach (['rental_order_payments','hourly_order_payments'] as $table) {
            Schema::table($table, fn(Blueprint $t) => $t->decimal('refunded_amount',12,2)->default(0));
        }
    }
    public function down(): void
    {
        Schema::dropIfExists('moyasar_contract_refunds');
        Schema::dropIfExists('moyasar_contract_payments');
        Schema::table('rental_contract_infos', fn(Blueprint $t) => $t->dropColumn('moyasar_refunded_amount'));
        foreach (['rental_order_payments','hourly_order_payments'] as $table) {
            Schema::table($table, fn(Blueprint $t) => $t->dropColumn('refunded_amount'));
        }
    }
};
