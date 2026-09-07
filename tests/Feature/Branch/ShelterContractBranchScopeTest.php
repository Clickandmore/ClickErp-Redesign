<?php

namespace Tests\Feature\Branch;

use App\Models\Permission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

class ShelterContractBranchScopeTest extends TestCase
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
                'group_name' => 'shelter_contracts',
                'section_name' => 'operations_management',
                'is_active' => true,
            ]
        );

        $admin->givePermissionTo($permission);
    }

    private function makeShelterContract(?int $branchId): int
    {
        return DB::table('contracts')->insertGetId([
            'branch_id' => $branchId,
            'status' => 'shelter',
            'type' => 'recruitment',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function shelter_statistics_only_count_the_logged_in_branch(): void
    {
        $this->makeBranch(['is_main' => true]);
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $viewer = $this->branchAdmin($own);
        $this->grant($viewer, \App\PermKeys::SHELTER_CONTRACT_INSIDE_VIEW);

        $this->makeShelterContract($own->id);
        $this->makeShelterContract($own->id);
        $this->makeShelterContract($other->id);

        $response = $this->actingAsAdmin($viewer)->get(route('ShelterContract.index'));

        $response->assertOk()
            ->assertViewHas('totalContracts', 2)
            ->assertViewHas('undecided', 2);
    }

    /** @test */
    public function view_all_permission_can_filter_shelter_statistics_by_branch(): void
    {
        $this->makeBranch(['is_main' => true]);
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $viewer = $this->branchAdmin($own);
        $this->grant($viewer, \App\PermKeys::SHELTER_CONTRACT_INSIDE_VIEW);
        $this->grant($viewer, \App\PermKeys::BRANCHES_VIEW_ALL);

        $this->makeShelterContract($own->id);
        $this->makeShelterContract($other->id);
        $this->makeShelterContract($other->id);

        $response = $this->actingAsAdmin($viewer)->get(route('ShelterContract.index', [
            'branch_id' => $other->id,
        ]));

        $response->assertOk()
            ->assertViewHas('totalContracts', 2)
            ->assertViewHas('undecided', 2);
    }

    /** @test */
    public function main_branch_includes_unassigned_legacy_shelter_contracts(): void
    {
        $main = $this->makeBranch(['is_main' => true]);
        $other = $this->makeBranch();
        $viewer = $this->branchAdmin($main);
        $this->grant($viewer, \App\PermKeys::SHELTER_CONTRACT_INSIDE_VIEW);

        $this->makeShelterContract($main->id);
        $this->makeShelterContract(null);
        $this->makeShelterContract($other->id);

        $this->actingAsAdmin($viewer)
            ->get(route('ShelterContract.index'))
            ->assertOk()
            ->assertViewHas('totalContracts', 2)
            ->assertViewHas('undecided', 2);
    }

    /** @test */
    public function branch_user_cannot_open_a_shelter_contract_from_another_branch(): void
    {
        $this->makeBranch(['is_main' => true]);
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $viewer = $this->branchAdmin($own);
        $this->grant($viewer, \App\PermKeys::SHELTER_CONTRACT_VIEW);
        $foreignContractId = $this->makeShelterContract($other->id);

        $response = $this->actingAsAdmin($viewer)
            ->get(route('ShelterContract.show', $foreignContractId));

        // Either answer denies access. Which one comes out depends on whether the
        // record is filtered out before the controller's own branch check runs:
        // the branch scope hides it (404), the explicit check refuses it (403).
        // 404 leaks less — it does not confirm the record exists.
        $this->assertContains($response->getStatusCode(), [403, 404], sprintf(
            'A branch user opened another branch\'s shelter contract (HTTP %d).',
            $response->getStatusCode()
        ));
    }
}
