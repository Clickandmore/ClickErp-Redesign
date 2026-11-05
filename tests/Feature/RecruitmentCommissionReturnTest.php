<?php

namespace Tests\Feature;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\auth\Admin;
use App\Models\commissions\CommissionBalanceTransaction;
use App\Models\commissions\CommissionBatch;
use App\Models\commissions\CommissionBatchItem;
use App\Services\commissions\CommissionOperationService;
use App\Services\commissions\CommissionPaymentService;
use App\Services\commissions\CommissionCalculationService;
use App\Services\commissions\CommissionAccrualService;
use App\Services\commissions\DelegationCommissionService;
use App\Services\commissions\RecruitmentCommissionService;
use App\Support\PostArrivalRefundSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\FnYear;
use Mockery;
use Nami\Finance\Models\ConfirmSetting;
use Tests\TestCase;

class RecruitmentCommissionReturnTest extends TestCase
{
    use DatabaseTransactions;
    use \Tests\Concerns\InteractsWithCommissionFinance;

    public function test_deducting_a_returned_commission_creates_the_financial_reversal_entry(): void
    {
        DB::table('fn_constraint_code_sequences')->updateOrInsert(
            ['id' => 1],
            ['last_code' => 0, 'created_at' => now(), 'updated_at' => now()]
        );
        FnYear::query()->create([
            'title' => '2026',
            'from_date' => '2026-01-01',
            'to_date' => '2026-12-31',
            'status' => 1,
        ]);
        $marketer = Admin::factory()->create();
        $parent = Account::query()->firstOrCreate(['code' => 144], [
            'name' => 'المسوقين',
            'status' => 'creditor',
            'level' => 1,
            'code' => 144,
            'type' => AccountTypesEnum::Marketers->value,
        ]);
        $payable = Account::query()->create([
            'name' => 'حساب اختبار عكس عمولة ' . $marketer->id,
            'status' => 'creditor',
            'level' => 2,
            'code' => 900000 + $marketer->id,
            'type' => AccountTypesEnum::Marketer->value,
            'parent_id' => $parent->id,
        ]);
        $expense = Account::query()->create([
            'name' => 'مصروف عمولات الموظفين',
            'status' => 'debtor',
            'level' => 1,
            'code' => 52149,
            'type' => AccountTypesEnum::MarketerExpenses->value,
        ]);
        $marketer->update(['commission_payable_account_id' => $payable->id]);
        $contract = Contract::query()->create([
            'status' => ContractStatusEnum::Back->value,
            'marketer_id' => $marketer->id,
            'contract_cost' => 12000,
            'contract_date' => now()->toDateString(),
        ]);
        $batch = CommissionBatch::query()->create([
            'marketer_id' => $marketer->id,
            'total_requests' => 1,
            'total_base_amount' => 12000,
            'total_commission' => 100,
            'status' => CommissionBatch::STATUS_APPROVED,
            'expense_account_id' => $expense->id,
            'payable_account_id' => $payable->id,
            'approved_by' => $marketer->id,
            'approved_at' => now(),
        ]);
        $item = CommissionBatchItem::query()->create([
            'batch_id' => $batch->id,
            'source_type' => CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT,
            'source_id' => $contract->id,
            'contract_id' => $contract->id,
            'service_type' => 'recruitment',
            'base_amount' => 12000,
            'method' => 'fixed',
            'rate_or_amount' => 100,
            'commission_amount' => 100,
        ]);
        CommissionBalanceTransaction::query()->create([
            'marketer_id' => $marketer->id,
            'type' => CommissionBalanceTransaction::TYPE_CREDIT,
            'amount' => 100,
            'reference_type' => 'commission_batch',
            'reference_id' => $batch->id,
            'balance_after' => 100,
        ]);

        app(RecruitmentCommissionService::class)->resolveSettlement(
            $item,
            RecruitmentCommissionService::SETTLEMENT_DEDUCTED,
            $marketer->id
        );

        $constraintId = $item->fresh()->commission_reversal_constraint_id;
        $this->assertNotNull($constraintId);
        $this->assertDatabaseHas('fn_constraint_details', [
            'constraint_id' => $constraintId,
            'account_id' => $payable->id,
            'type' => 'debtor',
            'debtor_value' => 100,
            'creditor_value' => 0,
        ]);
        $this->assertDatabaseHas('fn_constraint_details', [
            'constraint_id' => $constraintId,
            'account_id' => $expense->id,
            'type' => 'creditor',
            'debtor_value' => 0,
            'creditor_value' => 100,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCommissionFinance();
        ConfirmSetting::query()->create(['title' => PostArrivalRefundSettings::POSTING_MODE, 'value' => 'auto']);
    }

    public function test_returned_uncommissioned_contract_is_closed_at_zero_and_cannot_be_selected(): void
    {
        $marketer = Admin::factory()->create();
        $contract = Contract::query()->create([
            'status' => ContractStatusEnum::New->value,
            'marketer_id' => $marketer->id,
            'contract_cost' => 12000,
            'contract_date' => now()->toDateString(),
        ]);

        $contract->update(['status' => ContractStatusEnum::Back->value]);

        $item = CommissionBatchItem::query()
            ->where('source_type', CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT)
            ->where('source_id', $contract->id)
            ->firstOrFail();

        $this->assertSame(0.0, (float) $item->commission_amount);
        $this->assertSame(RecruitmentCommissionService::ZERO_SOURCE, $item->legacy_source);
        $this->assertCount(0, app(CommissionOperationService::class)->findSelected(
            $marketer->id,
            [CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT . ':' . $contract->id]
        ));
    }

    public function test_return_after_approval_waits_for_explicit_decision_without_changing_balance(): void
    {
        $marketer = Admin::factory()->create();
        $contract = Contract::query()->create([
            'status' => ContractStatusEnum::New->value,
            'marketer_id' => $marketer->id,
            'contract_cost' => 12000,
            'contract_date' => now()->toDateString(),
        ]);
        $batch = CommissionBatch::query()->create([
            'marketer_id' => $marketer->id,
            'total_requests' => 1,
            'total_base_amount' => 12000,
            'total_commission' => 500,
            'status' => CommissionBatch::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
        $item = CommissionBatchItem::query()->create([
            'batch_id' => $batch->id,
            'source_type' => CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT,
            'source_id' => $contract->id,
            'contract_id' => $contract->id,
            'service_type' => 'recruitment',
            'base_amount' => 12000,
            'method' => 'fixed',
            'rate_or_amount' => 500,
            'commission_amount' => 500,
        ]);
        CommissionBalanceTransaction::query()->create([
            'marketer_id' => $marketer->id,
            'type' => CommissionBalanceTransaction::TYPE_CREDIT,
            'amount' => 500,
            'reference_type' => 'commission_batch',
            'reference_id' => $batch->id,
            'balance_after' => 500,
        ]);

        $contract->update(['status' => ContractStatusEnum::Refunded->value]);

        $item->refresh();
        $this->assertSame(500.0, (float) $item->commission_amount);
        $this->assertNull($item->commission_reversal_transaction_id);
        $this->assertSame(0, CommissionBalanceTransaction::query()
            ->where('reference_type', 'commission_reversal')
            ->where('reference_id', $item->id)
            ->count());
        $this->assertSame(500.0, app(CommissionPaymentService::class)->getPaymentSummary($marketer->id)['balance']);

        app(RecruitmentCommissionService::class)->resolveSettlement(
            $item,
            RecruitmentCommissionService::SETTLEMENT_DEDUCTED,
            $marketer->id
        );

        $item->refresh();
        $this->assertNotNull($item->commission_reversal_transaction_id);
        $this->assertSame(0.0, (float) $item->reversalTransaction->balance_after);
    }

    public function test_shelter_admission_preserves_approved_commission_and_cannot_be_settled_as_return(): void
    {
        $marketer = Admin::factory()->create();
        $contract = Contract::query()->create([
            'status' => ContractStatusEnum::Completed->value,
            'marketer_id' => $marketer->id,
            'contract_cost' => 3980.75,
            'contract_date' => now()->toDateString(),
        ]);
        $batch = CommissionBatch::query()->create([
            'marketer_id' => $marketer->id,
            'total_requests' => 1,
            'total_base_amount' => 3980.75,
            'total_commission' => 100,
            'status' => CommissionBatch::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
        $item = CommissionBatchItem::query()->create([
            'batch_id' => $batch->id,
            'source_type' => CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT,
            'source_id' => $contract->id,
            'contract_id' => $contract->id,
            'service_type' => 'recruitment',
            'base_amount' => 3980.75,
            'method' => 'fixed',
            'rate_or_amount' => 100,
            'commission_amount' => 100,
        ]);
        CommissionBalanceTransaction::query()->create([
            'marketer_id' => $marketer->id,
            'type' => CommissionBalanceTransaction::TYPE_CREDIT,
            'amount' => 100,
            'reference_type' => 'commission_batch',
            'reference_id' => $batch->id,
            'balance_after' => 100,
        ]);

        $contract->update(['status' => ContractStatusEnum::Shelter->value]);
        $operation = app(CommissionOperationService::class)
            ->getOperations($marketer->id)
            ->firstWhere('source_id', $contract->id);

        $this->assertTrue($operation['commission_eligible']);
        $this->assertNotSame(RecruitmentCommissionService::SETTLEMENT_PENDING, $operation['return_settlement_status']);
        $this->assertFalse(app(RecruitmentCommissionService::class)->pendingDeductions($marketer->id)->contains('id', $item->id));
        $this->assertNull($item->fresh()->commission_reversal_transaction_id);
        $this->assertNull($item->fresh()->commission_reversal_reason);
        $this->assertSame(100.0, app(CommissionPaymentService::class)->getPaymentSummary($marketer->id)['balance']);

        $this->expectException(ValidationException::class);
        app(RecruitmentCommissionService::class)->resolveSettlement(
            $item,
            RecruitmentCommissionService::SETTLEMENT_DEDUCTED,
            $marketer->id
        );

    }

    public function test_uncommissioned_shelter_admission_does_not_create_zero_marker_or_financial_movement(): void
    {
        $marketer = Admin::factory()->create();
        $contract = Contract::query()->create([
            'status' => ContractStatusEnum::Completed->value, 'marketer_id' => $marketer->id,
            'contract_cost' => 12000, 'contract_date' => now()->toDateString(),
        ]);
        $constraints = DB::table('fn_constraints')->count();
        $transactions = CommissionBalanceTransaction::query()->count();
        $contract->update(['status' => ContractStatusEnum::Shelter->value]);
        $contract->setRelation('info', new \App\Models\ContractInfo(['shelter_id' => 123]));
        $service = app(RecruitmentCommissionService::class);
        $this->assertTrue($service->isEligible($contract));
        $service->assertEligible($contract);
        $this->assertNull($service->settleReturnedContract($contract));
        $this->assertFalse(CommissionBatchItem::query()->where('source_type', CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT)->where('source_id', $contract->id)->exists());
        $this->assertSame($constraints, DB::table('fn_constraints')->count());
        $this->assertSame($transactions, CommissionBalanceTransaction::query()->count());
    }

    public function test_shelter_admission_does_not_restore_commission_of_an_explicitly_returned_contract(): void
    {
        foreach ([ContractStatusEnum::Back->value, ContractStatusEnum::Refunded->value] as $status) {
            $contract = Contract::query()->create(['status' => ContractStatusEnum::Shelter->value]);
            $contract->contract_status_logs()->create(['status' => ContractStatusEnum::Shelter->value, 'previous_status' => $status, 'date' => now()]);
            $this->assertFalse(app(RecruitmentCommissionService::class)->isEligible($contract->fresh()));
        }
    }

    public function test_approved_return_request_still_blocks_shelter_commission_but_pending_request_does_not(): void
    {
        $contract = new Contract(['status' => ContractStatusEnum::Shelter->value]);
        $contract->setRelation('latestShelterLog', null);
        $request = new \App\Models\ContractReturnRequest(['status' => 'pending']);
        $contract->setRelation('returnRequests', collect([$request]));
        $service = app(RecruitmentCommissionService::class);
        $this->assertTrue($service->isEligible($contract));
        $request->status = 'approved';
        $this->assertFalse($service->isEligible($contract));
    }

    public function test_zero_decision_closes_returned_commission_without_deduction(): void
    {
        $marketer = Admin::factory()->create();
        $contract = Contract::query()->create([
            'status' => ContractStatusEnum::New->value,
            'marketer_id' => $marketer->id,
        ]);
        $batch = CommissionBatch::query()->create([
            'marketer_id' => $marketer->id,
            'total_requests' => 1,
            'total_base_amount' => 10000,
            'total_commission' => 300,
            'status' => CommissionBatch::STATUS_APPROVED,
        ]);
        $item = CommissionBatchItem::query()->create([
            'batch_id' => $batch->id,
            'source_type' => CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT,
            'source_id' => $contract->id,
            'contract_id' => $contract->id,
            'service_type' => 'recruitment',
            'base_amount' => 10000,
            'method' => 'fixed',
            'rate_or_amount' => 300,
            'commission_amount' => 300,
        ]);
        CommissionBalanceTransaction::query()->create([
            'marketer_id' => $marketer->id,
            'type' => CommissionBalanceTransaction::TYPE_CREDIT,
            'amount' => 300,
            'reference_type' => 'commission_batch',
            'reference_id' => $batch->id,
            'balance_after' => 300,
        ]);

        $contract->update(['status' => ContractStatusEnum::Back->value]);
        app(RecruitmentCommissionService::class)->resolveSettlement(
            $item->fresh(),
            RecruitmentCommissionService::SETTLEMENT_ZEROED,
            $marketer->id
        );

        $item->refresh();
        $this->assertTrue(app(RecruitmentCommissionService::class)->isZeroed($item));
        $this->assertNull($item->commission_reversal_transaction_id);
        $this->assertDatabaseMissing('commission_balance_transactions', [
            'reference_type' => 'commission_zero_adjustment',
            'reference_id' => $item->id,
            'amount' => 300,
        ]);
        $this->assertSame(300.0, app(CommissionPaymentService::class)->getPaymentSummary($marketer->id)['balance']);
    }

    public function test_reversal_is_reported_separately_from_paid_commissions(): void
    {
        $marketer = Admin::factory()->create();
        CommissionBalanceTransaction::query()->create([
            'marketer_id' => $marketer->id,
            'type' => CommissionBalanceTransaction::TYPE_CREDIT,
            'amount' => 500,
            'reference_type' => 'commission_batch',
            'reference_id' => 1001,
            'balance_after' => 500,
        ]);
        CommissionBalanceTransaction::query()->create([
            'marketer_id' => $marketer->id,
            'type' => CommissionBalanceTransaction::TYPE_DEBIT,
            'amount' => 200,
            'reference_type' => 'commission_payment',
            'reference_id' => 1002,
            'balance_after' => 300,
        ]);
        CommissionBalanceTransaction::query()->create([
            'marketer_id' => $marketer->id,
            'type' => CommissionBalanceTransaction::TYPE_DEBIT,
            'amount' => 100,
            'reference_type' => 'commission_reversal',
            'reference_id' => 1003,
            'balance_after' => 200,
        ]);

        $summary = app(CommissionPaymentService::class)->getPaymentSummary($marketer->id);

        $this->assertSame(500.0, $summary['approved']);
        $this->assertSame(200.0, $summary['paid']);
        $this->assertSame(100.0, $summary['reversed']);
        $this->assertSame(200.0, $summary['balance']);
    }

    public function test_approval_rechecks_contract_status_after_preview(): void
    {
        $marketer = Admin::factory()->create();
        $contract = Contract::query()->create([
            'status' => ContractStatusEnum::Back->value,
            'marketer_id' => $marketer->id,
            'contract_cost' => 12000,
        ]);
        $key = CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT . ':' . $contract->id;
        $operationService = Mockery::mock(CommissionOperationService::class);
        $operationService->shouldReceive('findSelected')->once()->andReturn(collect([[
            'key' => $key,
            'source_type' => CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT,
            'source_id' => $contract->id,
            'service_type' => 'recruitment',
            'base_amount' => 12000,
        ]]));
        $calculation = new CommissionCalculationService(
            $operationService,
            Mockery::mock(DelegationCommissionService::class),
            app(RecruitmentCommissionService::class),
            Mockery::mock(CommissionAccrualService::class)
        );

        $this->expectException(ValidationException::class);
        $calculation->approveBatch(
            $marketer->id,
            [$key],
            [$key => ['method' => 'fixed', 'value' => 500]],
            null,
            $marketer->id
        );
    }

    /** @dataProvider settlementDecisions */
    public function test_returned_commission_is_closed_only_by_its_explicit_settlement(string $decision, ?float $amount, float $deducted): void
    {
        $marketer = Admin::factory()->create();
        $contract = Contract::query()->create([
            'status' => ContractStatusEnum::New->value,
            'marketer_id' => $marketer->id,
            'contract_date' => '2026-07-01',
        ]);
        $batch = CommissionBatch::query()->create([
            'marketer_id' => $marketer->id,
            'status' => CommissionBatch::STATUS_APPROVED,
            'total_commission' => 500,
            'approved_at' => '2026-07-31',
        ]);
        $item = CommissionBatchItem::query()->create([
            'batch_id' => $batch->id,
            'source_type' => CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT,
            'source_id' => $contract->id,
            'operation_date' => '2026-07-01',
            'service_type' => 'recruitment',
            'base_amount' => 12000,
            'method' => 'fixed',
            'rate_or_amount' => 500,
            'commission_amount' => 500,
        ]);
        // The earlier commission was already paid, leaving no balance.
        foreach (['credit', 'debit'] as $type) {
            CommissionBalanceTransaction::query()->create([
                'marketer_id' => $marketer->id, 'type' => $type, 'amount' => 500,
                'reference_type' => $type === 'credit' ? 'commission_batch' : 'commission_payment',
                'reference_id' => $batch->id, 'balance_after' => $type === 'credit' ? 500 : 0,
            ]);
        }
        $contract->update(['status' => ContractStatusEnum::Back->value]);
        $rental = \App\Models\RentalContract::query()->create(['marketer_id' => $marketer->id, 'status' => 'active']);
        $rentalKey = 'rental_contract:' . $rental->id;
        $operations = Mockery::mock(CommissionOperationService::class);
        $operations->shouldReceive('findSelected')->andReturn(collect([[
            'key' => $rentalKey, 'source_type' => 'rental_contract',
            'source_id' => $rental->id, 'service_type' => 'rental', 'base_amount' => 2000,
            'order_id' => null, 'contract_id' => $rental->id, 'reference' => (string) $rental->id,
            'client_name' => 'Test', 'operation_date' => '2026-10-01',
        ]]));
        $accrual = Mockery::mock(CommissionAccrualService::class);
        $accrual->shouldReceive('createForBatch')->once()->andReturn(new \Nami\Finance\Models\Constraint());
        $service = new CommissionCalculationService($operations,
            app(DelegationCommissionService::class), app(RecruitmentCommissionService::class), $accrual);
        $keys = [$rentalKey];
        $adjustments = [$keys[0] => ['method' => 'fixed', 'value' => 300]];
        $preview = $service->previewBatch($marketer->id, $keys, $adjustments);
        $this->assertSame(500.0, $preview['total_deductions']);
        $this->assertSame(300.0, $preview['net_commission']);
        $this->assertSame([$item->id], $preview['returned_deductions']->pluck('id')->all());
        $this->assertSame('إرجاع عاملة عقد الاستقدام', $preview['returned_deductions']->first()->deduction_reason);
        $this->assertSame('تاريخ الوصول غير مسجل؛ لا يمكن حساب فترة الضمان', $preview['returned_deductions']->first()->return_warranty_unknown_reason);
        $service->approveBatch($marketer->id, $keys, $adjustments, null, $marketer->id, [$item->id]);
        $this->assertSame(300.0, app(CommissionPaymentService::class)->getPaymentSummary($marketer->id)['balance']);
        $this->assertCount(1, app(RecruitmentCommissionService::class)->pendingDeductions($marketer->id));
        if ($decision === 'partial') {
            foreach ([0.0, -1.0, 500.0, 501.0] as $invalidAmount) {
                try {
                    app(RecruitmentCommissionService::class)->resolveSettlement($item, 'partial', $marketer->id, null, $invalidAmount);
                    $this->fail('Invalid partial deduction was accepted.');
                } catch (ValidationException $exception) {
                    $this->assertArrayHasKey('amount', $exception->errors());
                }
            }
            $this->assertSame(300.0, app(CommissionPaymentService::class)->getPaymentSummary($marketer->id)['balance']);
        }
        app(RecruitmentCommissionService::class)->resolveSettlement($item, $decision, $marketer->id, null, $amount);
        $this->assertSame(300.0 - $deducted, app(CommissionPaymentService::class)->getPaymentSummary($marketer->id)['balance']);
        $this->assertCount(0, app(RecruitmentCommissionService::class)->pendingDeductions($marketer->id));
        $this->assertSame($deducted > 0 ? 1 : 0, CommissionBalanceTransaction::query()
            ->where('reference_type', 'commission_reversal')->where('reference_id', $item->id)->count());
        if ($deducted > 0) $this->assertSame($deducted, (float) $item->fresh()->reversalTransaction->amount);
        $this->assertSame(0.0, $service->previewBatch($marketer->id, $keys, $adjustments)['total_deductions']);
        $this->expectException(ValidationException::class);
        app(RecruitmentCommissionService::class)->resolveSettlement($item, $decision, $marketer->id, null, $amount);
    }

    public static function settlementDecisions(): array
    {
        return [['deducted', null, 500.0], ['partial', 125.0, 125.0], ['zeroed', null, 0.0]];
    }

    public function test_recruitment_warranty_is_calculated_from_arrival_without_saved_warranty_dates(): void
    {
        $contract = new Contract(['status' => ContractStatusEnum::Refunded->value]);
        $contract->setRelation('info', new \App\Models\ContractInfo(['arrival_date' => '2026-07-01']));
        $contract->setRelation('maid', null);
        $contract->setRelation('travelHistory', null);
        $service = app(RecruitmentCommissionService::class);
        $resolve = new \ReflectionMethod($service, 'contractWithResolvedWarranty');
        $resolve->setAccessible(true);
        $resolved = $resolve->invoke($service, $contract);
        $this->assertSame('2026-07-01', $resolved->warranty_start_date->format('Y-m-d'));
        $this->assertSame(\Carbon\Carbon::parse('2026-07-01')->addDays(\App\Support\AutomationSettings::warrantyPeriodDays())->format('Y-m-d'), $resolved->warranty_end_date->format('Y-m-d'));
        $this->assertNull($contract->warranty_start_date);
        $status = new \ReflectionMethod($service, 'returnWarrantyStatus');
        $status->setAccessible(true);
        $this->assertSame('inside', $status->invoke($service, $resolved, $resolved->warranty_end_date->copy()));
        $this->assertSame('outside', $status->invoke($service, $resolved, $resolved->warranty_end_date->copy()->addDay()));
    }
}
