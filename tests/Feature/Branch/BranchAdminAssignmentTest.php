<?php

namespace Tests\Feature\Branch;

use App\Models\auth\Admin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

/**
 * Assigning staff to branches, and the single "branch manager" flag per branch.
 */
class BranchAdminAssignmentTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        $this->enableBranchesModule();
        $this->actingAsAdmin($this->globalAdmin()->assignRole('Administrator'));
    }

    private function isManager(Admin $admin): bool
    {
        return (bool) DB::table('admins')->where('id', $admin->id)->value('is_branch_manager');
    }

    /** @test */
    public function it_assigns_an_admin_to_a_branch(): void
    {
        $branch = $this->makeBranch();
        $admin = $this->globalAdmin();

        $this->postJson(route('branches.assign-admin', $branch), ['admin_id' => $admin->id])
            ->assertOk();

        $this->assertSame($branch->id, (int) $admin->fresh()->branch_id);
    }

    /** @test */
    public function assigning_requires_an_existing_admin(): void
    {
        $branch = $this->makeBranch();

        $this->postJson(route('branches.assign-admin', $branch), ['admin_id' => 99999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors('admin_id');
    }

    /** @test */
    public function it_removes_an_admin_from_a_branch(): void
    {
        $branch = $this->makeBranch();
        $admin = $this->branchAdmin($branch);

        $this->postJson(route('branches.remove-admin', $branch), ['admin_id' => $admin->id])
            ->assertOk();

        $this->assertNull($admin->fresh()->branch_id);
    }

    /** @test */
    public function removing_an_admin_who_belongs_to_another_branch_is_a_no_op(): void
    {
        $branch = $this->makeBranch();
        $otherBranch = $this->makeBranch();
        $admin = $this->branchAdmin($otherBranch);

        $this->postJson(route('branches.remove-admin', $branch), ['admin_id' => $admin->id])
            ->assertOk();

        $this->assertSame(
            $otherBranch->id,
            (int) $admin->fresh()->branch_id,
            'One branch must not be able to detach another branch\'s staff.'
        );
    }

    /** @test */
    public function it_assigns_a_branch_manager_and_moves_them_into_the_branch(): void
    {
        $branch = $this->makeBranch();
        $admin = $this->globalAdmin();

        $this->postJson(route('branches.assign-manager', $branch), ['admin_id' => $admin->id])
            ->assertOk();

        $this->assertSame($branch->id, (int) $admin->fresh()->branch_id);
        $this->assertTrue($this->isManager($admin->fresh()));
    }

    /** @test */
    public function a_branch_has_only_one_manager_at_a_time(): void
    {
        $branch = $this->makeBranch();
        $first = $this->globalAdmin();
        $second = $this->globalAdmin();

        $this->postJson(route('branches.assign-manager', $branch), ['admin_id' => $first->id])->assertOk();
        $this->postJson(route('branches.assign-manager', $branch), ['admin_id' => $second->id])->assertOk();

        $this->assertFalse($this->isManager($first->fresh()), 'The previous manager must be demoted.');
        $this->assertTrue($this->isManager($second->fresh()));
    }

    /** @test */
    public function sending_no_admin_id_clears_the_branch_manager(): void
    {
        $branch = $this->makeBranch();
        $admin = $this->globalAdmin();

        $this->postJson(route('branches.assign-manager', $branch), ['admin_id' => $admin->id])->assertOk();
        $this->postJson(route('branches.assign-manager', $branch), ['admin_id' => null])->assertOk();

        $this->assertFalse($this->isManager($admin->fresh()));
    }

    /** @test */
    public function promoting_a_manager_in_one_branch_leaves_another_branchs_manager_alone(): void
    {
        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $managerA = $this->globalAdmin();
        $managerB = $this->globalAdmin();

        $this->postJson(route('branches.assign-manager', $branchA), ['admin_id' => $managerA->id])->assertOk();
        $this->postJson(route('branches.assign-manager', $branchB), ['admin_id' => $managerB->id])->assertOk();

        $this->assertTrue($this->isManager($managerA->fresh()));
        $this->assertTrue($this->isManager($managerB->fresh()));
    }

    /** @test */
    public function the_available_admins_list_excludes_staff_already_in_the_branch(): void
    {
        $branch = $this->makeBranch();
        $inBranch = $this->branchAdmin($branch);
        $elsewhere = $this->branchAdmin($this->makeBranch());
        $unassigned = $this->globalAdmin();

        $ids = collect($this->getJson(route('branches.available-admins', $branch))->assertOk()->json())
            ->pluck('id');

        $this->assertNotContains($inBranch->id, $ids);
        $this->assertContains($elsewhere->id, $ids);
        $this->assertContains($unassigned->id, $ids);
    }

    /** @test */
    public function the_available_admins_list_excludes_suspended_staff(): void
    {
        $branch = $this->makeBranch();
        $suspended = Admin::factory()->global()->suspended()->create();

        $ids = collect($this->getJson(route('branches.available-admins', $branch))->assertOk()->json())
            ->pluck('id');

        $this->assertNotContains($suspended->id, $ids);
    }

    /** @test */
    public function the_branch_admins_list_flags_the_current_manager(): void
    {
        $branch = $this->makeBranch();
        $manager = $this->globalAdmin();
        $other = $this->globalAdmin();

        $this->postJson(route('branches.assign-manager', $branch), ['admin_id' => $manager->id])->assertOk();

        $rows = collect($this->getJson(route('branches.admins', $branch))->assertOk()->json())->keyBy('id');

        $this->assertTrue($rows[$manager->id]['is_selected']);
        $this->assertFalse($rows[$other->id]['is_selected']);
    }

    /** @test */
    public function the_branch_relation_returns_the_staff_of_that_branch_only(): void
    {
        $branch = $this->makeBranch();
        $mine = $this->branchAdmin($branch);
        $theirs = $this->branchAdmin($this->makeBranch());

        $ids = $branch->admins()->pluck('id');

        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($theirs->id, $ids);
    }

    /** @test */
    public function soft_deleted_staff_are_excluded_from_the_branch_relation(): void
    {
        $branch = $this->makeBranch();
        $admin = $this->branchAdmin($branch);
        $admin->delete();

        $this->assertNotContains($admin->id, $branch->admins()->pluck('id'));
    }

    public function test_branch_employee_count_includes_customer_service_users(): void
    {
        $branch = $this->makeBranch();
        $admin = $this->branchAdmin($branch);
        $support = Admin::factory()->forBranch($branch)->count(2)->create([
            'admin_type' => 'customer_service',
        ]);
        $this->branchAdmin($this->makeBranch());
        Admin::factory()->forBranch($branch)->create([
            'admin_type' => 'customer_service',
            'deleted_at' => now(),
        ]);

        $expectedIds = $support->pluck('id')->push($admin->id)->sort()->values()->all();
        $this->assertSame($expectedIds, $branch->admins()->orderBy('id')->pluck('id')->all());

        $this->get(route('branches.index'))
            ->assertOk()
            ->assertViewHas('branches', function ($branches) use ($branch, $expectedIds) {
                $listedBranch = $branches->getCollection()->firstWhere('id', $branch->id);

                return $listedBranch !== null
                    && $listedBranch->admins->pluck('id')->sort()->values()->all() === $expectedIds;
            });
    }
}
