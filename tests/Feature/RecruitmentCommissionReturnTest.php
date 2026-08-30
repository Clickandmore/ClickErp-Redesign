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
use App\Services\commissions\DelegationCommissionService;
use App\Services\commissions\RecruitmentCommissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class RecruitmentCommissionReturnTest extends TestCase
{
    use DatabaseTransactions;

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
        $this->assertDatabaseHas('commission_balance_transactions', [
            'reference_type' => 'commission_zero_adjustment',
            'reference_id' => $item->id,
            'amount' => 300,
        ]);
        $this->assertSame(0.0, app(CommissionPaymentService::class)->getPaymentSummary($marketer->id)['balance']);
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
            app(RecruitmentCommissionService::class)
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
}
