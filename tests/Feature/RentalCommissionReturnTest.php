<?php

namespace Tests\Feature;

use App\Models\auth\Admin;
use App\Models\RentalContract;
use App\Models\RentalContractExtension;
use App\Models\RentalFinancialAdjustment;
use App\Models\commissions\CommissionBatch;
use App\Models\commissions\CommissionBatchItem;
use App\Models\commissions\CommissionBalanceTransaction;
use App\Services\commissions\CommissionOperationService;
use App\Services\commissions\RecruitmentCommissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RentalCommissionReturnTest extends TestCase
{
    use DatabaseTransactions;

    private function fixture(string $status = 'cancelled'): array
    {
        $admin = Admin::factory()->create();
        $contract = RentalContract::query()->create([
            'marketer_id' => $admin->id, 'status' => $status,
            'contract_date' => '2026-07-01', 'closed_at' => $status === 'cancelled' ? '2026-10-05' : null,
        ]);
        $batch = CommissionBatch::query()->create([
            'marketer_id' => $admin->id, 'status' => CommissionBatch::STATUS_APPROVED,
            'total_commission' => 500, 'approved_at' => '2026-07-31',
        ]);
        $item = CommissionBatchItem::query()->create([
            'batch_id' => $batch->id, 'source_type' => CommissionOperationService::SOURCE_RENTAL_CONTRACT,
            'source_id' => $contract->id, 'contract_id' => $contract->id,
            'service_type' => 'rental', 'operation_date' => '2026-07-01',
            'base_amount' => 5000, 'method' => 'fixed', 'rate_or_amount' => 500, 'commission_amount' => 500,
        ]);
        return [$admin, $contract, $batch, $item];
    }

    public function test_previous_month_rental_commission_appears_under_its_return_month_and_type(): void
    {
        [$admin, $contract, , $item] = $this->fixture();
        $service = app(RecruitmentCommissionService::class);
        $filters = ['service_type' => 'rental', 'date_filter' => 'month_year', 'year' => 2026, 'month' => 10];
        $pending = $service->pendingDeductions($admin->id, $filters);
        $this->assertSame([$item->id], $pending->pluck('id')->all());
        $this->assertSame('إلغاء عقد التأجير', $pending->first()->deduction_reason);
        $this->assertSame('rental:' . $contract->id, $pending->first()->deduction_contract_key);
        $this->assertSame(url('/admin/RentContract/' . $contract->id . '/details'), $pending->first()->deduction_contract_url);
        $this->assertCount(0, $service->pendingDeductions($admin->id, array_merge($filters, ['month' => 7])));
        $this->assertCount(0, $service->pendingDeductions($admin->id, ['service_type' => 'recruitment']));
        $this->assertCount(1, $service->pendingDeductions($admin->id, ['date_filter' => 'range', 'from_date' => '2026-10-05', 'to_date' => '2026-10-05']));
    }

    /** @dataProvider decisions */
    public function test_rental_settlement_closes_once_with_the_selected_amount(string $decision, ?float $amount, float $deduction): void
    {
        [$admin, , , $item] = $this->fixture();
        $service = app(RecruitmentCommissionService::class);
        $service->resolveSettlement($item, $decision, $admin->id, null, $amount);
        $this->assertCount(0, $service->pendingDeductions($admin->id));
        $transactions = CommissionBalanceTransaction::query()->where('reference_type', 'commission_reversal')->where('reference_id', $item->id)->get();
        $this->assertCount($deduction > 0 ? 1 : 0, $transactions);
        if ($deduction > 0) {
            $this->assertSame($deduction, (float) $transactions->first()->amount);
            $this->assertSame(-$deduction, (float) $transactions->first()->balance_after);
            $this->assertStringContainsString('عقد إيجار', $transactions->first()->notes);
        } else {
            $this->assertTrue($service->isZeroed($item->fresh()));
        }
        $this->expectException(ValidationException::class);
        $service->resolveSettlement($item, $decision, $admin->id, null, $amount);
    }

    public static function decisions(): array
    {
        return [['deducted', null, 500.0], ['partial', 125.0, 125.0], ['zeroed', null, 0.0]];
    }

    public function test_normal_completion_is_excluded_but_positive_refund_is_included(): void
    {
        [$admin, $contract, , $item] = $this->fixture('completed');
        $contract->update(['closed_at' => '2026-07-31']);
        $service = app(RecruitmentCommissionService::class);
        $this->assertCount(0, $service->pendingDeductions($admin->id));
        RentalFinancialAdjustment::query()->create([
            'rental_contract_id' => $contract->id, 'type' => 'income', 'context' => 'closure', 'amount' => 100,
        ]);
        $this->assertCount(0, $service->pendingDeductions($admin->id));
        RentalFinancialAdjustment::query()->create([
            'rental_contract_id' => $contract->id, 'type' => 'expense', 'context' => 'closure', 'amount' => 100, 'issue_date' => '2026-10-05',
        ]);
        $this->assertSame([$item->id], $service->pendingDeductions($admin->id)->pluck('id')->all());
        $this->assertStringContainsString('تسجيل استرداد للعميل', $service->pendingDeductions($admin->id)->first()->deduction_reason);
        $this->assertStringContainsString('100.00 ر.س', $service->pendingDeductions($admin->id)->first()->deduction_reason);
        $this->assertCount(1, $service->pendingDeductions($admin->id, ['date_filter' => 'month_year', 'month' => 10, 'year' => 2026]));
        $this->assertCount(0, $service->pendingDeductions($admin->id, ['date_filter' => 'month_year', 'month' => 7, 'year' => 2026]));
        $service->resolveSettlement($item, 'partial', $admin->id, null, 25);
        $this->assertCount(0, $service->pendingDeductions($admin->id));
    }

    public function test_extension_commissions_use_the_parent_rental_contract_and_are_settled_separately(): void
    {
        [$admin, $contract, $batch, $base] = $this->fixture();
        $extension = RentalContractExtension::query()->create([
            'rental_contract_id' => $contract->id, 'extension_days' => 30, 'extension_value' => 1000,
            'start_date' => '2026-08-01', 'end_date' => '2026-08-30',
        ]);
        $item = CommissionBatchItem::query()->create([
            'batch_id' => $batch->id, 'source_type' => CommissionOperationService::SOURCE_RENTAL_EXTENSION_PERIOD_PREFIX . '1',
            'source_id' => $extension->id, 'contract_id' => $contract->id, 'service_type' => 'rental',
            'base_amount' => 1000, 'method' => 'fixed', 'rate_or_amount' => 100, 'commission_amount' => 100,
        ]);
        $service = app(RecruitmentCommissionService::class);
        $pending = $service->pendingDeductions($admin->id);
        $this->assertCount(2, $pending);
        $this->assertCount(1, $pending->pluck('deduction_contract_key')->unique());
        $this->assertSame($contract->id, $pending->firstWhere('id', $item->id)->deduction_contract_id);
        $service->resolveSettlement($item, 'deducted', $admin->id);
        $this->assertSame([$base->id], $service->pendingDeductions($admin->id)->pluck('id')->all());
        $this->assertSame(100.0, (float) $item->fresh()->reversalTransaction->amount);
    }

    public function test_active_contract_without_refund_cannot_be_settled(): void
    {
        [$admin, , , $item] = $this->fixture('active');
        $this->expectException(ValidationException::class);
        app(RecruitmentCommissionService::class)->resolveSettlement($item, 'deducted', $admin->id);
    }

    /** @dataProvider warrantyPeriods */
    public function test_warranty_is_evaluated_at_return_date_including_boundary_days(?string $start, ?string $end, string $expected): void
    {
        [$admin, $contract] = $this->fixture();
        $contract->update(['warranty_start_date' => $start, 'warranty_end_date' => $end, 'warranty_status' => 'expired']);
        $item = app(RecruitmentCommissionService::class)->pendingDeductions($admin->id)->first();
        $this->assertSame($expected, $item->return_warranty_status);
        if (!$start && !$end) {
            $this->assertSame('بداية ونهاية الضمان غير مسجلتين', $item->return_warranty_unknown_reason);
        } elseif ($expected === 'unknown') {
            $this->assertSame('تواريخ الضمان غير صحيحة: النهاية قبل البداية', $item->return_warranty_unknown_reason);
        } else {
            $this->assertNull($item->return_warranty_unknown_reason);
        }
    }

    public static function warrantyPeriods(): array
    {
        return [
            ['2026-10-01', '2026-10-05', 'inside'],
            ['2026-10-05', '2026-10-10', 'inside'],
            ['2026-07-01', '2026-10-04', 'outside'],
            ['2026-10-06', '2026-10-10', 'outside'],
            [null, null, 'unknown'],
            ['2026-10-10', '2026-10-01', 'unknown'],
        ];
    }
}
