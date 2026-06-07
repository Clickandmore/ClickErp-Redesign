<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BranchScopedPermissions
{
    public static function appliesToAdmin($admin): bool
    {
        return $admin
            && SystemModuleManager::isEnabled('branches')
            && Schema::hasTable('role_branch_permission_scopes')
            && Schema::hasTable('role_branch_permissions')
            && Schema::hasTable('permissions')
            && Schema::hasColumn('admins', 'branch_id')
            && !empty($admin->branch_id)
            && self::roleIdsForAdmin($admin)->isNotEmpty();
    }

    public static function adminHasPermission($admin, string $permission): ?bool
    {
        if (!self::appliesToAdmin($admin)) {
            return null;
        }

        $roleIds = self::roleIdsForAdmin($admin);
        $branchId = (int) $admin->branch_id;

        if (!self::hasConfiguredBranchPermissions($roleIds, $branchId)) {
            return null;
        }

        return DB::table('role_branch_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_branch_permissions.permission_id')
            ->whereIn('role_branch_permissions.role_id', $roleIds)
            ->where('role_branch_permissions.branch_id', $branchId)
            ->where('permissions.guard_name', 'admin')
            ->where('permissions.name', $permission)
            ->exists();
    }

    public static function adminHasAnyPermission($admin, array $permissions): ?bool
    {
        if (!self::appliesToAdmin($admin)) {
            return null;
        }

        $roleIds = self::roleIdsForAdmin($admin);
        $branchId = (int) $admin->branch_id;

        if (!self::hasConfiguredBranchPermissions($roleIds, $branchId)) {
            return null;
        }

        return DB::table('role_branch_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_branch_permissions.permission_id')
            ->whereIn('role_branch_permissions.role_id', $roleIds)
            ->where('role_branch_permissions.branch_id', $branchId)
            ->where('permissions.guard_name', 'admin')
            ->whereIn('permissions.name', array_values(array_unique($permissions)))
            ->exists();
    }

    public static function permissionNamesForRoleBranch(int $roleId, int $branchId): Collection
    {
        if (
            !SystemModuleManager::isEnabled('branches') ||
            !Schema::hasTable('role_branch_permissions') ||
            !Schema::hasTable('permissions')
        ) {
            return collect();
        }

        return DB::table('role_branch_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_branch_permissions.permission_id')
            ->where('role_branch_permissions.role_id', $roleId)
            ->where('role_branch_permissions.branch_id', $branchId)
            ->where('permissions.guard_name', 'admin')
            ->pluck('permissions.name');
    }

    public static function syncRoleBranchPermissions(int $roleId, int $branchId, Collection $permissionNames): Collection
    {
        if (
            !SystemModuleManager::isEnabled('branches') ||
            !Schema::hasTable('role_branch_permission_scopes') ||
            !Schema::hasTable('role_branch_permissions') ||
            !Schema::hasTable('permissions')
        ) {
            return collect();
        }

        $permissions = DB::table('permissions')
            ->where('guard_name', 'admin')
            ->whereIn('name', $permissionNames->values())
            ->get(['id', 'name']);

        DB::table('role_branch_permissions')
            ->where('role_id', $roleId)
            ->where('branch_id', $branchId)
            ->delete();

        $now = now();

        DB::table('role_branch_permission_scopes')->updateOrInsert(
            ['role_id' => $roleId, 'branch_id' => $branchId],
            ['updated_at' => $now, 'created_at' => $now]
        );

        $rows = $permissions->map(fn ($permission) => [
            'role_id' => $roleId,
            'branch_id' => $branchId,
            'permission_id' => $permission->id,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($rows !== []) {
            DB::table('role_branch_permissions')->insert($rows);
        }

        return $permissions->pluck('name');
    }

    public static function hasConfiguredBranchPermissions(Collection $roleIds, int $branchId): bool
    {
        if (!Schema::hasTable('role_branch_permission_scopes')) {
            return false;
        }

        return DB::table('role_branch_permission_scopes')
            ->whereIn('role_id', $roleIds)
            ->where('branch_id', $branchId)
            ->exists();
    }

    private static function roleIdsForAdmin($admin): Collection
    {
        $roleIds = collect();

        if (!empty($admin->role_id)) {
            $roleIds->push((int) $admin->role_id);
        }

        if (method_exists($admin, 'roles')) {
            try {
                $roleIds = $roleIds->merge($admin->roles()->pluck('roles.id')->all());
            } catch (\Throwable $e) {
                // The legacy role_id relation above is enough for older admin records.
            }
        }

        return $roleIds
            ->filter(fn ($roleId) => is_numeric($roleId) && (int) $roleId > 0)
            ->map(fn ($roleId) => (int) $roleId)
            ->unique()
            ->values();
    }
}
