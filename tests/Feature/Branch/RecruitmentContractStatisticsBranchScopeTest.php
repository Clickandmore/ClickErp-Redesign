<?php

namespace Tests\Feature\Branch;

use App\Models\Permission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

class RecruitmentContractStatisticsBranchScopeTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        $this->enableBranchesModule();
    }

    private function grantViewPermission($admin): void
    {
        Permission::withoutGlobalScopes()->updateOrCreate(
            ['name' => \App\NewPermKeys::REC_CONTRACTS_VIEW, 'guard_name' => 'admin'],
            [
                'display_name' => 'View recruitment contracts',
                'group_name' => 'recruitment_contracts',
                'section_name' => 'operations_management',
                'is_active' => true,
            ]
        );

        $admin->givePermissionTo(\App\NewPermKeys::REC_CONTRACTS_VIEW);
    }

    private function makeContract(int $branchId, string $status): int
    {
        return DB::table('contracts')->insertGetId([
            'branch_id' => $branchId,
            'status' => $status,
            'type' => 'recruitment',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function statistics_endpoint_only_counts_the_logged_in_branch(): void
    {
        $this->makeBranch(['is_main' => true]);
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $viewer = $this->branchAdmin($own);
        $this->grantViewPermission($viewer);
        $this->makeContract($own->id, 'new');
        $this->makeContract($own->id, 'back');
        $this->makeContract($other->id, 'new');

        $response = $this->actingAsAdmin($viewer)->getJson(route('RecruitmentContract.statistics'));

        $response->assertOk()
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.current_total', 1)
            ->assertJsonPath('data.stage_new', 1)
            ->assertJsonPath('data.stage_back', 1);
    }

    /** @test */
    public function sidebar_contract_badges_only_count_the_logged_in_branch(): void
    {
        $this->makeBranch(['is_main' => true]);
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $viewer = $this->branchAdmin($own);
        $this->grantViewPermission($viewer);
        $this->makeContract($own->id, 'new');
        $this->makeContract($other->id, 'new');
        $this->makeContract($other->id, 'back');
        $response = $this->actingAsAdmin($viewer)->get(
            route('RecruitmentContract.index', ['status' => 'current'])
        );
        $html = $response->assertOk()->getContent();
        $document = new \DOMDocument();
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $currentBadge = $xpath->query('//ul[@id="contracts"]//a[contains(@href, "status=current")]//span[contains(@class, "badge")]')->item(0);
        $backBadge = $xpath->query('//ul[@id="contracts"]//a[contains(@href, "status=back")]//span[contains(@class, "badge")]')->item(0);

        $this->assertNotNull($currentBadge);
        $this->assertNotNull($backBadge);
        $this->assertSame('1', trim($currentBadge->textContent));
        $this->assertSame('0', trim($backBadge->textContent));
    }
}
