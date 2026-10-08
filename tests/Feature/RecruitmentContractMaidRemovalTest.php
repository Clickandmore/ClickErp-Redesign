<?php

namespace Tests\Feature;

use App\Http\Controllers\ingaz\RecruitmentContractController;
use App\Models\Contract;
use App\Models\Permission;
use App\Models\auth\Admin;
use App\NewPermKeys;
use App\Services\ingaz\RecruitmentContractMaidRemovalService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RecruitmentContractMaidRemovalTest extends TestCase
{
    use DatabaseTransactions;
    use \Tests\Concerns\InteractsWithBranches;

    private function contract(string $status = 'new', bool $withOrder = false): Contract
    {
        Cache::put('superadmin.modules.enabled_map', ['branches' => false], 60);
        $maidId = DB::table('maids')->insertGetId(['status' => 'reserved', 'available' => false, 'created_at' => now(), 'updated_at' => now()]);
        $orderId = $withOrder ? DB::table('orders')->insertGetId([
            'maid_id' => $maidId, 'status' => 'contracted', 'created_at' => now(), 'updated_at' => now(),
        ]) : null;
        $id = DB::table('contracts')->insertGetId([
            'maid_id' => $maidId, 'order_id' => $orderId, 'status' => $status, 'type' => 'recruitment',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return Contract::findOrFail($id);
    }

    public function test_removal_releases_worker_and_detaches_related_order(): void
    {
        $contract = $this->contract('new', true);
        $maidId = $contract->maid_id;
        app(RecruitmentContractMaidRemovalService::class)->remove($contract, $maidId, 'available', null, null);
        $this->assertNull($contract->fresh()->maid_id);
        $this->assertSame('pending_maid_selection', $contract->fresh()->status);
        $this->assertNull(DB::table('orders')->where('id', $contract->order_id)->value('maid_id'));
        $this->assertSame('available', DB::table('maids')->where('id', $maidId)->value('status'));
        $this->assertEquals(1, DB::table('maids')->where('id', $maidId)->value('available'));
        $this->assertStringContainsString('#' . $maidId, $contract->contract_status_logs()->latest('id')->first()->notes);
    }

    public function test_backout_records_reason_and_keeps_worker_unavailable(): void
    {
        $contract = $this->contract('tafwed');
        $maidId = $contract->maid_id;
        app(RecruitmentContractMaidRemovalService::class)->remove($contract, $maidId, 'backout', 'Worker declined', null);
        $this->assertSame('pending_maid_selection', $contract->fresh()->status);
        $this->assertSame('backout', DB::table('maids')->where('id', $maidId)->value('status'));
        $this->assertEquals(0, DB::table('maids')->where('id', $maidId)->value('available'));
        $this->assertDatabaseHas('maid_unavailable_records', ['maid_id' => $maidId, 'contract_id' => $contract->id, 'reason' => 'Worker declined']);
    }

    /** @dataProvider blockedStatuses */
    public function test_invalid_contract_states_are_unchanged(string $status): void
    {
        $contract = $this->contract($status);
        try {
            app(RecruitmentContractMaidRemovalService::class)->remove($contract, $contract->maid_id, 'available', null, null);
            $this->fail('Expected rejection');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('contract', $e->errors());
        }
        $this->assertSame($status, $contract->fresh()->status);
        $this->assertSame($contract->maid_id, $contract->fresh()->maid_id);
    }

    public function blockedStatuses(): array
    {
        return [['completed'], ['refunded'], ['back'], ['shelter'], ['pending_maid_selection']];
    }

    public function test_stale_worker_and_backout_without_reason_are_rejected(): void
    {
        $contract = $this->contract();
        foreach ([[$contract->maid_id + 1, 'available', null], [$contract->maid_id, 'backout', null]] as [$maidId, $status, $reason]) {
            try {
                app(RecruitmentContractMaidRemovalService::class)->remove($contract, $maidId, $status, $reason, null);
                $this->fail('Expected rejection');
            } catch (ValidationException $e) {
                $this->assertNotEmpty($e->errors());
            }
        }
        $this->assertSame('new', $contract->fresh()->status);
        $this->assertSame($contract->maid_id, $contract->fresh()->maid_id);
    }

    public function test_another_active_assignment_prevents_releasing_worker(): void
    {
        $contract = $this->contract();
        DB::table('contracts')->insert(['maid_id' => $contract->maid_id, 'status' => 'new', 'type' => 'recruitment']);
        try {
            app(RecruitmentContractMaidRemovalService::class)->remove($contract, $contract->maid_id, 'available', null, null);
            $this->fail('Expected rejection');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('contract', $e->errors());
        }
        $this->assertSame('new', $contract->fresh()->status);
        $this->assertSame('reserved', DB::table('maids')->where('id', $contract->maid_id)->value('status'));
    }

    public function test_update_permission_alone_cannot_remove_worker(): void
    {
        $admin = Admin::factory()->create();
        Permission::firstOrCreate(['name' => NewPermKeys::REC_CONTRACTS_UPDATE, 'guard_name' => 'admin'], [
            'display_name' => 'Update', 'group_name' => 'rec_contracts', 'section_name' => 'recruitment_contracts', 'is_active' => true,
        ]);
        $admin->givePermissionTo(NewPermKeys::REC_CONTRACTS_UPDATE);
        $contract = $this->contract();
        $this->actingAs($admin, 'admin')->postJson(route('RecruitmentContract.removeMaid', $contract->id), [
            'old_maid_id' => $contract->maid_id, 'old_maid_status' => 'available',
        ])->assertForbidden();
        $this->assertSame($contract->maid_id, $contract->fresh()->maid_id);
    }

    public function test_authorized_removal_and_later_selection_synchronize_the_order(): void
    {
        $admin = Admin::factory()->create();
        foreach ([NewPermKeys::REC_CONTRACTS_REMOVE_MAID, NewPermKeys::REC_CONTRACTS_UPDATE] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'admin'], [
                'display_name' => $permission, 'group_name' => 'rec_contracts', 'section_name' => 'recruitment_contracts', 'is_active' => true,
            ]);
            $admin->givePermissionTo($permission);
        }
        $contract = $this->contract('new', true);
        $this->actingAs($admin, 'admin')->postJson(route('RecruitmentContract.removeMaid', $contract->id), [
            'old_maid_id' => $contract->maid_id, 'old_maid_status' => 'available',
        ])->assertOk()->assertJsonPath('success', true);
        $this->assertSame('pending_maid_selection', $contract->fresh()->status);
        $newMaidId = DB::table('maids')->insertGetId(['status' => 'available', 'available' => true]);
        $response = app(RecruitmentContractController::class)->addMaidToContract(
            \Illuminate\Http\Request::create('/', 'POST', ['maid_id' => $newMaidId]), $contract->id
        );
        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame($newMaidId, $contract->fresh()->maid_id);
        $this->assertSame('new', $contract->fresh()->status);
        $this->assertSame($newMaidId, DB::table('orders')->where('id', $contract->order_id)->value('maid_id'));
    }

    public function test_linked_trip_blocks_removal_without_changing_assignments(): void
    {
        $contract = $this->contract('tazker', true);
        DB::table('travel_histories')->insert([
            'order_id' => $contract->id, 'maid_id' => $contract->maid_id, 'status' => 'new',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        try {
            app(RecruitmentContractMaidRemovalService::class)->remove($contract, $contract->maid_id, 'available', null, null);
            $this->fail('Expected rejection');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('رحلة', $e->errors()['contract'][0]);
        }
        $this->assertSame($contract->maid_id, $contract->fresh()->maid_id);
        $this->assertSame($contract->maid_id, DB::table('orders')->where('id', $contract->order_id)->value('maid_id'));
    }

    public function test_permission_does_not_allow_removing_worker_from_another_branch(): void
    {
        $contract = $this->contract();
        $ownBranch = $this->makeBranch();
        $otherBranch = $this->makeBranch();
        DB::table('contracts')->where('id', $contract->id)->update(['branch_id' => $otherBranch->id]);
        $this->enableBranchesModule();
        $admin = $this->lockToOwnBranch($this->branchAdmin($ownBranch));
        Permission::firstOrCreate(['name' => NewPermKeys::REC_CONTRACTS_REMOVE_MAID, 'guard_name' => 'admin'], [
            'display_name' => 'Remove worker', 'group_name' => 'rec_contracts', 'section_name' => 'recruitment_contracts', 'is_active' => true,
        ]);
        $admin->givePermissionTo(NewPermKeys::REC_CONTRACTS_REMOVE_MAID);
        $this->actingAs($admin, 'admin')->postJson(route('RecruitmentContract.removeMaid', $contract->id), [
            'old_maid_id' => $contract->maid_id, 'old_maid_status' => 'available',
        ])->assertNotFound();
        $this->assertSame($contract->maid_id, DB::table('contracts')->where('id', $contract->id)->value('maid_id'));
    }
}
