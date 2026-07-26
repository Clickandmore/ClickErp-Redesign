<?php

namespace Tests\Unit;

use App\Support\RentalContractPricing;
use PHPUnit\Framework\TestCase;

class RentalContractPricingTest extends TestCase
{
    public function test_salary_is_exempt_for_the_full_number_of_days(): void
    {
        $amounts = RentalContractPricing::fromNetPrice(1320, 346.67, 90, 3, 1400);

        $this->assertSame(1040.01, $amounts['salary_exempt_amount']);
        $this->assertSame(279.99, $amounts['taxable_subtotal']);
        $this->assertSame(42.0, $amounts['vat_amount']);
        $this->assertSame(1362.0, $amounts['total_due']);
        $this->assertSame(80.0, $amounts['discount']);
    }

    public function test_zero_salary_keeps_full_package_vat(): void
    {
        $amounts = RentalContractPricing::fromNetPrice(1320, 0, 90);

        $this->assertSame(0.0, $amounts['salary_exempt_amount']);
        $this->assertSame(1320.0, $amounts['taxable_subtotal']);
        $this->assertSame(198.0, $amounts['vat_amount']);
        $this->assertSame(1518.0, $amounts['total_due']);
    }

    public function test_partial_month_salary_is_prorated(): void
    {
        $amounts = RentalContractPricing::fromNetPrice(1000, 900, 14);

        $this->assertSame(420.0, $amounts['salary_exempt_amount']);
        $this->assertSame(580.0, $amounts['taxable_subtotal']);
        $this->assertSame(87.0, $amounts['vat_amount']);
        $this->assertSame(1087.0, $amounts['total_due']);
    }

    public function test_inclusive_package_keeps_agreed_total_and_reduces_only_vat(): void
    {
        $amounts = RentalContractPricing::fromGrossPrice(3300, 1500, 30);

        $this->assertSame(1500.0, $amounts['salary_exempt_amount']);
        $this->assertSame(1565.22, $amounts['taxable_subtotal']);
        $this->assertSame(234.78, $amounts['vat_amount']);
        $this->assertSame(3300.0, $amounts['total_due']);
    }
}
