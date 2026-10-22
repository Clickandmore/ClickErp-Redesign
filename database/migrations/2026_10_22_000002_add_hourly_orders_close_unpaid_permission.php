<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) return;
        Permission::firstOrCreate(['name' => 'hourly_orders_close_unpaid', 'guard_name' => 'admin'], [
            'group_name' => 'hourly_orders', 'section_name' => 'hourly_rental', 'display_name' => 'إغلاق طلب غير مسدد',
            'description' => 'إغلاق طلب انتهت زياراته ولم يُسدَّد كاملًا، مع بقاء المتبقي مسجّلًا عليه وتسجيل السبب',
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) return;
        Permission::where('name', 'hourly_orders_close_unpaid')->where('guard_name', 'admin')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
