<?php

namespace Tests\Unit;

use App\Models\ingaz\Package;
use App\Models\RentalContract;
use App\Models\RentalContractInfo;
use App\Services\ingaz\RentalContractFinanceService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class RentalContractFinanceServiceTest extends TestCase
{
    public function test_it_excludes_worker_salary_from_rental_vat(): void
    {
        $amounts = $this->buildInvoiceAmounts([
            'monthly_salary' => 1000,
            'contract_duration_months' => 1,
            'contract_duration_days' => 30,
            'contract_value' => 1990,
        ]);

        $this->assertSame(1000.0, $amounts['salary_exempt_amount']);
        $this->assertSame(860.87, $amounts['taxable_subtotal']);
        $this->assertSame(1860.87, $amounts['net_subtotal']);
        $this->assertSame(129.13, $amounts['vat_amount']);
        $this->assertSame(1990.0, $amounts['total_due']);
    }

    public function test_it_preserves_package_pricing_when_no_salary_is_available(): void
    {
        $package = $this->package([
            'id' => 3,
            'original_price' => 1400,
            'price_after_discount' => 1320,
            'price_with_tax' => 1466,
        ]);

        $amounts = $this->buildInvoiceAmounts([
            'package_id' => 3,
            'monthly_salary' => 0,
            'contract_duration_months' => 3,
            'contract_duration_days' => 90,
            'contract_value' => 1466,
        ], $package);

        $this->assertSame(0.0, $amounts['salary_exempt_amount']);
        $this->assertSame(1400.0, $amounts['subtotal']);
        $this->assertSame(80.0, $amounts['discount']);
        $this->assertSame(1320.0, $amounts['net_subtotal']);
        $this->assertSame(146.0, $amounts['vat_amount']);
    }

    public function test_it_uses_entered_salary_amount_without_multiplying_by_duration(): void
    {
        $amounts = $this->buildInvoiceAmounts([
            'monthly_salary' => 1000,
            'contract_duration_months' => 3,
            'contract_duration_days' => 90,
            'contract_value' => 1466,
        ]);

        $this->assertSame(1000.0, $amounts['salary_exempt_amount']);
        $this->assertSame(405.22, $amounts['taxable_subtotal']);
        $this->assertSame(60.78, $amounts['vat_amount']);
        $this->assertSame(1466.0, $amounts['total_due']);
    }

    private function buildInvoiceAmounts(array $infoAttributes, ?Package $package = null): array
    {
        $contract = new RentalContract();
        $contract->setRelation('info', new RentalContractInfo($infoAttributes));
        $contract->setRelation('maid', null);

        $reflection = new ReflectionClass(RentalContractFinanceService::class);
        $service = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('buildInvoiceAmounts');
        $method->setAccessible(true);

        return $method->invoke($service, $contract, $package);
    }

    private function package(array $attributes): Package
    {
        return new class($attributes) extends Package {
            public function __construct(private array $testAttributes)
            {
            }

            public function getAttribute($key)
            {
                return $this->testAttributes[$key] ?? null;
            }
        };
    }
}
