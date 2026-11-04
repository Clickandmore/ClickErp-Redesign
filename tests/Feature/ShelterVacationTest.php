<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\Permission;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

class ShelterVacationTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        $this->enableBranchesModule();
    }

    public function test_worker_can_leave_for_vacation_and_return_to_shelter(): void
    {
        $branch = $this->makeBranch(['is_main' => true]);
        $admin = $this->branchAdmin($branch);
        $this->grant($admin, PermKeys::SHELTER_CONTRACT_REMOVE_FROM_SHELTER_EDIT);

        $contractId = DB::table('contracts')->insertGetId([
            'branch_id' => $branch->id,
            'status' => 'shelter',
            'type' => 'recruitment',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ContractInfo::create([
            'contract_id' => $contractId,
            'wants_to_work' => 'yes',
            'is_outside_shelter' => false,
        ]);

        $this->actingAsAdmin($admin)
            ->post(route('ShelterContract.vacation', $contractId))
            ->assertOk()
            ->assertJsonPath('success', true);

        $contract = Contract::with('info')->findOrFail($contractId);
        $this->assertSame('shelter', $contract->status);
        $this->assertTrue($contract->info->is_outside_shelter);
        $this->assertSame('vacation', $contract->info->outside_shelter_reason);
        $this->assertFalse(Contract::insideShelter()->whereKey($contractId)->exists());
        $this->assertTrue(Contract::outsideShelter()->whereKey($contractId)->exists());

        $this->actingAsAdmin($admin)
            ->post(route('ShelterContract.returnFromVacation', $contractId))
            ->assertOk()
            ->assertJsonPath('success', true);

        $contract->refresh()->load('info');
        $this->assertSame('shelter', $contract->status);
        $this->assertFalse($contract->info->is_outside_shelter);
        $this->assertNull($contract->info->outside_shelter_reason);
        $this->assertNotNull($contract->info->maid_date_back);
        $this->assertTrue(Contract::insideShelter()->whereKey($contractId)->exists());
    }

    public function test_outside_shelter_name_search_handles_extra_spaces_and_combines_with_passport(): void
    {
        $maidId = DB::table('maids')->insertGetId([
            'name' => 'NURHAYANI  MUHAMMAD IDRUS', 'passport_num' => 'SEARCH-' . uniqid(),
            'age' => 30, 'cv_file' => '', 'height' => '', 'weight' => '',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $contractId = DB::table('contracts')->insertGetId([
            'maid_id' => $maidId, 'status' => 'shelter', 'type' => 'recruitment',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        ContractInfo::create([
            'contract_id' => $contractId, 'wants_to_work' => 'yes',
            'is_outside_shelter' => true, 'outside_shelter_reason' => 'vacation',
        ]);
        $controller = app(\App\Http\Controllers\ingaz\ShelterContractController::class);
        $filter = new \ReflectionMethod($controller, 'applyFilters');
        $filter->setAccessible(true);
        foreach (['NURHAYANI MUHAMMAD IDRUS', '  NURHAYANI   MUHAMMAD IDRUS  ', 'MUHAMMAD IDRUS'] as $name) {
            $query = Contract::outsideShelter()->whereKey($contractId);
            $filter->invoke($controller, $query, \Illuminate\Http\Request::create('/', 'GET', ['maid_name' => $name]));
            $this->assertTrue($query->exists());
        }
        $query = Contract::outsideShelter()->whereKey($contractId);
        $filter->invoke($controller, $query, \Illuminate\Http\Request::create('/', 'GET', [
            'maid_name' => 'NURHAYANI MUHAMMAD IDRUS', 'passport_num' => 'WRONG-PASSPORT',
        ]));
        $this->assertFalse($query->exists());
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
}
