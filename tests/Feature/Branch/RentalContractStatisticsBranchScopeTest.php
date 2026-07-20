<?php

namespace Tests\Feature\Branch;

use App\Models\ingaz\User;
use App\Models\Permission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

class RentalContractStatisticsBranchScopeTest extends TestCase
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
            ['name' => \App\PermKeys::RENT_CONTRACT_VIEW, 'guard_name' => 'admin'],
            [
                'display_name' => 'View rental contracts',
                'group_name' => 'rental_contracts',
                'section_name' => 'operations_management',
                'is_active' => true,
            ]
        );

        $admin->givePermissionTo(\App\PermKeys::RENT_CONTRACT_VIEW);
    }

    private function makeClient(int $branchId): User
    {
        return User::query()->create([
            'name' => 'Rental statistics client',
            'phone_code' => '+966',
            'phone' => '5' . random_int(10000000, 99999999),
            'password' => bcrypt('password'),
            'register_from' => 'dashboard',
            'is_blocked' => 0,
            'branch_id' => $branchId,
        ]);
    }

    private function makeContract(int $userId, int $branchId, string $status, ?string $closureType = null): int
    {
        return DB::table('rental_contracts')->insertGetId([
            'user_id' => $userId,
            'branch_id' => $branchId,
            'status' => $status,
            'closure_type' => $closureType,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function rental_contract_statistics_only_count_the_logged_in_branch(): void
    {
        $this->makeBranch(['is_main' => true]);
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $viewer = $this->branchAdmin($own);
        $this->grantViewPermission($viewer);
        $client = $this->makeClient($own->id);

        $this->makeContract($client->id, $own->id, 'active');
        $this->makeContract($client->id, $own->id, 'completed', 'available');
        $this->makeContract($client->id, $own->id, 'completed', 'transferred');
        $this->makeContract($client->id, $other->id, 'active');
        $this->makeContract($client->id, $other->id, 'cancelled');

        $response = $this->actingAsAdmin($viewer)->getJson(route('RentContract.statistics'));

        $response->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonPath('active', 1)
            ->assertJsonPath('completed', 2)
            ->assertJsonPath('available', 1)
            ->assertJsonPath('transferred', 1)
            ->assertJsonPath('cancelled', 0);
    }
}
