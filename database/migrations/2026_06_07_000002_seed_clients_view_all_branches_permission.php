<?php

use App\PermKeys;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedClientsViewAllBranchesPermission extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $payload = [
            'guard_name' => 'admin',
            'updated_at' => now(),
            'created_at' => now(),
        ];

        if (Schema::hasColumn('permissions', 'display_name')) {
            $payload['display_name'] = 'عرض عملاء كل الفروع';
        }

        if (Schema::hasColumn('permissions', 'description')) {
            $payload['description'] = 'إمكانية عرض وإدارة عملاء كل الفروع عند تفعيل موديول الفروع';
        }

        if (Schema::hasColumn('permissions', 'group_name')) {
            $payload['group_name'] = 'clients';
        }

        if (Schema::hasColumn('permissions', 'section_name')) {
            $payload['section_name'] = 'clients_maids';
        }

        if (Schema::hasColumn('permissions', 'is_active')) {
            $payload['is_active'] = true;
        }

        DB::table('permissions')->updateOrInsert(
            ['name' => PermKeys::CLIENTS_VIEW_ALL_BRANCHES, 'guard_name' => 'admin'],
            $payload
        );

        $this->assignToAdministrator();
    }

    public function down()
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')
            ->where('guard_name', 'admin')
            ->where('name', PermKeys::CLIENTS_VIEW_ALL_BRANCHES)
            ->value('id');

        if ($permissionId && Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')
                ->where('permission_id', $permissionId)
                ->delete();
        }

        DB::table('permissions')
            ->where('guard_name', 'admin')
            ->where('name', PermKeys::CLIENTS_VIEW_ALL_BRANCHES)
            ->delete();
    }

    private function assignToAdministrator(): void
    {
        if (
            !Schema::hasTable('roles') ||
            !Schema::hasTable('role_has_permissions')
        ) {
            return;
        }

        $role = DB::table('roles')
            ->where('guard_name', 'admin')
            ->where('name', 'Administrator')
            ->first();

        $permissionId = DB::table('permissions')
            ->where('guard_name', 'admin')
            ->where('name', PermKeys::CLIENTS_VIEW_ALL_BRANCHES)
            ->value('id');

        if (!$role || !$permissionId) {
            return;
        }

        DB::table('role_has_permissions')->updateOrInsert([
            'permission_id' => $permissionId,
            'role_id' => $role->id,
        ]);
    }
}
