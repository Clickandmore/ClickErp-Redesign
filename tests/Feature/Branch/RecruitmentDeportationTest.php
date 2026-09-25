<?php

namespace Tests\Feature\Branch;

use App\Models\Contract;
use App\Models\DeportationProcedure;
use App\Models\Permission;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

class RecruitmentDeportationTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBranches;

    private $branch;
    private $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        $this->enableBranchesModule();
        $this->branch = $this->makeBranch();
        $this->viewer = $this->branchAdmin($this->branch);
        Permission::updateOrCreate(['name' => PermKeys::REC_CONTRACTS_DEPORTATION, 'guard_name' => 'admin'], [
            'display_name' => 'ترحيل العاملة من عقود الاستقدام', 'group_name' => 'recruitment_contracts',
            'section_name' => 'operations_management', 'is_active' => true,
        ]);
        $this->viewer->givePermissionTo(PermKeys::REC_CONTRACTS_DEPORTATION);
        $this->actingAsAdmin($this->viewer);
    }

    private function contract(string $status = 'arrival', ?int $branchId = null, string $type = 'recruitment'): Contract
    {
        $maidId = DB::table('maids')->insertGetId([
            'name' => 'Deportation test worker', 'status' => 'contracted', 'available' => false,
            'branch_id' => $branchId ?? $this->branch->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $id = DB::table('contracts')->insertGetId([
            'maid_id' => $maidId,
            'type' => $type, 'status' => $status, 'branch_id' => $branchId ?? $this->branch->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return Contract::withoutGlobalScopes()->findOrFail($id);
    }

    public function test_arrival_and_completed_can_start_and_view_without_entering_shelter(): void
    {
        foreach (['arrival', 'completed'] as $status) {
            $contract = $this->contract($status);
            $this->postJson(route('RecruitmentContract.deportation.start', $contract->id))
                ->assertOk()->assertJsonPath('redirect', route('RecruitmentContract.deportation.procedures', $contract->id));
            $procedure = $contract->latestDeportationProcedure()->firstOrFail();
            $this->assertSame('pending', $procedure->status);
            $this->assertSame($status, $contract->fresh()->status);
            $this->assertNull($contract->fresh()->info);
            $this->get(route('RecruitmentContract.deportation.procedures', $contract->id))
                ->assertOk()->assertSee(route('RecruitmentContract.deportation.update', $procedure->id), false);
            $this->getJson(route('RecruitmentContract.deportation.activity-log', $procedure->id))->assertOk();
            $this->postJson(route('RecruitmentContract.deportation.start', $contract->id))->assertUnprocessable();
            $this->assertSame(1, DeportationProcedure::where('contract_id', $contract->id)->count());
        }
    }

    public function test_permission_is_required_for_all_recruitment_deportation_actions(): void
    {
        $contract = $this->contract();
        $this->postJson(route('RecruitmentContract.deportation.start', $contract->id))->assertOk();
        $procedure = $contract->latestDeportationProcedure()->firstOrFail();
        $this->viewer->revokePermissionTo(PermKeys::REC_CONTRACTS_DEPORTATION);
        $this->postJson(route('RecruitmentContract.deportation.start', $contract->id))->assertForbidden();
        $this->getJson(route('RecruitmentContract.deportation.procedures', $contract->id))->assertForbidden();
        $this->postJson(route('RecruitmentContract.deportation.update', $procedure->id), ['partial_save' => 1])->assertForbidden();
        $this->postJson(route('RecruitmentContract.deportation.cancel', $procedure->id), ['reason' => 'Test'])->assertForbidden();
        $this->getJson(route('RecruitmentContract.deportation.activity-log', $procedure->id))->assertForbidden();
    }

    public function test_invalid_stages_and_other_branches_are_rejected(): void
    {
        foreach (['new', 'shelter', 'back'] as $status) {
            $this->postJson(route('RecruitmentContract.deportation.start', $this->contract($status)->id))->assertUnprocessable();
        }
        $other = $this->contract('arrival', $this->makeBranch()->id);
        $this->assertContains($this->postJson(route('RecruitmentContract.deportation.start', $other->id))->status(), [403, 404]);
        $this->assertContains($this->getJson(route('RecruitmentContract.deportation.procedures', $other->id))->status(), [403, 404]);
    }

    public function test_escape_report_prevents_starting_deportation(): void
    {
        $contract = $this->contract();
        $contract->escapeReports()->create([
            'escape_date' => now()->toDateString(), 'contract_status_at_escape' => 'داخل الضمان',
            'process_status' => 'escape_reported', 'attachments' => [], 'reported_by' => $this->viewer->id,
        ]);
        $this->postJson(route('RecruitmentContract.deportation.start', $contract->id))->assertUnprocessable();
        $this->assertFalse($contract->latestDeportationProcedure()->exists());
    }

    public function test_cancellation_preserves_stage_and_restart_displays_latest_procedure(): void
    {
        $contract = $this->contract();
        $this->postJson(route('RecruitmentContract.deportation.start', $contract->id))->assertOk();
        $old = $contract->latestDeportationProcedure()->firstOrFail();
        $this->postJson(route('RecruitmentContract.deportation.cancel', $old->id), ['reason' => 'Started in error'])->assertOk();
        $this->assertSame('arrival', $contract->fresh()->status);
        $this->postJson(route('RecruitmentContract.deportation.update', $old->id), ['partial_save' => 1])->assertUnprocessable();
        $this->postJson(route('RecruitmentContract.deportation.start', $contract->id))->assertOk();
        $response = $this->get(route('RecruitmentContract.deportation.procedures', $contract->id))->assertOk();
        $this->assertNotEquals($old->id, $response->viewData('procedure')->id);
    }

    public function test_procedure_can_be_updated_confirmed_and_completed_without_shelter(): void
    {
        $contract = $this->contract();
        $this->postJson(route('RecruitmentContract.deportation.start', $contract->id))->assertOk();
        $procedure = $contract->latestDeportationProcedure()->firstOrFail();
        $url = route('RecruitmentContract.deportation.update', $procedure->id);
        $this->postJson($url, [
            'partial_save' => 1, 'salary_received' => 1, 'has_passport' => 1,
            'has_deportation_letter' => 1, 'has_settlement_session' => 1, 'has_final_exit' => 1,
        ])->assertOk();
        $this->postJson($url, ['confirm_procedures' => true])->assertOk();
        $this->assertTrue($procedure->fresh()->procedures_confirmed);
        $this->postJson($url, ['complete_deportation' => true])->assertStatus(400);
        $procedure->update(['trip_scheduled' => true]);
        $this->postJson($url, ['complete_deportation' => true])->assertOk()
            ->assertJsonPath('redirect', route('RecruitmentContract.index'));
        $this->assertSame('completed', $procedure->fresh()->status);
        $this->assertSame('back', $contract->fresh()->status);
        $this->assertSame('deported', $contract->maid->fresh()->status);
        $this->assertNull($contract->fresh()->info);
        $this->assertSame('arrival', $contract->contract_status_logs()->latest('id')->value('previous_status'));
    }

    public function test_action_button_depends_on_permission_and_stage(): void
    {
        Permission::updateOrCreate(['name' => \App\NewPermKeys::REC_CONTRACTS_VIEW, 'guard_name' => 'admin'], [
            'display_name' => 'View contracts', 'group_name' => 'recruitment_contracts',
            'section_name' => 'operations_management', 'is_active' => true,
        ]);
        $this->viewer->givePermissionTo(\App\NewPermKeys::REC_CONTRACTS_VIEW);
        $arrival = $this->contract('arrival');
        $completed = $this->contract('completed');
        $new = $this->contract('new');
        $rows = collect($this->getJson(route('RecruitmentContract.index'), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'))->keyBy('id');
        foreach ([$arrival, $completed] as $contract) {
            $this->assertStringContainsString('start-recruitment-deportation', $rows[$contract->id]['actions']);
        }
        $this->assertStringNotContainsString('start-recruitment-deportation', $rows[$new->id]['actions']);
        $this->viewer->revokePermissionTo(PermKeys::REC_CONTRACTS_DEPORTATION);
        $rows = collect($this->getJson(route('RecruitmentContract.index'), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'))->keyBy('id');
        $this->assertStringNotContainsString('start-recruitment-deportation', $rows[$arrival->id]['actions']);
    }
}
