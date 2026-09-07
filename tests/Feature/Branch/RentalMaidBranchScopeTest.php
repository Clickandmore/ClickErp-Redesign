<?php

namespace Tests\Feature\Branch;

use App\Http\Actions\ingaz\MaidAction;
use App\Models\ingaz\Maid;
use App\Models\Permission;
use App\Models\RentalContract;
use App\Models\ingaz\Order;
use App\Support\BranchSharingSettings;
use App\Services\BranchDataSharingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

class RentalMaidBranchScopeTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        $this->enableBranchesModule();
        BranchSharingSettings::flush();
    }

    /** @test */
    public function rental_maid_page_is_scoped_to_the_current_branch(): void
    {
        $own = $this->makeBranch(['is_main' => true]);
        $other = $this->makeBranch();
        $admin = $this->branchAdmin($own);
        $this->grant($admin, \App\PermKeys::MAIDS_RENTAL_VIEW);

        $ownMaid = $this->makeRentalMaid('Own rental maid', $own->id);
        $otherMaid = $this->makeRentalMaid('Other rental maid', $other->id);

        $response = $this->actingAsAdmin($admin)->get(
            route('maids.rental.index'),
            ['X-Requested-With' => 'XMLHttpRequest']
        );

        $response->assertOk()
            ->assertSee($ownMaid->name, false)
            ->assertDontSee($otherMaid->name, false);
    }

    /** @test */
    public function unassigned_maid_visibility_follows_the_company_sharing_setting(): void
    {
        $main = $this->makeBranch(['is_main' => true]);
        $own = $this->makeBranch();
        $admin = $this->branchAdmin($own);
        $maid = $this->makeRentalMaid('Unassigned shared maid', $main->id);
        $maid->updateQuietly(['branch_id' => null]);

        $this->actingAsAdmin($admin);

        BranchSharingSettings::persist(['share_maids' => false]);
        BranchSharingSettings::flush();
        $this->assertFalse(Maid::query()->whereKey($maid->id)->exists());

        BranchSharingSettings::persist(['share_maids' => true]);
        BranchSharingSettings::flush();
        $this->assertTrue(Maid::query()->whereKey($maid->id)->exists());
    }

    /** @test */
    public function maid_view_all_permission_bypasses_maid_branch_isolation(): void
    {
        $own = $this->makeBranch(['is_main' => true]);
        $other = $this->makeBranch();
        $admin = $this->branchAdmin($own);
        $this->grant($admin, \App\PermKeys::MAIDS_VIEW_ALL_BRANCHES);
        $maid = $this->makeRentalMaid('Cross branch permission maid', $other->id);

        BranchSharingSettings::persist(['share_maids' => false]);
        BranchSharingSettings::flush();

        $this->actingAsAdmin($admin);
        $this->assertTrue(Maid::query()->whereKey($maid->id)->exists());
    }

    /** @test */
    public function a_source_policy_shares_assigned_maids_with_all_other_branches(): void
    {
        $target = $this->makeBranch();
        $source = $this->makeBranch();
        $unrelated = $this->makeBranch();
        $admin = $this->branchAdmin($target);
        $sharedMaid = $this->makeRentalMaid('Directionally shared maid', $source->id);
        $hiddenMaid = $this->makeRentalMaid('Unrelated branch maid', $unrelated->id);

        BranchSharingSettings::persist(['share_maids' => false]);
        BranchSharingSettings::flush();
        app(BranchDataSharingService::class)->sync([
            $source->id => [
                BranchDataSharingService::MAIDS => true,
            ],
        ]);

        $this->actingAsAdmin($admin);

        $this->assertTrue(Maid::query()->whereKey($sharedMaid->id)->exists());
        $this->assertFalse(Maid::query()->whereKey($hiddenMaid->id)->exists());
    }

    /** @test */
    public function view_all_permission_allows_filtering_and_the_dedicated_permission_allows_moving_a_maid(): void
    {
        $own = $this->makeBranch(['is_main' => true]);
        $other = $this->makeBranch();
        $admin = $this->branchAdmin($own);
        foreach ([
            \App\PermKeys::MAIDS_RENTAL_VIEW,
            \App\PermKeys::BRANCHES_VIEW_ALL,
            \App\PermKeys::MAIDS_CHANGE_BRANCH,
        ] as $permission) {
            $this->grant($admin, $permission);
        }

        $maid = $this->makeRentalMaid('Movable rental maid', $other->id);

        $this->actingAsAdmin($admin)->get(
            route('maids.rental.index', ['branch_id' => $other->id]),
            ['X-Requested-With' => 'XMLHttpRequest']
        )->assertOk()->assertSee($maid->name, false);

        $this->postJson(route('maids.branch.update', $maid), ['branch_id' => $own->id])
            ->assertOk()
            ->assertJsonPath('branch.id', $own->id);

        $this->assertSame($own->id, (int) $maid->fresh()->branch_id);
    }

    /** @test */
    public function rental_orders_and_direct_rental_contracts_inherit_the_maid_branch(): void
    {
        $adminBranch = $this->makeBranch(['is_main' => true]);
        $maidBranch = $this->makeBranch();
        $admin = $this->branchAdmin($adminBranch);
        $maid = $this->makeRentalMaid('Branch source maid', $maidBranch->id);
        $userId = $this->makeClient();

        $this->actingAsAdmin($admin);

        $order = Order::query()->create([
            'user_id' => $userId,
            'maid_id' => $maid->id,
            'job_type_id' => 2,
            'status' => 'new',
            'order_code' => 'BRANCH-' . random_int(10000, 99999),
        ]);
        $contract = RentalContract::query()->create([
            'user_id' => $userId,
            'maid_id' => $maid->id,
            'status' => 'new',
        ]);

        $this->assertSame($maidBranch->id, (int) $order->branch_id);
        $this->assertSame($maidBranch->id, (int) $contract->branch_id);
    }

    /** @test */
    public function rental_order_page_is_scoped_to_the_current_branch(): void
    {
        $own = $this->makeBranch(['is_main' => true]);
        $other = $this->makeBranch();
        $admin = $this->branchAdmin($own);
        $this->grant($admin, \App\PermKeys::RENTAL_ORDERS_VIEW);
        $userId = $this->makeClient();

        $ownMaid = $this->makeRentalMaid('Own order maid', $own->id);
        $otherMaid = $this->makeRentalMaid('Other order maid', $other->id);
        $ownOrder = Order::query()->create([
            'user_id' => $userId,
            'maid_id' => $ownMaid->id,
            'job_type_id' => 2,
            'status' => 'new',
            'order_code' => 'OWN-' . random_int(10000, 99999),
        ]);
        $otherOrder = Order::query()->create([
            'user_id' => $userId,
            'maid_id' => $otherMaid->id,
            'job_type_id' => 2,
            'status' => 'new',
            'order_code' => 'OTHER-' . random_int(10000, 99999),
        ]);

        $this->actingAsAdmin($admin)->get(
            route('rental-orders.index'),
            ['X-Requested-With' => 'XMLHttpRequest']
        )->assertOk()
            ->assertSee($ownOrder->order_code, false)
            ->assertDontSee($otherOrder->order_code, false);
    }

    /** @test */
    public function public_rental_cv_query_can_be_filtered_by_branch(): void
    {
        $own = $this->makeBranch(['is_main' => true]);
        $other = $this->makeBranch();
        $mine = $this->makeRentalMaid('Public own rental maid', $own->id);
        $theirs = $this->makeRentalMaid('Public other rental maid', $other->id);

        $request = Request::create('/client/all-workers', 'GET', [
            'type' => 'egar',
            'branch_id' => $own->id,
            'per_page' => 100,
        ]);

        $ids = app(MaidAction::class)->paginate($request)->getCollection()->pluck('id');

        $this->assertTrue($ids->contains($mine->id));
        $this->assertFalse($ids->contains($theirs->id));
    }

    /** @test */
    public function disabled_branches_module_removes_every_branch_element_from_the_rental_maids_page(): void
    {
        $branch = $this->makeBranch(['is_main' => true]);
        $admin = $this->branchAdmin($branch);
        $this->grant($admin, \App\PermKeys::MAIDS_RENTAL_VIEW);
        $this->makeRentalMaid('Rental maid while branches are disabled', $branch->id);
        $this->enableBranchesModule(false);

        $this->actingAsAdmin($admin)
            ->get(route('maids.rental.index'))
            ->assertOk()
            ->assertDontSee('id="branch_id"', false)
            ->assertDontSee("data: 'branch_name'", false)
            ->assertDontSee('change-maid-branch', false);

        $response = $this->get(route('maids.rental.index'), ['X-Requested-With' => 'XMLHttpRequest']);
        $response->assertOk();

        $firstRow = (array) data_get($response->json(), 'data.0', []);
        $this->assertArrayNotHasKey('branch_id', $firstRow);
        $this->assertArrayNotHasKey('branch_name', $firstRow);
    }

    private function grant($admin, string $permission): void
    {
        Permission::withoutGlobalScopes()->updateOrCreate(
            ['name' => $permission, 'guard_name' => 'admin'],
            [
                'display_name' => $permission,
                'group_name' => 'maids_rental',
                'section_name' => 'cv_management',
                'is_active' => true,
            ]
        );

        $admin->givePermissionTo($permission);
    }

    private function makeRentalMaid(string $name, int $branchId): Maid
    {
        DB::table('job_types')->updateOrInsert(
            ['id' => 2],
            ['image' => '', 'created_at' => now(), 'updated_at' => now()]
        );

        $nationalityId = DB::table('nationalities')->insertGetId([
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $religionId = DB::table('religions')->insertGetId([
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $maidId = DB::table('maids')->insertGetId([
            'name' => $name,
            'passport_num' => 'BR-' . str_replace('.', '', uniqid('', true)),
            'nationality_id' => $nationalityId,
            'religion_id' => $religionId,
            'age' => 30,
            'cv_file' => '',
            'height' => '',
            'weight' => '',
            'status' => 'available',
            'available' => true,
            'is_show' => true,
            'branch_id' => $branchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('maid_jobs')->insert([
            'maid_id' => $maidId,
            'job_type_id' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // The fixture builds maids across several branches while acting as one
        // branch's staff, so it has to look past the branch scope on purpose.
        return Maid::query()->withoutBranchScope()->findOrFail($maidId);
    }

    private function makeClient(): int
    {
        return DB::table('users')->insertGetId([
            'name' => 'Rental branch client',
            'phone_code' => '+966',
            'phone' => '5' . random_int(10000000, 99999999),
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
