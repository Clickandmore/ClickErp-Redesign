<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'orders_view' => 'عرض الطلبات',
            'orders_change_status' => 'تغيير حالة الطلب',
            'orders_change_maid' => 'تغيير العاملة في الطلب',
            'orders_change_responsible' => 'تغيير الموظف المسؤول عن الطلب',
            'orders_reject' => 'رفض/إرجاع الطلب',
            'orders_add_contract' => 'إضافة عقد للطلب',
            'orders_delete' => 'حذف الطلبات',
        ];

        foreach ($permissions as $name => $label) {
            $values = array_intersect_key([
                'display_name' => $label,
                'description' => $label,
                'section_name' => 'orders',
                'group_name' => 'orders',
                'is_active' => true,
            ], array_flip(Schema::getColumnListing('permissions')));
            DB::table('permissions')->updateOrInsert(['name' => $name, 'guard_name' => 'admin'], $values);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Preserve existing permissions and role assignments.
    }
};
