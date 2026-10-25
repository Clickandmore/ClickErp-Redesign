<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Customer compensation is a separate expense linked to the order: the issued invoice and the order total stay as they are.
 * Wallet compensations are credited at once; cash and bank compensations wait until a cashier records the payout.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('hourly_order_compensations')) {
            Schema::create('hourly_order_compensations', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('hourly_rental_order_id')->index();
                $t->unsignedBigInteger('visit_id')->nullable()->index();
                $t->unsignedBigInteger('user_id')->index();
                $t->decimal('amount', 12, 2);
                $t->string('reason', 30);
                $t->text('note')->nullable();
                $t->string('destination', 20);
                $t->string('status', 20)->index();
                $t->unsignedBigInteger('wallet_transaction_id')->nullable();
                $t->boolean('over_paid_limit')->default(false);
                $t->string('payout_reference', 100)->nullable();
                $t->dateTime('paid_out_at')->nullable();
                $t->unsignedBigInteger('paid_out_by')->nullable();
                $t->unsignedBigInteger('created_by');
                $t->uuid('request_token')->unique();
                $t->timestamps();
            });
        }
        if (!Schema::hasTable('permissions')) return;
        foreach ([
            'hourly_orders_compensate' => ['تعويض العملاء', 'تعويض العميل عن مشكلة في طلب بمبلغ لا يتجاوز ما دفعه، إلى المحفظة أو نقدًا أو تحويلًا، مع تسجيل السبب'],
            'hourly_orders_compensate_override' => ['تعويض يتجاوز المدفوع', 'تعويض العميل بمبلغ يتجاوز ما دفعه عن الطلب أو الزيارة'],
        ] as $name => [$display, $description]) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin'], [
                'group_name' => 'hourly_orders', 'section_name' => 'hourly_rental', 'display_name' => $display, 'description' => $description,
            ]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('hourly_order_compensations');
        if (!Schema::hasTable('permissions')) return;
        Permission::whereIn('name', ['hourly_orders_compensate', 'hourly_orders_compensate_override'])->where('guard_name', 'admin')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
