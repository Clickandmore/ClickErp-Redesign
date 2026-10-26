<?php

use App\PermKeys;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')
            || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $permissionData = [
            'guard_name' => 'admin',
        ];

        foreach ([
            'display_name' => 'تصدير سجل النشاط',
            'description' => 'تصدير سجلات النشاط المطابقة للفلاتر الحالية.',
            'group_name' => 'activity',
            'section_name' => 'general',
            'is_active' => true,
        ] as $column => $value) {
            if (Schema::hasColumn('permissions', $column)) {
                $permissionData[$column] = $value;
            }
        }

        DB::table('permissions')->updateOrInsert(
            ['name' => PermKeys::ACTIVITY_EXPORT, 'guard_name' => 'admin'],
            $permissionData
        );

        $permissionId = DB::table('permissions')
            ->where('name', PermKeys::ACTIVITY_EXPORT)
            ->where('guard_name', 'admin')
            ->value('id');
        $roleId = DB::table('roles')
            ->where('name', 'Administrator')
            ->where('guard_name', 'admin')
            ->value('id');

        if ($permissionId && $roleId) {
            DB::table('role_has_permissions')->updateOrInsert(
                ['permission_id' => $permissionId, 'role_id' => $roleId],
                []
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $ids = DB::table('permissions')
            ->where('name', PermKeys::ACTIVITY_EXPORT)
            ->where('guard_name', 'admin')
            ->pluck('id');

        if (Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        }

        DB::table('permissions')->whereIn('id', $ids)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
