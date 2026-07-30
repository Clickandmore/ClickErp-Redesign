<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\RentContractController;
use App\Models\RentalContract;
use App\Models\RentalContractExtension;
use App\Models\RentalContractInfo;
use App\Models\ingaz\Package;
use Carbon\Carbon;
use Illuminate\Support\Collection;
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

    private function buildSummary(
        float $paidAmount = 0.0,
        string $paymentStatus = 'pending',
        array $extensions = [],
        string $closureDate = '2026-07-29',
        string $endDate = '2026-10-17'
    ): array {
        $package = new Package([
            'days_count' => 30,
            'price_with_tax' => 1565.00,
            'rental_type' => 'monthly',
            'duration_unit' => 'month',
        ]);

        $info = new RentalContractInfo([
            'package_id' => 5,
            'monthly_cost' => 1565.00,
            'contract_duration_months' => 1,
            'contract_duration_days' => 30,
            'contract_value' => 1565.00,
            'paid_amount' => $paidAmount,
            'payment_status' => $paymentStatus,
            'ingaz_from' => '2026-07-19',
            'ingaz_to' => $endDate,
        ]);
        $info->setRelation('package', $package);

        $contract = new FakeAdjustmentsRentalContract();
        $contract->setRelation('info', $info);
        $contract->setRelation('order', null);
        $contract->setRelation('maid', null);
        $contract->setRelation('financeInvoice', null);
        $contract->setRelation('financialAdjustments', new Collection());
        $contract->setRelation('extensions', new Collection(array_map(
            fn (array $attributes) => new RentalContractExtension($attributes),
            $extensions
        )));

        $method = new ReflectionMethod(RentContractController::class, 'buildRentalClosureSummary');
        $method->setAccessible(true);

        return $method->invoke(
            app(RentContractController::class),
            $contract,
            Carbon::parse($closureDate)
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
