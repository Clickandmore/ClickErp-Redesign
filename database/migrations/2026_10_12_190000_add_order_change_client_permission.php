<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->updateOrInsert(['name' => 'orders_change_client', 'guard_name' => 'admin'], array_intersect_key([
            'display_name' => 'تغيير العميل في الطلب',
            'description' => 'تغيير العميل للطلبات والحجوزات الجديدة أو قيد التنفيذ غير المرتبطة بعقد أو مدفوعات.',
            'section_name' => 'orders', 'group_name' => 'orders', 'is_active' => true,
        ], array_flip(Schema::getColumnListing('permissions'))));
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
    public function down(): void
    {
        // Preserve permission assignments.
    }
};
