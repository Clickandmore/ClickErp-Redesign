<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const PERMISSIONS = [
        'data_import_upload',
        'data_import_review',
        'data_import_approve',
        'data_import_generate_bonds',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('permissions')
            || ! Schema::hasTable('roles')
            || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'admin')
            ->whereIn('name', self::PERMISSIONS)
            ->pluck('id');

        $roleIds = DB::table('roles')
            ->where('guard_name', 'admin')
            ->whereIn('name', ['Administrator', 'super_admin'])
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }

        if (Schema::hasTable('role_branch_permission_scopes')
            && Schema::hasTable('role_branch_permissions')) {
            $scopes = DB::table('role_branch_permission_scopes')
                ->whereIn('role_id', $roleIds)
                ->get(['role_id', 'branch_id']);
            $now = now();

            foreach ($scopes as $scope) {
                foreach ($permissionIds as $permissionId) {
                    DB::table('role_branch_permissions')->insertOrIgnore([
                        'role_id' => $scope->role_id,
                        'branch_id' => $scope->branch_id,
                        'permission_id' => $permissionId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')
            || ! Schema::hasTable('roles')
            || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'admin')
            ->whereIn('name', self::PERMISSIONS)
            ->pluck('id');

        $roleIds = DB::table('roles')
            ->where('guard_name', 'admin')
            ->whereIn('name', ['Administrator', 'super_admin'])
            ->pluck('id');

        DB::table('role_has_permissions')
            ->whereIn('role_id', $roleIds)
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        if (Schema::hasTable('role_branch_permissions')) {
            DB::table('role_branch_permissions')
                ->whereIn('role_id', $roleIds)
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
