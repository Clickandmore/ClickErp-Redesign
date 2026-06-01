<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AssignBranchPermissionsToAdministratorRole extends Migration
{
    public function up()
    {
        if (
            !Schema::hasTable('roles') ||
            !Schema::hasTable('permissions') ||
            !Schema::hasTable('role_has_permissions')
        ) {
            return;
        }

        $role = DB::table('roles')
            ->where('guard_name', 'admin')
            ->where('name', 'Administrator')
            ->first();

        if (!$role) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'admin')
            ->whereIn('name', [
                'branches_view',
                'branches_create',
                'branches_edit',
                'branches_delete',
                'branches_control',
            ])
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $role->id,
            ]);
        }
    }

    public function down()
    {
        if (
            !Schema::hasTable('roles') ||
            !Schema::hasTable('permissions') ||
            !Schema::hasTable('role_has_permissions')
        ) {
            return;
        }

        $role = DB::table('roles')
            ->where('guard_name', 'admin')
            ->where('name', 'Administrator')
            ->first();

        if (!$role) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'admin')
            ->whereIn('name', [
                'branches_view',
                'branches_create',
                'branches_edit',
                'branches_delete',
                'branches_control',
            ])
            ->pluck('id');

        DB::table('role_has_permissions')
            ->where('role_id', $role->id)
            ->whereIn('permission_id', $permissionIds)
            ->delete();
    }
}
