<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\auth\Admin;
use App\NewPermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecruitmentContractShelterViewTest extends TestCase
{
    use DatabaseTransactions;

    private function adminWithPermissions(array $permissions): Admin
    {
        $admin = Admin::factory()->create();

        foreach ($permissions as $permissionName) {
            Permission::withoutGlobalScopes()->updateOrCreate(
                ['name' => $permissionName, 'guard_name' => 'admin'],
                [
                    'display_name' => $permissionName,
                    'group_name' => 'rec_contracts',
                    'section_name' => 'recruitment_contracts',
                    'is_active' => true,
                ]
            );
        }

        $admin->givePermissionTo($permissions);

        return $admin;
    }

    private function makeContract(string $status): int
    {
        return DB::table('contracts')->insertGetId([
            'status' => $status,
            'type' => 'recruitment',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function shelter_button_and_mode_require_the_separate_permission(): void
    {
        $admin = $this->adminWithPermissions([NewPermKeys::REC_CONTRACTS_VIEW]);

        $this->actingAs($admin, 'admin')
            ->get(route('RecruitmentContract.index'))
            ->assertOk()
            ->assertDontSee('shelter_contracts=1', false);

        $this->actingAs($admin, 'admin')
            ->get(route('RecruitmentContract.index', ['shelter_contracts' => 1]))
            ->assertForbidden();
    }

    /** @test */
    public function shelter_mode_returns_only_shelter_contracts_and_supports_contract_search(): void
    {
        $admin = $this->adminWithPermissions([
            NewPermKeys::REC_CONTRACTS_VIEW,
            NewPermKeys::REC_CONTRACTS_VIEW_SHELTER,
        ]);
        $shelterContractId = $this->makeContract('shelter');
        $this->makeContract('new');

        $this->actingAs($admin, 'admin')
            ->get(route('RecruitmentContract.index', [
                'shelter_contracts' => 1,
                'contract_number' => $shelterContractId,
            ]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $shelterContractId);
    }

    /** @test */
    public function regular_mode_treats_shelter_as_an_operational_state_not_a_recruitment_stage(): void
    {
        $admin = $this->adminWithPermissions([NewPermKeys::REC_CONTRACTS_VIEW]);
        $shelterContractId = $this->makeContract('shelter');

        DB::table('contract_status_logs')->insert([
            'contract_id' => $shelterContractId,
            'status' => 'shelter',
            'previous_status' => 'arrival',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $allContractsResponse = $this->actingAs($admin, 'admin')
            ->get(route('RecruitmentContract.index', [
                'all_contracts' => 1,
                'contract_number' => $shelterContractId,
            ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $allContractsResponse
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $shelterContractId);
        $this->assertStringContainsString(
            'العاملة داخل الإيواء',
            (string) $allContractsResponse->json('data.0.status')
        );

        $this->actingAs($admin, 'admin')
            ->get(route('RecruitmentContract.index', [
                'status' => 'arrival',
                'contract_number' => $shelterContractId,
            ]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($admin, 'admin')
            ->get(route('RecruitmentContract.index', ['status' => 'shelter']), [
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /** @test */
    public function shelter_permission_is_configured_as_manual_opt_in(): void
    {
        $this->assertContains(
            NewPermKeys::REC_CONTRACTS_VIEW_SHELTER,
            config('manual_permission_opt_ins.permissions', [])
        );
    }
}
