<?php

namespace Tests\Feature\Branch;

use App\Models\Permission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

class AdminUsersBranchScopeTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        $this->enableBranchesModule();
    }

    private function grant($admin, string $permission): void
    {
        Permission::withoutGlobalScopes()->updateOrCreate(
            ['name' => $permission, 'guard_name' => 'admin'],
            [
                'display_name' => $permission,
                'group_name' => 'branches',
                'section_name' => 'users_management',
                'is_active' => true,
            ]
        );

        $admin->givePermissionTo($permission);
    }

    /** @test */
    public function branch_employee_list_is_strictly_limited_to_the_logged_in_branch(): void
    {
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $viewer = $this->branchAdmin($own);
        $this->grant($viewer, 'users_view');
        $ownEmployee = $this->branchAdmin($own, ['name' => 'Own branch employee']);
        $otherEmployee = $this->branchAdmin($other, ['name' => 'Other branch employee']);
        $unassignedEmployee = $this->globalAdmin(['name' => 'Unassigned employee']);

        $response = $this->actingAsAdmin($viewer)->get(
            route('admins.index', [
                'filter_scope' => 'branch',
                'branch_id' => $other->id,
            ]),
            ['X-Requested-With' => 'XMLHttpRequest']
        );

        $response->assertOk()
            ->assertJsonFragment(['name' => $ownEmployee->name])
            ->assertJsonMissing(['name' => $otherEmployee->name])
            ->assertJsonMissing(['name' => $unassignedEmployee->name]);
    }

    /** @test */
    public function view_all_permission_allows_filtering_the_employee_list_by_another_branch(): void
    {
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $viewer = $this->branchAdmin($own);
        $this->grant($viewer, 'users_view');
        $this->grant($viewer, \App\PermKeys::BRANCHES_VIEW_ALL);
        $ownEmployee = $this->branchAdmin($own, ['name' => 'Own branch employee']);
        $otherEmployee = $this->branchAdmin($other, ['name' => 'Other branch employee']);

        $response = $this->actingAsAdmin($viewer)->get(
            route('admins.index', [
                'filter_scope' => 'branch',
                'branch_id' => $other->id,
            ]),
            ['X-Requested-With' => 'XMLHttpRequest']
        );

        $response->assertOk()
            ->assertJsonMissing(['name' => $ownEmployee->name])
            ->assertJsonFragment(['name' => $otherEmployee->name]);
    }
}
