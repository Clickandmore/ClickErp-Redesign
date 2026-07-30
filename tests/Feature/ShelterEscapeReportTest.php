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

class ShelterEscapeReportTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        $this->enableBranchesModule();
    }

    /** @test */
    public function an_escape_report_moves_the_contract_outside_shelter(): void
    {
        $branch = $this->makeBranch(['is_main' => true]);
        $admin = $this->branchAdmin($branch);
        $this->grant($admin, PermKeys::SHELTER_CONTRACT_EDIT);
        $this->grant($admin, PermKeys::SHELTER_CONTRACT_DEPORTATION_PROCEDURES_EDIT);

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
            'is_available_for_instant' => true,
        ]);

        $this->actingAsAdmin($admin)
            ->post(route('ShelterContract.escapeReport', $contractId), [
                'note' => 'تم فقد التواصل مع العاملة.',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $contract = Contract::with('info')->findOrFail($contractId);

        $this->assertTrue($contract->hasEscapeReport());
        $this->assertTrue($contract->info->is_outside_shelter);
        $this->assertFalse((bool) $contract->info->is_available_for_instant);
        $this->assertSame('تم فقد التواصل مع العاملة.', $contract->info->escape_report_note);
        $this->assertFalse(Contract::insideShelter()->whereKey($contractId)->exists());
        $this->assertTrue(Contract::outsideShelter()->whereKey($contractId)->exists());

        $this->actingAsAdmin($admin)
            ->post(route('ShelterContract.deportation.start', $contractId))
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'لا يمكن بدء الترحيل لوجود بلاغ هروب على العاملة']);
    }

    /** @test */
    public function an_escape_report_requires_a_note_or_an_attachment(): void
    {
        $branch = $this->makeBranch(['is_main' => true]);
        $admin = $this->branchAdmin($branch);
        $this->grant($admin, PermKeys::SHELTER_CONTRACT_EDIT);

        $contractId = DB::table('contracts')->insertGetId([
            'branch_id' => $branch->id,
            'status' => 'shelter',
            'type' => 'recruitment',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ContractInfo::create([
            'contract_id' => $contractId,
            'is_outside_shelter' => false,
        ]);

        $this->actingAsAdmin($admin)
            ->post(route('ShelterContract.escapeReport', $contractId))
            ->assertSessionHasErrors(['note', 'attachment']);

        $this->assertFalse((bool) ContractInfo::where('contract_id', $contractId)->value('is_outside_shelter'));
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
