<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRentalOrderPaymentsTable extends Migration
{
    public function up(): void
    {
        Schema::create('rental_order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->uuid('public_id')->unique();
            $table->string('provider')->default('moyasar');
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('SAR');
            $table->string('status')->default('creating');
            $table->string('provider_invoice_id')->nullable()->unique();
            $table->string('provider_payment_id')->nullable()->index();
            $table->text('payment_url')->nullable();
            $table->json('provider_payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'provider', 'status'], 'rental_order_payment_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_order_payments');
    }
}
