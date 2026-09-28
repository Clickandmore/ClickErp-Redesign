<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->json('hourly_customer_profile')->nullable();
            $t->decimal('hourly_wallet_balance', 12, 2)->default(0);
        });
        Schema::table('user_addresses', function (Blueprint $t) {
            $t->string('contact_first_name')->nullable();
            $t->string('contact_last_name')->nullable();
            $t->string('contact_phone2', 30)->nullable();
            $t->unsignedBigInteger('hourly_district_id')->nullable();
        });
        Schema::table('hourly_rental_orders', function (Blueprint $t) {
            $t->uuid('booking_token')->nullable()->unique();
            $t->string('coupon_code')->nullable();
            $t->json('booking_metadata')->nullable();
        });
        Schema::table('hourly_visits', function (Blueprint $t) {
            $t->unsignedBigInteger('delivery_driver_id')->nullable();
            $t->unsignedBigInteger('pickup_driver_id')->nullable();
        });
        Schema::create('hourly_coupons', function (Blueprint $t) {
            $t->id(); $t->string('code')->unique(); $t->string('type');
            $t->decimal('value',12,2); $t->decimal('minimum_amount',12,2)->default(0);
            $t->unsignedBigInteger('service_category_id')->nullable();
            $t->timestamp('starts_at')->nullable(); $t->timestamp('expires_at')->nullable();
            $t->unsignedInteger('usage_limit')->nullable(); $t->unsignedInteger('used_count')->default(0);
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('hourly_wallet_transactions', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id')->index();
            $t->unsignedBigInteger('hourly_rental_order_id')->nullable()->unique();
            $t->decimal('amount',12,2); $t->decimal('balance_after',12,2);
            $t->unsignedBigInteger('admin_id')->nullable(); $t->timestamps();
        });
        Schema::create('hourly_worker_leaves', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('maid_id')->index();
            $t->dateTime('starts_at'); $t->dateTime('ends_at');
            $t->string('reason')->nullable(); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('hourly_visit_reservations', function (Blueprint $t) {
            $t->id(); $t->foreignId('hourly_visit_id')->constrained('hourly_visits')->cascadeOnDelete();
            $t->unsignedBigInteger('maid_id')->index(); $t->unique(['hourly_visit_id','maid_id']); $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hourly_visit_reservations');
        Schema::dropIfExists('hourly_worker_leaves');
        Schema::dropIfExists('hourly_wallet_transactions');
        Schema::dropIfExists('hourly_coupons');
        Schema::table('hourly_visits', fn (Blueprint $t) => $t->dropColumn(['delivery_driver_id','pickup_driver_id']));
        Schema::table('hourly_rental_orders', function (Blueprint $t) {
            $t->dropUnique(['booking_token']); $t->dropColumn(['booking_token','coupon_code','booking_metadata']);
        });
        Schema::table('user_addresses', fn (Blueprint $t) => $t->dropColumn(['contact_first_name','contact_last_name','contact_phone2','hourly_district_id']));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['hourly_customer_profile','hourly_wallet_balance']));
    }
};
