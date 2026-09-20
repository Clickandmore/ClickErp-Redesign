<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\RentContractController;
use App\Models\RentalContract;
use App\Models\RentalContractExtension;
use App\Models\RentalFinancialAdjustment;
use App\Models\RentalContractInfo;
use App\Models\ingaz\Package;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class RentalContractClosureSummaryTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * The reported case: a 1-month contract worth 1,565.00 extended by two
     * months, then cancelled on day 11 with nothing paid. The settlement used
     * to price it as a 1-month contract and propose refunding 1,565.00.
     */
    public function test_an_extended_contract_is_settled_over_its_full_period(): void
    {
        $summary = $this->buildSummary(
            extensions: [
                ['extension_days' => 60, 'extension_value' => 3130.00, 'payment_amount' => null],
            ],
            closureDate: '2026-07-29'
        );

        $this->assertSame(90, $summary['duration_days']);
        $this->assertSame(4695.00, round($summary['contract_value'], 2));
        $this->assertSame(11, $summary['used_days']);
        $this->assertSame(80, $summary['remaining_days_value']);
        $this->assertSame(52.17, $summary['daily_rate']);
        $this->assertSame(573.83, $summary['used_cost']);
    }

    public function test_an_unpaid_extended_contract_is_never_refunded(): void
    {
        $summary = $this->buildSummary(
            extensions: [
                ['extension_days' => 60, 'extension_value' => 3130.00, 'payment_amount' => null],
            ],
            closureDate: '2026-07-29'
        );

        $this->assertSame(0.0, $summary['calculated_refund_value']);
        $this->assertSame(573.83, $summary['calculated_company_due_value']);
        $this->assertSame('company_due', $summary['settlement_type']);
        $this->assertSame(4121.17, $summary['calculated_credit_note_value']);
        $this->assertSame(0.0, $summary['cash_refund_value']);
        $this->assertSame(573.83, $summary['cash_to_collect_value']);
        $this->assertSame('refund', $summary['document_type']);
        $this->assertSame(4121.17, $summary['document_value']);
    }

    public function test_early_closure_of_an_unpaid_contract_credits_unused_days_and_collects_only_used_days(): void
    {
        $summary = $this->buildSummary(
            paidAmount: 0,
            closureDate: '2026-08-10',
            startDate: '2026-08-01',
            endDate: '2026-08-30',
            contractValue: 1900.00
        );

        $this->assertSame(10, $summary['used_days']);
        $this->assertSame(633.33, $summary['used_cost']);
        $this->assertSame('refund', $summary['document_type']);
        $this->assertSame(1266.67, $summary['calculated_credit_note_value']);
        $this->assertSame(0.0, $summary['cash_refund_value']);
        $this->assertSame(633.33, $summary['cash_to_collect_value']);
    }

    public function test_early_closure_of_a_partially_paid_contract_does_not_treat_the_credit_note_as_cash_refund(): void
    {
        $summary = $this->buildSummary(
            paidAmount: 500.00,
            paymentStatus: 'partial',
            closureDate: '2026-08-10',
            startDate: '2026-08-01',
            endDate: '2026-08-30',
            contractValue: 1900.00
        );

        $this->assertSame(1266.67, $summary['calculated_credit_note_value']);
        $this->assertSame(0.0, $summary['cash_refund_value']);
        $this->assertSame(133.33, $summary['cash_to_collect_value']);
    }

    public function test_the_legacy_complete_action_uses_the_unified_financial_close_flow(): void
    {
        Carbon::setTestNow('2026-08-10 12:00:00');
        $request = Request::create('/admin/RentContract/77/complete', 'POST');
        $financeService = Mockery::mock(\App\Services\ingaz\RentalContractFinanceService::class);
        $expectedResponse = response()->json(['success' => true]);
        $controller = Mockery::mock(RentContractController::class)->makePartial();

        $controller->shouldReceive('closeAndMakeAvailable')
            ->once()
            ->with(77, $request, $financeService)
            ->andReturn($expectedResponse);

        $actualResponse = $controller->completeContract($request, 77, $financeService);

        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame('2026-08-10', $request->input('closure_date'));
        $this->assertSame(0, $request->input('adjustment_amount'));
    }

    public function test_a_paid_extension_is_refunded_down_to_the_used_days(): void
    {
        $summary = $this->buildSummary(
            paidAmount: 1565.00,
            paymentStatus: 'paid',
            extensions: [
                ['extension_days' => 60, 'extension_value' => 3130.00, 'payment_amount' => 3130.00],
            ],
            closureDate: '2026-07-29'
        );

        $this->assertSame(4695.00, round($summary['paid_amount'], 2));
        $this->assertSame(4121.17, $summary['calculated_refund_value']);
        $this->assertSame(0.0, $summary['calculated_company_due_value']);
        $this->assertSame(4121.17, $summary['calculated_credit_note_value']);
        $this->assertSame(4121.17, $summary['cash_refund_value']);
    }

    public function test_a_contract_without_extensions_keeps_its_original_period(): void
    {
        $summary = $this->buildSummary(
            paidAmount: 1565.00,
            paymentStatus: 'paid',
            extensions: [],
            closureDate: '2026-07-29',
            endDate: '2026-08-18'
        );

        $this->assertSame(30, $summary['duration_days']);
        $this->assertSame(1565.00, round($summary['contract_value'], 2));
        $this->assertSame(11, $summary['used_days']);
        $this->assertSame(991.17, $summary['calculated_refund_value']);
    }

    public function test_a_legacy_paid_extension_uses_the_original_daily_rate(): void
    {
        $summary = $this->buildSummary(
            paidAmount: 3500.00,
            paymentStatus: 'paid',
            extensions: [
                [
                    'extension_days' => 30,
                    'extension_value' => null,
                    'payment_amount' => null,
                    'payment_status' => 'paid',
                ],
            ],
            closureDate: '2026-08-11',
            startDate: '2026-05-04',
            endDate: '2026-07-03',
            contractValue: 3500.00
        );

        $this->assertSame(60, $summary['duration_days']);
        $this->assertSame(7000.00, $summary['contract_value']);
        $this->assertSame(7000.00, $summary['paid_amount']);
        $this->assertSame(3500.00, $summary['extension_value']);
        $this->assertSame(116.67, $summary['daily_rate']);
    }

    public function test_a_previous_full_credit_note_is_net_against_the_required_closure_note(): void
    {
        $summary = $this->buildSummary(
            paidAmount: 1700.00,
            paymentStatus: 'paid',
            closureDate: '2026-08-24',
            endDate: '2026-08-28',
            startDate: '2026-07-29',
            contractValue: 1700.00,
            adjustments: [
                ['type' => 'expense', 'context' => 'manual_credit_note', 'amount' => 1700.00],
            ]
        );

        $this->assertSame(170.00, $summary['target_credit_note_value']);
        $this->assertSame(0.0, $summary['calculated_credit_note_value']);
        $this->assertSame(1530.00, $summary['calculated_debit_note_value']);
        $this->assertSame('company_due', $summary['document_type']);
        $this->assertSame(1530.00, $summary['document_value']);
        $this->assertSame(170.00, $summary['cash_refund_value']);
    }

    /**
     * The `contract_*` columns must keep describing the contract as sold, or
     * rebuilding the settlement would add the extensions a second time.
     */
    public function test_the_base_contract_figures_are_reported_separately(): void
    {
        $summary = $this->buildSummary(
            extensions: [
                ['extension_days' => 60, 'extension_value' => 3130.00, 'payment_amount' => null],
            ],
            closureDate: '2026-07-29'
        );

        $this->assertSame(30, $summary['base_duration_days']);
        $this->assertSame(1565.00, round($summary['base_contract_value'], 2));
        $this->assertSame(60, $summary['extension_days']);
        $this->assertSame(3130.00, $summary['extension_value']);
    }

    /** @dataProvider fullDailyDiscountProvider */
    public function test_a_full_daily_discount_clears_an_unpaid_contract(string $discountType, float $discountValue, string $closureDate): void
    {
        $summary = $this->buildSummary(
            closureDate: $closureDate,
            startDate: '2026-08-01',
            endDate: '2026-08-30',
            contractValue: 1500.00,
            dailyDiscountType: $discountType,
            dailyDiscountValue: $discountValue
        );

        $this->assertSame(0.0, $summary['adjusted_daily_rate']);
        $this->assertSame(0.0, $summary['earned_amount']);
        $this->assertSame(0.0, $summary['cash_to_collect_value']);
        $this->assertSame(0.0, $summary['cash_refund_value']);
        $this->assertSame(1500.00, $summary['calculated_credit_note_value']);
    }

    public function fullDailyDiscountProvider(): array
    {
        return [
            'percentage, early closure' => ['percentage', 100.0, '2026-08-10'],
            'percentage, overdue closure' => ['percentage', 100.0, '2026-09-05'],
            'fixed, early closure' => ['fixed', 50.0, '2026-08-10'],
        ];
    }

    private function buildSummary(
        float $paidAmount = 0.0,
        string $paymentStatus = 'pending',
        array $extensions = [],
        string $closureDate = '2026-07-29',
        string $endDate = '2026-10-17',
        string $startDate = '2026-07-19',
        float $contractValue = 1565.00,
        array $adjustments = [],
        ?string $dailyDiscountType = null,
        float $dailyDiscountValue = 0.0
    ): array {
        $package = new Package([
            'days_count' => 30,
            'price_with_tax' => 1565.00,
            'rental_type' => 'monthly',
            'duration_unit' => 'month',
        ]);

        $info = new RentalContractInfo([
            'package_id' => 5,
            'monthly_cost' => $contractValue,
            'contract_duration_months' => 1,
            'contract_duration_days' => 30,
            'contract_value' => $contractValue,
            'paid_amount' => $paidAmount,
            'payment_status' => $paymentStatus,
            'ingaz_from' => $startDate,
            'ingaz_to' => $endDate,
        ]);
        $info->setRelation('package', $package);

        $contract = new FakeAdjustmentsRentalContract();
        $contract->setRelation('info', $info);
        $contract->setRelation('order', null);
        $contract->setRelation('maid', null);
        $contract->setRelation('financeInvoice', null);
        $contract->setRelation('financialAdjustments', new Collection(array_map(
            fn (array $attributes) => new RentalFinancialAdjustment($attributes),
            $adjustments
        )));
        $contract->setRelation('extensions', new Collection(array_map(
            fn (array $attributes) => new RentalContractExtension($attributes),
            $extensions
        )));

        $method = new ReflectionMethod(RentContractController::class, 'buildRentalClosureSummary');
        $method->setAccessible(true);

        return $method->invoke(
            app(RentContractController::class),
            $contract,
            Carbon::parse($closureDate),
            $dailyDiscountType,
            $dailyDiscountValue
        );
    }
}

/**
 * The settlement reads previously recorded closure adjustments straight from
 * the database. None exist for a contract being closed for the first time.
 */
class FakeAdjustmentsRentalContract extends RentalContract
{
    public function financialAdjustments()
    {
        return new FakeAdjustmentsQuery();
    }
}

class FakeAdjustmentsQuery
{
    public function where(): self
    {
        return $this;
    }

    public function whereNull(): self
    {
        return $this;
    }

    public function sum(): float
    {
        return 0.0;
    }
}
