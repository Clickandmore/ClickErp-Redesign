<?php

namespace Tests\Unit;

use App\Models\RentalContractExtension;
use Carbon\Carbon;
use Tests\TestCase;

class RentalContractExtensionCommissionPeriodsTest extends TestCase
{
    public function test_one_month_extension_is_one_commission_period_in_its_service_month(): void
    {
        $extension = new RentalContractExtension([
            'extension_months' => 1,
            'extension_days' => 30,
            'extension_value' => 1900,
            'start_date' => '2026-09-22',
        ]);
        $extension->created_at = Carbon::parse('2026-09-02');

        $periods = $extension->commissionPeriods();

        $this->assertCount(1, $periods);
        $this->assertSame('2026-09-22', $periods[0]['start_date']->toDateString());
        $this->assertSame('2026-10-21', $periods[0]['end_date']->toDateString());
        $this->assertSame(1900.0, $periods[0]['amount']);
    }

    public function test_backdated_two_month_extension_is_attributed_to_each_service_period(): void
    {
        $extension = new RentalContractExtension([
            'extension_months' => 2,
            'extension_days' => 60,
            'extension_value' => 3800,
            'start_date' => '2026-08-15',
        ]);
        $extension->created_at = Carbon::parse('2026-09-20');

        $periods = $extension->commissionPeriods();

        $this->assertCount(2, $periods);
        $this->assertSame('2026-08-15', $periods[0]['start_date']->toDateString());
        $this->assertSame('2026-09-14', $periods[1]['start_date']->toDateString());
        $this->assertSame(1900.0, $periods[0]['amount']);
        $this->assertSame(1900.0, $periods[1]['amount']);
    }

    public function test_partial_period_keeps_the_exact_extension_invoice_total(): void
    {
        $extension = new RentalContractExtension([
            'extension_months' => 1,
            'extension_days' => 45,
            'extension_value' => 2850.01,
            'start_date' => '2026-08-15',
        ]);

        $periods = $extension->commissionPeriods();

        $this->assertCount(2, $periods);
        $this->assertSame(2850.01, round(array_sum(array_column($periods, 'amount')), 2));
        $this->assertSame(30, $periods[0]['days']);
        $this->assertSame(15, $periods[1]['days']);
    }
}
