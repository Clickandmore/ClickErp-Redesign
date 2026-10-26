<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->updateOrInsert([
            'name' => 'orders_delete', 'guard_name' => 'admin',
        ], array_intersect_key([
            'display_name' => 'حذف طلبات الآخرين',
            'description' => 'حذف واستعادة طلبات الموظفين الآخرين ضمن الفروع المتاحة. يستطيع منشئ الطلب حذف طلباته واستعادتها دون هذه الصلاحية، مع استمرار شروط الحذف.',
            'section_name' => 'orders', 'group_name' => 'orders', 'is_active' => true,
        ], array_flip(Schema::getColumnListing('permissions'))));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Preserve existing permission assignments.
    }
};
