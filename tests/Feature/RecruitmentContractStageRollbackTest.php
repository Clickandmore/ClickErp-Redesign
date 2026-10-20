<?php

namespace Tests\Feature;

use App\Enums\ingaz\ContractStatusEnum;
use App\Http\Controllers\admin\AdminTravelHistoryController;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\Permission;
use App\Models\TravelHistory;
use App\Models\auth\Admin;
use App\Models\ingaz\Invoice;
use App\NewPermKeys;
use App\Services\ingaz\RecruitmentContractStageRollbackService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * إرجاع مراحل عقد الاستقدام يتم خطوة واحدة فقط ضمن التسلسل، ويُرفض إذا كان
 * سيتخطى مرحلة أُثبتت فيها قيود مالية أو إذا خرج العقد من سياق الاستقدام.
 */
class RecruitmentContractStageRollbackTest extends TestCase
{
    use DatabaseTransactions;

    private RecruitmentContractStageRollbackService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RecruitmentContractStageRollbackService::class);
    }

    private function makeContract(string $status, array $attributes = []): Contract
    {
        $marketer = Admin::factory()->create();

        $contract = Contract::query()->create(array_merge([
            'status' => $status,
            'type' => 'recruitment',
            'marketer_id' => $marketer->id,
        ], $attributes));
        $contract->contract_status_logs()->create(['status' => $status, 'date' => now()]);

        return $contract->fresh();
    }

    private function grant(Admin $admin, string $permission): void
    {
        Permission::withoutGlobalScopes()->updateOrCreate(
            ['name' => $permission, 'guard_name' => 'admin'],
            [
                'display_name' => $permission,
                'group_name' => 'rec_contracts',
                'section_name' => 'operations_management',
                'is_active' => true,
            ]
        );

        $admin->givePermissionTo($permission);
    }

    private function assertBlocked(Contract $contract, string $needle): void
    {
        try {
            $this->service->rollback($contract, 1);
            $this->fail('Rollback should have been blocked.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString($needle, $exception->errors()['contract'][0]);
        }

        $this->assertSame($contract->status, $contract->fresh()->status);
    }

    public function test_the_sequence_goes_back_exactly_one_stage(): void
    {
        $this->assertSame('arrival', $this->service->previousStatusFor('completed'));
        $this->assertSame('tazker', $this->service->previousStatusFor('arrival'));
        $this->assertSame('taffez', $this->service->previousStatusFor('tazker'));
        $this->assertSame('tafwed', $this->service->previousStatusFor('taffez'));
        $this->assertSame('msaned', $this->service->previousStatusFor('tafwed'));
        $this->assertSame('new', $this->service->previousStatusFor('msaned'));
        $this->assertSame('new', $this->service->previousStatusFor('accreditation_contracts'));

        foreach (['new', 'pending_maid_selection', 'refunded', 'back', 'shelter', 'sari', 'closed', 'trial'] as $status) {
            $this->assertNull($this->service->previousStatusFor($status), $status);
        }

        $this->assertSame(ContractStatusEnum::Arrival, contractBackStatus('completed'));
        $this->assertSame(ContractStatusEnum::New, contractBackStatus('new'));
    }

    public function test_a_completed_contract_without_a_real_arrival_rolls_back_to_arrival(): void
    {
        $contract = $this->makeContract('completed', ['warranty_status' => 'expired']);

        $rolledBack = $this->service->rollback($contract, 7, '2026-09-01');

        $this->assertSame('arrival', $rolledBack->status);
        $this->assertSame('not_applicable', $rolledBack->warranty_status);
        $this->assertDatabaseHas('contract_status_logs', [
            'contract_id' => $contract->id,
            'status' => 'arrival',
            'previous_status' => 'completed',
            'date' => '2026-09-01',
        ]);
    }

    public function test_a_completed_contract_whose_warranty_really_expired_is_blocked(): void
    {
        $contract = $this->makeContract('completed', [
            'warranty_status' => 'expired',
            'warranty_start_date' => now()->subDays(400),
            'warranty_end_date' => now()->subDays(310),
        ]);

        $this->assertBlocked($contract, 'انتهاء فترة الضمان');
    }

    public function test_a_contract_with_a_services_transfer_is_out_of_context(): void
    {
        $contract = $this->makeContract('completed', ['warranty_status' => 'expired']);
        DB::table('sponsorship_transfers')->insert([
            'contract_id' => $contract->id,
            'old_sponsor_id' => 1,
            'new_sponsor_id' => 2,
            'trial_days' => 7,
            'trial_start_date' => now()->toDateString(),
            'contract_value' => 1000,
            'paid_amount' => 1000,
            'remaining_amount' => 0,
            'status' => 'transferred',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertBlocked($contract, 'نقل خدمات');
    }

    public function test_leaving_arrival_is_blocked_while_arrival_revenue_is_recognized(): void
    {
        $contract = $this->makeContract('arrival', ['warranty_status' => 'active']);
        Invoice::query()->create([
            'contract_id' => $contract->id,
            'arrival_revenue_constraint_id' => 987654,
        ]);

        $this->assertBlocked($contract, 'إيراد الوصول بالقيد رقم 987654');
    }

    public function test_leaving_arrival_is_blocked_while_the_trip_is_completed(): void
    {
        $contract = $this->makeContract('arrival', ['warranty_status' => 'active']);
        TravelHistory::query()->create([
            'user_id' => 1,
            'maid_id' => 1,
            'order_id' => $contract->id,
            'travel_type' => 'arrival',
            'travel_with' => 'none',
            'status' => 'completed',
        ]);

        $this->assertBlocked($contract, 'مكتملة');
    }

    public function test_arrival_rolls_back_to_tazker_and_clears_the_warranty_when_nothing_is_posted(): void
    {
        $contract = $this->makeContract('arrival', [
            'warranty_status' => 'active',
            'warranty_start_date' => now(),
            'warranty_end_date' => now()->addDays(90),
        ]);
        Invoice::query()->create(['contract_id' => $contract->id]);

        $rolledBack = $this->service->rollback($contract, 1);

        $this->assertSame('tazker', $rolledBack->status);
        $this->assertSame('not_applicable', $rolledBack->warranty_status);
        $this->assertNull($rolledBack->warranty_start_date);
    }

    public function test_rollback_before_arrival_clears_a_trip_copied_arrival_date_when_no_trip_is_active_or_confirmed(): void
    {
        $contract = $this->makeContract('arrival', ['warranty_status' => 'active']);
        $info = ContractInfo::create(['contract_id' => $contract->id, 'arrival_date' => '2026-10-01']);

        $this->service->rollback($contract, 1);

        $this->assertNull($info->fresh()->arrival_date);
    }

    public function test_rollback_before_arrival_preserves_date_while_another_trip_is_active(): void
    {
        $contract = $this->makeContract('arrival', ['warranty_status' => 'active']);
        $info = ContractInfo::create(['contract_id' => $contract->id, 'arrival_date' => '2026-10-01']);
        TravelHistory::query()->create([
            'user_id' => 1, 'maid_id' => 1, 'order_id' => $contract->id,
            'travel_type' => 'arrival', 'travel_with' => 'none', 'status' => 'scheduled',
        ]);

        $this->service->rollback($contract, 1);

        $this->assertSame('2026-10-01', $info->fresh()->arrival_date);
    }

    public function test_deleting_the_only_unconfirmed_trip_clears_the_contract_arrival_date(): void
    {
        $contract = $this->makeContract('tazker');
        $info = ContractInfo::create(['contract_id' => $contract->id, 'arrival_date' => '2026-10-01']);
        $trip = TravelHistory::query()->create([
            'user_id' => 1, 'maid_id' => 1, 'order_id' => $contract->id,
            'travel_type' => 'arrival', 'travel_with' => 'none', 'status' => 'scheduled',
        ]);

        app(AdminTravelHistoryController::class)->destroy((string) $trip->id);

        $this->assertNull($info->fresh()->arrival_date);
    }

    public function test_deleting_a_trip_keeps_the_contract_date_when_another_trip_is_active(): void
    {
        $contract = $this->makeContract('tazker');
        $info = ContractInfo::create(['contract_id' => $contract->id, 'arrival_date' => '2026-10-01']);
        $trip = TravelHistory::query()->create([
            'user_id' => 1, 'maid_id' => 1, 'order_id' => $contract->id,
            'travel_type' => 'arrival', 'travel_with' => 'none', 'status' => 'scheduled',
        ]);
        TravelHistory::query()->create([
            'user_id' => 1, 'maid_id' => 1, 'order_id' => $contract->id,
            'travel_type' => 'arrival', 'travel_with' => 'none', 'status' => 'active',
        ]);

        app(AdminTravelHistoryController::class)->destroy((string) $trip->id);

        $this->assertSame('2026-10-01', $info->fresh()->arrival_date);
    }

    public function test_deleting_a_trip_keeps_the_contract_date_when_arrival_was_confirmed(): void
    {
        $contract = $this->makeContract('tazker');
        $info = ContractInfo::create(['contract_id' => $contract->id, 'arrival_date' => '2026-10-01']);
        $trip = TravelHistory::query()->create([
            'user_id' => 1, 'maid_id' => 1, 'order_id' => $contract->id,
            'travel_type' => 'arrival', 'travel_with' => 'none', 'status' => 'completed',
            'arrived_at' => now(),
        ]);

        app(AdminTravelHistoryController::class)->destroy((string) $trip->id);

        $this->assertSame('2026-10-01', $info->fresh()->arrival_date);
    }

    public function test_leaving_msaned_is_blocked_while_a_musaned_transfer_exists(): void
    {
        $contract = $this->makeContract('msaned');
        $invoice = Invoice::query()->create(['contract_id' => $contract->id]);
        DB::table('musaned_transfer_items')->insert([
            'musaned_transfer_id' => 1,
            'invoice_id' => $invoice->id,
            'contract_id' => $contract->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertBlocked($contract, 'تحويل مساند');
        $this->assertSame('msaned', $this->service->rollback($this->makeContract('tafwed'), 1)->status);
    }

    public function test_refunded_and_returned_contracts_cannot_roll_back(): void
    {
        $this->assertBlocked($this->makeContract('refunded'), 'مسترد');
        $this->assertBlocked($this->makeContract('back'), 'التراجع عن الإرجاع');
        $this->assertBlocked($this->makeContract('new'), 'المرحلة الأولى');
    }

    public function test_the_back_endpoint_rolls_a_completed_contract_back_one_stage_only(): void
    {
        $admin = Admin::factory()->create();
        $this->grant($admin, NewPermKeys::REC_CONTRACTS_RETURN);
        $contract = $this->makeContract('completed', ['warranty_status' => 'expired']);

        $this->actingAs($admin, 'admin')
            ->getJson(route('RecruitmentContract.back', $contract->id))
            ->assertOk()
            ->assertJson(['success' => true, 'status' => 'arrival']);

        $this->assertSame('arrival', $contract->fresh()->status);
    }

    public function test_the_update_status_endpoint_refuses_free_jumps_for_completed_contracts(): void
    {
        $admin = Admin::factory()->create();
        $this->grant($admin, NewPermKeys::REC_CONTRACTS_RETURN);
        $this->grant($admin, NewPermKeys::REC_CONTRACTS_UPDATE);
        $contract = $this->makeContract('completed', ['warranty_status' => 'expired']);

        $this->actingAs($admin, 'admin')
            ->postJson(route('RecruitmentContract.updateStatus'), [
                'row_id' => $contract->id,
                'next_status' => 'new',
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertSame('completed', $contract->fresh()->status);

        $this->actingAs($admin, 'admin')
            ->postJson(route('RecruitmentContract.updateStatus'), [
                'row_id' => $contract->id,
                'next_status' => 'arrival',
                'date' => '2026-09-05',
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'next_status' => 'arrival']);

        $this->assertSame('arrival', $contract->fresh()->status);
    }

    public function test_the_back_endpoint_reports_financial_blockers_without_changing_anything(): void
    {
        $admin = Admin::factory()->create();
        $this->grant($admin, NewPermKeys::REC_CONTRACTS_RETURN);
        $contract = $this->makeContract('arrival', ['warranty_status' => 'active']);
        Invoice::query()->create([
            'contract_id' => $contract->id,
            'arrival_revenue_constraint_id' => 555,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->getJson(route('RecruitmentContract.back', $contract->id))
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertStringContainsString('555', $response->json('message'));
        $this->assertSame('arrival', $contract->fresh()->status);
    }
}
