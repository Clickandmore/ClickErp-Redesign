<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\Contract;
use App\Models\Permission;
use App\Services\BranchAccessService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

/**
 * Read-side branch scoping: BranchAccessService is what every list screen calls to
 * decide which rows an admin may see.
 */
class BranchAccessServiceTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithBranches;

    private BranchAccessService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        $this->enableBranchesModule();
        $this->service = app(BranchAccessService::class);
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
    public function it_reports_the_branch_id_of_a_branch_scoped_admin(): void
    {
        $branch = $this->makeBranch();
        $admin = $this->branchAdmin($branch);

        $this->assertSame($branch->id, $this->service->adminBranchId($admin));
    }

    /** @test */
    public function an_admin_without_a_branch_has_no_branch_id(): void
    {
        $this->assertNull($this->service->adminBranchId($this->globalAdmin()));
    }

    /** @test */
    public function a_branch_admin_cannot_override_their_branch_via_the_request(): void
    {
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $admin = $this->branchAdmin($own);

        $request = Request::create('/', 'GET', ['branch_id' => $other->id]);

        $this->assertSame(
            $own->id,
            $this->service->selectedBranchId($request, $admin),
            'A branch-scoped admin must stay pinned to their own branch.'
        );
    }

    /** @test */
    public function the_view_all_permission_allows_a_branch_admin_to_select_another_branch(): void
    {
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $admin = $this->branchAdmin($own);
        $this->grantViewAllBranchesPermission($admin);

        $request = Request::create('/', 'GET', ['branch_id' => $other->id]);

        $this->assertTrue($this->service->canViewAllBranches($admin));
        $this->assertSame($other->id, $this->service->selectedBranchId($request, $admin));
        $this->assertCount(2, $this->service->selectorBranches($admin));
    }

    /** @test */
    public function the_view_all_permission_allows_a_branch_admin_to_view_all_rows(): void
    {
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $admin = $this->branchAdmin($own);
        $this->grantViewAllBranchesPermission($admin);

        $ownEmployee = $this->branchAdmin($own)->id;
        $otherEmployee = $this->branchAdmin($other)->id;
        $query = DB::table('admins')->whereIn('id', [$ownEmployee, $otherEmployee]);

        $ids = $this->service
            ->applyToQuery($query, Request::create('/'), 'admins', null, $admin)
            ->pluck('id')
            ->all();

        $this->assertEqualsCanonicalizing([$ownEmployee, $otherEmployee], $ids);
    }

    /** @test */
    public function a_global_admin_may_select_any_existing_branch(): void
    {
        $branch = $this->makeBranch();
        $admin = $this->globalAdmin();

        $request = Request::create('/', 'GET', ['branch_id' => $branch->id]);

        $this->assertSame($branch->id, $this->service->selectedBranchId($request, $admin));
    }

    /** @test */
    public function selecting_a_branch_that_does_not_exist_falls_back_to_all_branches(): void
    {
        $admin = $this->globalAdmin();
        $missingId = ((int) Branch::query()->max('id')) + 5000;

        $request = Request::create('/', 'GET', ['branch_id' => $missingId]);

        $this->assertNull($this->service->selectedBranchId($request, $admin));
    }

    /** @test */
    public function no_branch_is_selected_when_the_module_is_disabled(): void
    {
        $branch = $this->makeBranch();
        $admin = $this->branchAdmin($branch);
        $this->enableBranchesModule(false);

        $request = Request::create('/', 'GET');

        $this->assertNull($this->service->selectedBranchId($request, $admin));
    }

    /** @test */
    public function the_branch_selector_only_offers_a_branch_admin_their_own_branch(): void
    {
        $own = $this->makeBranch();
        $this->makeBranch();
        $admin = $this->branchAdmin($own);

        $branches = $this->service->selectorBranches($admin);

        $this->assertCount(1, $branches);
        $this->assertSame($own->id, $branches->first()->id);
    }

    /** @test */
    public function the_branch_selector_hides_inactive_branches_from_global_admins(): void
    {
        $active = $this->makeBranch();
        $inactive = Branch::factory()->inactive()->create();

        $ids = $this->service->selectorBranches($this->globalAdmin())->pluck('id');

        $this->assertContains($active->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
    }

    /** @test */
    public function selector_data_marks_a_branch_admin_as_unable_to_view_all_branches(): void
    {
        $branch = $this->makeBranch();
        $data = $this->service->selectorData(Request::create('/'), $this->branchAdmin($branch));

        $this->assertTrue($data['enabled']);
        $this->assertSame($branch->id, $data['forced_branch_id']);
        $this->assertFalse($data['can_view_all_branches']);
    }

    /** @test */
    public function selector_data_lets_a_global_admin_view_all_branches(): void
    {
        $data = $this->service->selectorData(Request::create('/'), $this->globalAdmin());

        $this->assertNull($data['forced_branch_id']);
        $this->assertTrue($data['can_view_all_branches']);
    }

    /** @test */
    public function applying_the_filter_restricts_a_branch_admin_to_their_own_rows_only(): void
    {
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $admin = $this->branchAdmin($own);

        $mine = $this->makeBranch(['name_ar' => 'داخل الفرع']);
        $theirs = $this->makeBranch(['name_ar' => 'خارج الفرع']);

        // `branches` itself has no branch_id column, so scope over `admins`, which does.
        $inMyBranch = $this->branchAdmin($own)->id;
        $inOtherBranch = $this->branchAdmin($other)->id;
        $unassigned = $this->globalAdmin()->id;

        $query = DB::table('admins')->whereIn('id', [$inMyBranch, $inOtherBranch, $unassigned]);
        $ids = $this->service
            ->applyToQuery($query, Request::create('/'), 'admins', null, $admin)
            ->pluck('id')
            ->all();

        $this->assertContains($inMyBranch, $ids);
        $this->assertNotContains($unassigned, $ids, 'Unassigned rows must not leak into a branch-scoped account.');
        $this->assertNotContains($inOtherBranch, $ids);

        unset($mine, $theirs);
    }

    /** @test */
    public function the_main_branch_includes_legacy_unassigned_rows(): void
    {
        $main = $this->makeBranch(['is_main' => true]);
        $other = $this->makeBranch();
        $admin = $this->branchAdmin($main);
        $inMainBranch = $this->branchAdmin($main)->id;
        $inOtherBranch = $this->branchAdmin($other)->id;
        $unassigned = $this->globalAdmin()->id;

        $query = DB::table('admins')->whereIn('id', [$inMainBranch, $inOtherBranch, $unassigned]);
        $ids = $this->service
            ->applyToQuery($query, Request::create('/'), 'admins', null, $admin)
            ->pluck('id')
            ->all();

        $this->assertContains($inMainBranch, $ids);
        $this->assertContains($unassigned, $ids);
        $this->assertNotContains($inOtherBranch, $ids);
        $this->assertTrue($this->service->canAccessModel($this->globalAdmin(), $admin));
    }

    /** @test */
    public function a_legacy_contract_follows_its_responsible_employee_branch(): void
    {
        $main = $this->makeBranch(['is_main' => true]);
        $riyadh = $this->makeBranch();
        $marketer = $this->branchAdmin($riyadh);
        $contractId = DB::table('contracts')->insertGetId([
            'status' => 'new',
            'marketer_id' => $marketer->id,
            'branch_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $riyadhIds = $this->service
            ->applyBranchIdToQuery(DB::table('contracts')->where('id', $contractId), $riyadh->id, 'contracts')
            ->pluck('id')
            ->all();
        $mainIds = $this->service
            ->applyBranchIdToQuery(DB::table('contracts')->where('id', $contractId), $main->id, 'contracts')
            ->pluck('id')
            ->all();

        $this->assertSame([$contractId], $riyadhIds);
        $this->assertSame([], $mainIds);
        $this->assertTrue($this->service->canAccessModel(Contract::findOrFail($contractId), $marketer));
    }

    /** @test */
    public function a_legacy_contract_whose_employee_has_no_branch_belongs_to_main(): void
    {
        $main = $this->makeBranch(['is_main' => true]);
        $other = $this->makeBranch();
        $unassignedEmployee = $this->globalAdmin();
        $contractId = DB::table('contracts')->insertGetId([
            'status' => 'new',
            'marketer_id' => $unassignedEmployee->id,
            'branch_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $mainIds = $this->service
            ->applyBranchIdToQuery(DB::table('contracts')->where('id', $contractId), $main->id, 'contracts')
            ->pluck('id')
            ->all();
        $otherIds = $this->service
            ->applyBranchIdToQuery(DB::table('contracts')->where('id', $contractId), $other->id, 'contracts')
            ->pluck('id')
            ->all();

        $this->assertSame([$contractId], $mainIds);
        $this->assertSame([], $otherIds);
        $this->assertTrue($this->service->canAccessModel(
            Contract::findOrFail($contractId),
            $this->branchAdmin($main)
        ));
    }

    /** @test */
    public function branch_inference_honours_query_table_aliases(): void
    {
        $main = $this->makeBranch(['is_main' => true]);

        $ordersCount = $this->service
            ->applyBranchIdToQuery(
                DB::table('orders as gender_orders')->whereRaw('1 = 0'),
                $main->id,
                'orders',
                'gender_orders.branch_id'
            )
            ->count();
        $contractsCount = $this->service
            ->applyBranchIdToQuery(
                DB::table('contracts as dashboard_contracts')->whereRaw('1 = 0'),
                $main->id,
                'contracts',
                'dashboard_contracts.branch_id'
            )
            ->count();

        $this->assertSame(0, $ordersCount);
        $this->assertSame(0, $contractsCount);
    }

    /** @test */
    public function a_global_admin_selecting_a_branch_sees_only_that_branch(): void
    {
        $this->makeBranch(['is_main' => true]);
        $target = $this->makeBranch();
        $other = $this->makeBranch();

        $inTarget = $this->branchAdmin($target)->id;
        $inOther = $this->branchAdmin($other)->id;
        $unassigned = $this->globalAdmin()->id;

        $request = Request::create('/', 'GET', ['branch_id' => $target->id]);
        $query = DB::table('admins')->whereIn('id', [$inTarget, $inOther, $unassigned]);

        $ids = $this->service
            ->applyToQuery($query, $request, 'admins', null, $this->globalAdmin())
            ->pluck('id')
            ->all();

        $this->assertSame([$inTarget], $ids, 'A deliberate branch pick is exact — null rows are excluded.');
    }

    /** @test */
    public function the_filter_is_a_no_op_for_tables_without_a_branch_column(): void
    {
        $branch = $this->makeBranch();
        $admin = $this->branchAdmin($branch);

        $query = DB::table('branches');
        $before = $query->toSql();

        $after = $this->service->applyToQuery($query, Request::create('/'), 'branches', null, $admin)->toSql();

        $this->assertSame($before, $after);
    }

    /** @test */
    public function the_filter_is_a_no_op_when_the_module_is_disabled(): void
    {
        $branch = $this->makeBranch();
        $admin = $this->branchAdmin($branch);
        $this->enableBranchesModule(false);

        $inOther = $this->branchAdmin($this->makeBranch())->id;
        $query = DB::table('admins')->whereIn('id', [$inOther]);

        $ids = $this->service
            ->applyToQuery($query, Request::create('/'), 'admins', null, $admin)
            ->pluck('id')
            ->all();

        $this->assertSame([$inOther], $ids);
    }

    /** @test */
    public function a_branch_admin_may_open_a_record_from_their_own_branch(): void
    {
        $branch = $this->makeBranch();
        $admin = $this->branchAdmin($branch);
        $record = $this->branchAdmin($branch);

        $this->assertTrue($this->service->canAccessModel($record, $admin));
    }

    /** @test */
    public function a_branch_admin_may_not_open_a_record_from_another_branch(): void
    {
        $admin = $this->branchAdmin($this->makeBranch());
        $foreign = $this->branchAdmin($this->makeBranch());

        $this->assertFalse($this->service->canAccessModel($foreign, $admin));
    }

    /** @test */
    public function a_branch_admin_may_not_open_an_unassigned_record(): void
    {
        $this->makeBranch(['is_main' => true]);
        $admin = $this->branchAdmin($this->makeBranch());

        $this->assertFalse($this->service->canAccessModel($this->globalAdmin(), $admin));
    }

    /** @test */
    public function a_global_admin_may_open_any_record(): void
    {
        $foreign = $this->branchAdmin($this->makeBranch());

        $this->assertTrue($this->service->canAccessModel($foreign, $this->globalAdmin()));
    }

    /** @test */
    public function asserting_access_to_a_foreign_record_aborts_with_403(): void
    {
        $admin = $this->branchAdmin($this->makeBranch());
        $foreign = $this->branchAdmin($this->makeBranch());

        try {
            $this->service->assertCanAccessModel($foreign, $admin);
            $this->fail('Expected a 403 to be raised for a cross-branch record.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }
}
