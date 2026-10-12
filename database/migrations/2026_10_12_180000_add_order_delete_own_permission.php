<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $columns = array_flip(Schema::getColumnListing('permissions'));
        DB::table('permissions')->updateOrInsert([
            'name' => 'orders_delete_own', 'guard_name' => 'admin',
        ], array_intersect_key([
            'display_name' => 'حذف طلباتي',
            'description' => 'حذف واستعادة الطلبات والحجوزات التي أنشأها المستخدم فقط، ضمن شروط الحذف والفروع المتاحة.',
            'section_name' => 'orders', 'group_name' => 'orders', 'is_active' => true,
        ], $columns));
        DB::table('permissions')->where('name', 'orders_delete')->where('guard_name', 'admin')->update(array_intersect_key([
            'display_name' => 'حذف طلبات الآخرين',
            'description' => 'حذف واستعادة طلبات المستخدم وطلبات الموظفين الآخرين، ضمن شروط الحذف والفروع المتاحة.',
        ], $columns));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Preserve existing permission assignments.
    }
};
