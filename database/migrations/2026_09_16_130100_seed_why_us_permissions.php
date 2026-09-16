<?php

use App\PermKeys;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * صلاحيات شاشة "لماذا نحن". الشاشة كانت بلا حماية، فنمنح الصلاحيات تلقائيًا
 * لمن يدير محتوى الموقع حاليًا حتى لا يفقد أحد الوصول بعد النشر:
 * - الكاملة: Administrator / super_admin ومن يملك why_choose_us_control أو settings_control.
 * - العرض فقط: من يملك why_choose_us_view.
 */
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

        $this->grantToExistingContentManagers();
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

    private function grantToExistingContentManagers(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $idsFor = fn (array $names) => DB::table('permissions')
            ->where('guard_name', 'admin')
            ->whereIn('name', $names)
            ->pluck('id');

        $fullRoleIds = DB::table('roles')
            ->where('guard_name', 'admin')
            ->whereIn('name', ['Administrator', 'super_admin'])
            ->pluck('id')
            ->merge(
                DB::table('role_has_permissions')
                    ->whereIn('permission_id', $idsFor([
                        PermKeys::WHY_CHOOSE_US_CONTROL,
                        PermKeys::SETTINGS_CONTROL,
                        'settings_manage',
                    ]))
                    ->pluck('role_id')
            )
            ->unique();

        $viewRoleIds = DB::table('role_has_permissions')
            ->whereIn('permission_id', $idsFor([PermKeys::WHY_CHOOSE_US_VIEW]))
            ->whereNotIn('role_id', $fullRoleIds)
            ->pluck('role_id')
            ->unique();

        $this->assignPermissions($fullRoleIds, $idsFor($this->permissions()));
        $this->assignPermissions($viewRoleIds, $idsFor([PermKeys::WHY_US_VIEW]));
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
            PermKeys::WHY_US_VIEW,
            PermKeys::WHY_US_CREATE,
            PermKeys::WHY_US_EDIT,
            PermKeys::WHY_US_DELETE,
            PermKeys::WHY_US_CONTROL,
        ];
    }
};
