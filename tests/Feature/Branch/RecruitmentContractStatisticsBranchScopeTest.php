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
    public function statistics_follow_status_and_reset_to_all_contracts(): void
    {
        $own = $this->makeBranch();
        $viewer = $this->branchAdmin($own);
        $this->grantViewPermission($viewer);
        $this->makeContract($own->id, 'new');
        $this->makeContract($own->id, 'back');

        $this->actingAsAdmin($viewer)->getJson(route('RecruitmentContract.statistics', ['status' => 'new']))
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.stage_new', 1)
            ->assertJsonPath('data.stage_back', 0);

        $this->getJson(route('RecruitmentContract.statistics', ['status' => 'arrival']))
            ->assertOk()
            ->assertJsonPath('data.total', 0);

        $this->getJson(route('RecruitmentContract.statistics'))
            ->assertOk()
            ->assertJsonPath('data.total', 2);
    }

    public function test_company_contracts_are_separate_in_statistics_and_exports(): void
    {
        $branch = $this->makeBranch();
        $viewer = $this->branchAdmin($branch);
        $this->grantViewPermission($viewer);
        $company = \App\Models\ingaz\User::withoutEvents(fn () => \App\Models\ingaz\User::create([
            'name' => 'شركة أسس الإنجاز للاستقدام',
            'phone' => 'company-split-' . uniqid(),
            'password' => bcrypt('test'),
        ]));
        $companyContract = $this->makeContract($branch->id, 'new');
        DB::table('contracts')->where('id', $companyContract)->update(['user_id' => $company->id]);
        $clientContract = $this->makeContract($branch->id, 'new');

        // Soft-deleting the internal client must not reclassify its contracts.
        DB::table('users')->where('id', $company->id)->update(['deleted_at' => now()]);

        $this->actingAsAdmin($viewer)->getJson(route('RecruitmentContract.statistics'))
            ->assertOk()->assertJsonPath('data.total', 1);
        $this->getJson(route('RecruitmentContract.statistics', ['company_contracts' => 1]))
            ->assertOk()->assertJsonPath('data.total', 1);

        $method = new \ReflectionMethod(\App\Http\Controllers\ingaz\RecruitmentContractController::class, 'recruitmentContractExportQuery');
        $method->setAccessible(true);
        $controller = new \App\Http\Controllers\ingaz\RecruitmentContractController();
        foreach ([0 => [$clientContract], 1 => [$companyContract]] as $mode => $expected) {
            $query = $method->invoke($controller, \Illuminate\Http\Request::create('/', 'GET', ['company_contracts' => $mode]));
            $this->assertEquals($expected, $query->pluck('contracts.id')->all());
        }
    }

    /** @test */
    public function statistics_follow_warranty_and_marketer_filters_together(): void
    {
        $own = $this->makeBranch();
        $viewer = $this->branchAdmin($own);
        $otherMarketer = $this->branchAdmin($own);
        $this->grantViewPermission($viewer);

        foreach ([
            [$viewer->id, now()->addDays(10)->toDateString()],
            [$viewer->id, now()->subDays(10)->toDateString()],
            [$otherMarketer->id, now()->addDays(10)->toDateString()],
        ] as [$marketerId, $warrantyEnd]) {
            $id = $this->makeContract($own->id, 'arrival');
            DB::table('contracts')->where('id', $id)->update([
                'marketer_id' => $marketerId,
                'warranty_end_date' => $warrantyEnd,
            ]);
        }

        $this->actingAsAdmin($viewer)->getJson(route('RecruitmentContract.statistics', [
            'status' => 'arrival',
            'warranty_status' => 'active',
            'marketer_id' => $viewer->id,
        ]))
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.stage_arrival', 1)
            ->assertJsonPath('data.warranty_active', 1);
    }

    /** @test */
    public function sidebar_current_count_matches_statistics_for_legacy_status_and_completed_arrivals(): void
    {
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $viewer = $this->branchAdmin($own);
        $this->grantViewPermission($viewer);
        $this->makeContract($own->id, 'new');
        $this->makeContract($own->id, 'sari');
        $this->makeContract($other->id, 'sari');
        $arrivedId = $this->makeContract($own->id, 'tazker');
        DB::table('travel_histories')->insert([
            'order_id' => $arrivedId,
            'branch_id' => $own->id,
            'user_id' => 1,
            'maid_id' => 1,
            'travel_type' => 'arrival',
            'travel_with' => 'none',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAsAdmin($viewer)->getJson(route('RecruitmentContract.statistics'))
            ->assertOk()
            ->assertJsonPath('data.current_total', 2);

        $counts = app(\App\Services\RecruitmentContractSidebarCountService::class)
            ->counts(request());
        $this->assertSame(2, $counts['are_valid']);
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
