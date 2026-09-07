<?php

namespace Tests\Concerns;

use App\Models\auth\Admin;
use App\Models\Branch;
use App\PermKeys;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Shared setup for branch tests.
 *
 * SystemModuleManager memoises the enabled-map and the branch limit in the cache,
 * so anything that flips those settings mid-suite has to clear it or the next test
 * inherits the previous test's answer.
 */
trait InteractsWithBranches
{
    protected function setUpBranches(): void
    {
        Cache::flush();
    }

    protected function enableBranchesModule(bool $enabled = true): void
    {
        DB::table('system_module_settings')->updateOrInsert(
            ['key' => 'branches'],
            ['name' => 'branches', 'is_enabled' => $enabled, 'updated_at' => now(), 'created_at' => now()]
        );

        Cache::flush();
    }

    protected function setBranchLimit(?int $limit): void
    {
        DB::table('dev_superadmin_settings')->updateOrInsert(
            ['key' => 'branches_limit'],
            ['value' => $limit === null ? null : (string) $limit, 'updated_at' => now(), 'created_at' => now()]
        );

        Cache::flush();
    }

    protected function makeBranch(array $attributes = []): Branch
    {
        return Branch::factory()->create($attributes);
    }

    /** An admin locked to one branch. */
    protected function branchAdmin(Branch $branch, array $attributes = []): Admin
    {
        return Admin::factory()->forBranch($branch)->create($attributes);
    }

    /**
     * Strip branches_view_all from every role the admin holds.
     *
     * The seeded Administrator role legitimately grants it, so an admin built with
     * that role is a "sees every branch" user no matter which branch_id it carries.
     * A test about branch isolation has to say out loud that its subject is locked
     * to one branch, rather than inheriting whatever the seeded role happens to hold.
     */
    protected function lockToOwnBranch(Admin $admin): Admin
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', [
                PermKeys::BRANCHES_VIEW_ALL,
                PermKeys::CLIENTS_VIEW_ALL_BRANCHES,
                PermKeys::MAIDS_VIEW_ALL_BRANCHES,
                PermKeys::OFFICES_VIEW_ALL_BRANCHES,
            ])
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            $roleIds = $admin->roles()->pluck('roles.id');

            if ($roleIds->isNotEmpty()) {
                DB::table('role_has_permissions')
                    ->whereIn('role_id', $roleIds)
                    ->whereIn('permission_id', $permissionIds)
                    ->delete();
            }

            DB::table('model_has_permissions')
                ->where('model_id', $admin->id)
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Cache::flush();

        return $admin->fresh();
    }

    /** An admin with no branch_id — the "sees everything" case. */
    protected function globalAdmin(array $attributes = []): Admin
    {
        return Admin::factory()->global()->create($attributes);
    }

    protected function actingAsAdmin(Admin $admin): self
    {
        $this->actingAs($admin, 'admin');

        return $this;
    }
}
