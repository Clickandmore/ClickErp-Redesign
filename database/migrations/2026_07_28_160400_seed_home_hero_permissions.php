<?php

use App\PermKeys;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissions = [
        PermKeys::HOME_HERO_VIEW,
        PermKeys::HOME_HERO_CREATE,
        PermKeys::HOME_HERO_EDIT,
        PermKeys::HOME_HERO_DELETE,
        PermKeys::HOME_HERO_CONTROL,
    ];

    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        foreach ($this->permissions as $name) {
            $payload = ['guard_name' => 'admin'];

            foreach ([
                'group_name' => 'home_hero',
                'section_name' => 'content_management',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ] as $column => $value) {
                if (Schema::hasColumn('permissions', $column)) {
                    $payload[$column] = $value;
                }
            }

            DB::table('permissions')->updateOrInsert(
                ['name' => $name, 'guard_name' => 'admin'],
                $payload
            );
        }

        if (Schema::hasTable('roles') && Schema::hasTable('role_has_permissions')) {
            $roleIds = DB::table('roles')
                ->where('guard_name', 'admin')
                ->whereIn('name', ['Administrator', 'super_admin'])
                ->pluck('id');
            $permissionIds = DB::table('permissions')
                ->where('guard_name', 'admin')
                ->whereIn('name', $this->permissions)
                ->pluck('id');

            foreach ($roleIds as $roleId) {
                foreach ($permissionIds as $permissionId) {
                    DB::table('role_has_permissions')->updateOrInsert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $ids = DB::table('permissions')
            ->where('guard_name', 'admin')
            ->whereIn('name', $this->permissions)
            ->pluck('id');

        foreach (['role_has_permissions', 'model_has_permissions', 'role_branch_permissions'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'permission_id')) {
                DB::table($table)->whereIn('permission_id', $ids)->delete();
            }
        }

        DB::table('permissions')->whereIn('id', $ids)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
