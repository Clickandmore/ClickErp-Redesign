<?php

namespace Tests\Feature\Branch;

use App\PermKeys;
use App\Support\BranchScopedPermissions;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

class BranchNavigationPermissionsTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithBranches;

    public function test_navigation_reuses_permissions_without_changing_branch_decisions(): void
    {
        $this->enableBranchesModule();
        $branch = $this->makeBranch();
        $role = Role::create(['name' => 'Navigation test role', 'guard_name' => 'admin']);
        $permission = Permission::findOrCreate('navigation-test-allowed', 'admin');
        $admin = $this->branchAdmin($branch, ['role_id' => $role->id]);
        $otherAdmin = $this->branchAdmin($this->makeBranch(), ['role_id' => $role->id]);
        BranchScopedPermissions::syncRoleBranchPermissions($role->id, $branch->id, collect([$permission->name]));

        BranchScopedPermissions::withCachedPermissions(function () use ($admin, $otherAdmin, $permission) {
            $this->assertTrue(BranchScopedPermissions::adminHasPermission($admin, $permission->name));
            DB::enableQueryLog();
            DB::flushQueryLog();
            try {
                for ($i = 0; $i < 20; $i++) {
                    $this->assertTrue(BranchScopedPermissions::adminHasPermission($admin, $permission->name));
                    $this->assertFalse(BranchScopedPermissions::adminHasPermission($admin, 'navigation-test-denied'));
                    $this->assertTrue(BranchScopedPermissions::adminHasAnyPermission($admin, ['navigation-test-denied', $permission->name]));
                    $this->assertFalse(BranchScopedPermissions::adminHasAnyPermission($admin, []));
                }
                $this->assertCount(0, DB::getQueryLog());
            } finally {
                DB::disableQueryLog();
            }
            // A branch without configured permissions must retain the legacy fallback.
            $this->assertNull(BranchScopedPermissions::adminHasPermission($otherAdmin, $permission->name));
        });

        BranchScopedPermissions::syncRoleBranchPermissions($role->id, $branch->id, collect());
        $this->assertFalse(BranchScopedPermissions::adminHasPermission($admin, $permission->name));
        BranchScopedPermissions::withCachedPermissions(function () use ($admin, $permission) {
            // An explicitly configured empty scope denies permissions instead of falling back.
            $this->assertFalse(BranchScopedPermissions::adminHasPermission($admin, $permission->name));
        });
    }

    public function test_render_cache_is_discarded_even_when_rendering_fails(): void
    {
        $this->enableBranchesModule();
        $branch = $this->makeBranch();
        $role = Role::create(['name' => 'Navigation exception role', 'guard_name' => 'admin']);
        $permission = Permission::findOrCreate('navigation-test-exception', 'admin');
        $admin = $this->branchAdmin($branch, ['role_id' => $role->id]);
        BranchScopedPermissions::syncRoleBranchPermissions($role->id, $branch->id, collect([$permission->name]));

        try {
            BranchScopedPermissions::withCachedPermissions(function () use ($admin, $permission) {
                $this->assertTrue(BranchScopedPermissions::adminHasPermission($admin, $permission->name));
                throw new \RuntimeException('render failed');
            });
        } catch (\RuntimeException $exception) {
            $this->assertSame('render failed', $exception->getMessage());
        }

        BranchScopedPermissions::syncRoleBranchPermissions($role->id, $branch->id, collect());
        $this->assertFalse(BranchScopedPermissions::adminHasPermission($admin, $permission->name));
    }

    public function test_post_arrival_refund_permission_command_updates_existing_branch_scope(): void
    {
        $this->enableBranchesModule();
        $branch = $this->makeBranch();
        $role = Role::create(['name' => 'Refund branch role', 'guard_name' => 'admin']);
        $existingPermission = Permission::findOrCreate('refund-existing-permission', 'admin');
        $admin = $this->branchAdmin($branch, ['role_id' => $role->id]);
        BranchScopedPermissions::syncRoleBranchPermissions($role->id, $branch->id, collect([$existingPermission->name]));

        $this->artisan('permissions:assign-post-arrival-refunds', ['--role' => $role->name])
            ->assertExitCode(0);

        $this->assertTrue(BranchScopedPermissions::adminHasPermission($admin, $existingPermission->name));
        $this->assertTrue(BranchScopedPermissions::adminHasPermission($admin, PermKeys::POST_ARRIVAL_REFUND_VIEW));
        $this->assertTrue(BranchScopedPermissions::adminHasPermission($admin, PermKeys::POST_ARRIVAL_REFUND_CREATE));
        $this->assertTrue(BranchScopedPermissions::adminHasPermission($admin, PermKeys::POST_ARRIVAL_REFUND_POST));
        $this->assertTrue(BranchScopedPermissions::adminHasPermission($admin, PermKeys::POST_ARRIVAL_REFUND_PAYOUT));
        $this->assertTrue(BranchScopedPermissions::adminHasPermission($admin, PermKeys::POST_ARRIVAL_REFUND_REVERSE));
    }
}
