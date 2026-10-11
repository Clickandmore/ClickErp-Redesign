<?php

use App\PermKeys;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        $data = ['created_at' => now(), 'updated_at' => now()];
        foreach (['display_name' => 'عرض إجمالي تكلفة المورد في الفاتورة',
            'description' => 'عرض إجمالي تكلفة المورد بالدولار ومصدر التكلفة في مسار الفاتورة الجديد وطلبات الاعتماد.',
            'group_name' => 'invoices', 'section_name' => 'recruitment_contracts', 'is_active' => true] as $column => $value) {
            if (Schema::hasColumn('permissions', $column)) $data[$column] = $value;
        }
        DB::table('permissions')->updateOrInsert(['name' => PermKeys::INVOICES_VIEW_SUPPLIER_TOTAL_COST, 'guard_name' => 'admin'], $data);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->where('guard_name', 'admin')->where('name', PermKeys::INVOICES_VIEW_SUPPLIER_TOTAL_COST)->pluck('id');
        foreach (['role_has_permissions', 'model_has_permissions', 'role_branch_permissions'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'permission_id')) DB::table($table)->whereIn('permission_id', $ids)->delete();
        }
        DB::table('permissions')->whereIn('id', $ids)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
