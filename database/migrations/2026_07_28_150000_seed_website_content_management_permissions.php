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
        if (! Schema::hasTable('permissions')) {
            return;
        }

        foreach ($this->permissions() as $name) {
            $payload = ['guard_name' => 'admin'];

            if (Schema::hasColumn('permissions', 'group_name')) {
                $payload['group_name'] = 'content';
            }

            if (Schema::hasColumn('permissions', 'section_name')) {
                $payload['section_name'] = 'content_management';
            }

            if (Schema::hasColumn('permissions', 'is_active')) {
                $payload['is_active'] = true;
            }

            if (Schema::hasColumn('permissions', 'updated_at')) {
                $payload['updated_at'] = now();
            }

            if (Schema::hasColumn('permissions', 'created_at')) {
                $payload['created_at'] = now();
            }

            DB::table('permissions')->updateOrInsert(
                ['name' => $name, 'guard_name' => 'admin'],
                $payload
            );
        }

        $this->preserveExistingRoleAccess();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'admin')
            ->whereIn('name', $this->permissions())
            ->pluck('id');

        foreach (['role_has_permissions', 'model_has_permissions', 'role_branch_permissions'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'permission_id')) {
                DB::table($table)->whereIn('permission_id', $permissionIds)->delete();
            }
        }

        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function preserveExistingRoleAccess(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $allPermissionIds = DB::table('permissions')
            ->where('guard_name', 'admin')
            ->whereIn('name', $this->permissions())
            ->pluck('id');

        $viewPermissionIds = DB::table('permissions')
            ->where('guard_name', 'admin')
            ->whereIn('name', $this->viewPermissions())
            ->pluck('id');

        $settingsPermissionIds = DB::table('permissions')
            ->where('guard_name', 'admin')
            ->whereIn('name', [
                PermKeys::SETTINGS_VIEW,
                PermKeys::SETTINGS_CONTROL,
                'settings_manage',
            ])
            ->pluck('id', 'name');

        $fullRoleIds = DB::table('roles')
            ->where('guard_name', 'admin')
            ->whereIn('name', ['Administrator', 'super_admin'])
            ->pluck('id');

        $controlPermissionIds = collect([
            $settingsPermissionIds[PermKeys::SETTINGS_CONTROL] ?? null,
            $settingsPermissionIds['settings_manage'] ?? null,
        ])->filter();

        if ($controlPermissionIds->isNotEmpty()) {
            $fullRoleIds = $fullRoleIds
                ->merge(
                    DB::table('role_has_permissions')
                        ->whereIn('permission_id', $controlPermissionIds)
                        ->pluck('role_id')
                )
                ->unique();
        }

        $viewRoleIds = collect();
        $settingsViewId = $settingsPermissionIds[PermKeys::SETTINGS_VIEW] ?? null;

        if ($settingsViewId) {
            $viewRoleIds = DB::table('role_has_permissions')
                ->where('permission_id', $settingsViewId)
                ->whereNotIn('role_id', $fullRoleIds)
                ->pluck('role_id');
        }

        $this->assignPermissions($fullRoleIds, $allPermissionIds);
        $this->assignPermissions($viewRoleIds, $viewPermissionIds);
    }

    private function assignPermissions($roleIds, $permissionIds): void
    {
        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    private function permissions(): array
    {
        return [
            PermKeys::WHY_CHOOSE_US_VIEW,
            PermKeys::WHY_CHOOSE_US_CREATE,
            PermKeys::WHY_CHOOSE_US_EDIT,
            PermKeys::WHY_CHOOSE_US_DELETE,
            PermKeys::WHY_CHOOSE_US_CONTROL,
            PermKeys::FIND_US_VIEW,
            PermKeys::FIND_US_CREATE,
            PermKeys::FIND_US_EDIT,
            PermKeys::FIND_US_DELETE,
            PermKeys::FIND_US_CONTROL,
            PermKeys::RECRUITMENT_REQUIREMENTS_VIEW,
            PermKeys::RECRUITMENT_REQUIREMENTS_CREATE,
            PermKeys::RECRUITMENT_REQUIREMENTS_EDIT,
            PermKeys::RECRUITMENT_REQUIREMENTS_DELETE,
            PermKeys::RECRUITMENT_REQUIREMENTS_CONTROL,
        ];
    }

    private function viewPermissions(): array
    {
        return [
            PermKeys::WHY_CHOOSE_US_VIEW,
            PermKeys::FIND_US_VIEW,
            PermKeys::RECRUITMENT_REQUIREMENTS_VIEW,
        ];
    }
};
