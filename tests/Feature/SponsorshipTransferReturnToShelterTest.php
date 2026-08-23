<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\Permission;
use App\Models\SponsorshipTransfer;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

class SponsorshipTransferReturnToShelterTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        $this->enableBranchesModule();
    }

    public function test_transferred_maid_returns_to_shelter_under_current_sponsor(): void
    {
        $branch = $this->makeBranch(['is_main' => true]);
        $admin = $this->branchAdmin($branch);
        $this->grant($admin, PermKeys::SPONSOR_TRANSFER_CONTROL);

        $suffix = str_replace('.', '', uniqid('', true));
        $nationalityId = DB::table('nationalities')->insertGetId([
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $religionId = DB::table('religions')->insertGetId([
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $maidId = DB::table('maids')->insertGetId([
            'branch_id' => $branch->id,
            'name' => 'Transferred shelter maid',
            'passport_num' => 'TRANSFER-' . $suffix,
            'nationality_id' => $nationalityId,
            'religion_id' => $religionId,
            'cv_file' => '',
            'height' => '',
            'weight' => '',
            'status' => 'contracted',
            'available' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $oldSponsorId = $this->makeClient('Old sponsor', '50' . substr($suffix, -7), $branch->id);
        $currentSponsorId = $this->makeClient('Current sponsor', '55' . substr($suffix, -7), $branch->id);

        $contractId = DB::table('contracts')->insertGetId([
            'branch_id' => $branch->id,
            'user_id' => $currentSponsorId,
            'maid_id' => $maidId,
            'status' => 'completed',
            'type' => 'recruitment',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ContractInfo::create([
            'contract_id' => $contractId,
            'wants_to_work' => 'no',
            'is_outside_shelter' => true,
            'outside_shelter_reason' => 'transfer_delivery',
        ]);

        $transfer = SponsorshipTransfer::create([
            'branch_id' => $branch->id,
            'contract_id' => $contractId,
            'old_sponsor_id' => $oldSponsorId,
            'new_sponsor_id' => $currentSponsorId,
            'trial_days' => 5,
            'trial_start_date' => now()->subDays(5)->toDateString(),
            'contract_value' => 10000,
            'paid_amount' => 10000,
            'remaining_amount' => 0,
            'status' => 'transferred',
            'transferred_at' => now(),
        ]);

        $this->actingAsAdmin($admin)
            ->post(route('SponsorshipTransfer.returnToShelter', $transfer->id))
            ->assertOk()
            ->assertJsonPath('success', true);

        $contract = Contract::with(['info', 'maid'])->findOrFail($contractId);
        $this->assertSame('shelter', $contract->status);
        $this->assertSame($currentSponsorId, (int) $contract->user_id);
        $this->assertSame('yes', $contract->info->wants_to_work);
        $this->assertFalse((bool) $contract->info->is_outside_shelter);
        $this->assertNotNull($contract->info->maid_date_back);
        $this->assertSame('available', $contract->maid->status);
        $this->assertTrue((bool) $contract->maid->available);
        $this->assertSame(2, (int) $contract->maid->maid_accommodation_type);
        $this->assertTrue(Contract::insideShelter()->whereKey($contractId)->exists());
    }

    private function makeClient(string $name, string $phone, int $branchId): int
    {
        return DB::table('users')->insertGetId([
            'branch_id' => $branchId,
            'name' => $name,
            'phone_code' => '+966',
            'phone' => $phone,
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function grant($admin, string $permission): void
    {
        Permission::withoutGlobalScopes()->updateOrCreate(
            ['name' => $permission, 'guard_name' => 'admin'],
            [
                'display_name' => $permission,
                'group_name' => 'sponsor_transfer',
                'section_name' => 'operations_management',
                'is_active' => true,
            ]
        );

        $admin->givePermissionTo($permission);
    }
}
