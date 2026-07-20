<?php

namespace Tests\Feature\Branch;

use App\Http\Controllers\admin\DashboardController;
use App\Models\Permission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

class DashboardBranchScopeTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        $this->enableBranchesModule();
    }

    private function dashboardContext(Request $request): array
    {
        $method = new \ReflectionMethod(DashboardController::class, 'resolveDashboardBranchContext');
        $method->setAccessible(true);

        return $method->invoke(app(DashboardController::class), $request);
    }

    private function grantViewAllBranchesPermission($admin): void
    {
        Permission::withoutGlobalScopes()->updateOrCreate(
            ['name' => \App\PermKeys::BRANCHES_VIEW_ALL, 'guard_name' => 'admin'],
            [
                'display_name' => 'عرض بيانات جميع الفروع',
                'group_name' => 'branches',
                'section_name' => 'users_management',
                'is_active' => true,
            ]
        );

        $admin->givePermissionTo(\App\PermKeys::BRANCHES_VIEW_ALL);
    }

    /** @test */
    public function dashboard_forces_a_branch_user_to_their_own_branch(): void
    {
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $admin = $this->branchAdmin($own);
        $this->actingAsAdmin($admin);

        $context = $this->dashboardContext(
            Request::create('/dashboard', 'GET', ['branch_id' => $other->id])
        );

        $this->assertTrue($context['enabled']);
        $this->assertTrue($context['isForced']);
        $this->assertFalse($context['canSelect']);
        $this->assertSame($own->id, $context['activeBranchId']);
        $this->assertSame([$own->id], $context['branches']->pluck('id')->all());
    }

    /** @test */
    public function dashboard_view_all_permission_enables_all_branches_and_filtering(): void
    {
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $admin = $this->branchAdmin($own);
        $this->grantViewAllBranchesPermission($admin);
        $this->actingAsAdmin($admin);

        $context = $this->dashboardContext(
            Request::create('/dashboard', 'GET', ['branch_id' => $other->id])
        );

        $this->assertFalse($context['isForced']);
        $this->assertTrue($context['canSelect']);
        $this->assertSame($other->id, $context['activeBranchId']);
        $this->assertEqualsCanonicalizing(
            [$own->id, $other->id],
            $context['branches']->pluck('id')->all()
        );
    }

    /** @test */
    public function dashboard_branch_filter_excludes_other_and_unassigned_rows(): void
    {
        $this->makeBranch(['is_main' => true]);
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $ownEmployee = $this->branchAdmin($own)->id;
        $otherEmployee = $this->branchAdmin($other)->id;
        $unassignedEmployee = $this->globalAdmin()->id;

        $query = DB::table('admins')->whereIn('id', [
            $ownEmployee,
            $otherEmployee,
            $unassignedEmployee,
        ]);
        $method = new \ReflectionMethod(DashboardController::class, 'applyDashboardBranchFilter');
        $method->setAccessible(true);
        $method->invoke(
            app(DashboardController::class),
            $query,
            $own->id,
            'admins.branch_id',
            'admins'
        );

        $this->assertSame([$ownEmployee], $query->pluck('id')->all());
    }

    /** @test */
    public function dashboard_main_branch_includes_legacy_unassigned_rows(): void
    {
        $main = $this->makeBranch(['is_main' => true]);
        $other = $this->makeBranch();
        $mainEmployee = $this->branchAdmin($main)->id;
        $otherEmployee = $this->branchAdmin($other)->id;
        $unassignedEmployee = $this->globalAdmin()->id;
        $query = DB::table('admins')->whereIn('id', [
            $mainEmployee,
            $otherEmployee,
            $unassignedEmployee,
        ]);
        $method = new \ReflectionMethod(DashboardController::class, 'applyDashboardBranchFilter');
        $method->setAccessible(true);
        $method->invoke(
            app(DashboardController::class),
            $query,
            $main->id,
            'admins.branch_id',
            'admins'
        );

        $this->assertEqualsCanonicalizing(
            [$mainEmployee, $unassignedEmployee],
            $query->pluck('id')->all()
        );
    }

    /** @test */
    public function dashboard_does_not_apply_branch_scoping_when_the_module_is_disabled(): void
    {
        $branch = $this->makeBranch();
        $admin = $this->branchAdmin($branch);
        $this->actingAsAdmin($admin);
        $this->enableBranchesModule(false);

        $context = $this->dashboardContext(Request::create('/dashboard'));

        $this->assertFalse($context['enabled']);
        $this->assertFalse($context['isForced']);
        $this->assertFalse($context['canSelect']);
        $this->assertNull($context['activeBranchId']);
    }
}
