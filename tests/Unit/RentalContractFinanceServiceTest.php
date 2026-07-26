<?php

namespace Tests\Unit;

use App\Models\ingaz\Package;
use App\Models\RentalContract;
use App\Models\RentalContractInfo;
use App\Models\RentalContractInvoice;
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
            'package_price_basis' => 'net',
            'package_price_after_discount' => 1320,
            'contract_value' => 1990,
        ]);

        $this->assertSame(1000.0, $amounts['salary_exempt_amount']);
        $this->assertSame(320.0, $amounts['taxable_subtotal']);
        $this->assertSame(1320.0, $amounts['net_subtotal']);
        $this->assertSame(48.0, $amounts['vat_amount']);
        $this->assertSame(1368.0, $amounts['total_due']);
    }

    public function test_it_applies_full_vat_to_net_package_price_when_no_salary_is_available(): void
    {
        $package = $this->package([
            'id' => 3,
            'original_price' => 1400,
            'price_after_discount' => 1320,
            'price_with_tax' => 1518,
        ]);

        $amounts = $this->buildInvoiceAmounts([
            'package_id' => 3,
            'monthly_salary' => 0,
            'contract_duration_months' => 3,
            'contract_duration_days' => 90,
            'contract_value' => 1518,
        ], $package);

        $this->assertSame(0.0, $amounts['salary_exempt_amount']);
        $this->assertSame(1400.0, $amounts['subtotal']);
        $this->assertSame(80.0, $amounts['discount']);
        $this->assertSame(1320.0, $amounts['net_subtotal']);
        $this->assertSame(198.0, $amounts['vat_amount']);
        $this->assertSame(1518.0, $amounts['total_due']);
    }

    public function test_it_multiplies_monthly_salary_exemption_by_contract_duration(): void
    {
        $amounts = $this->buildInvoiceAmounts([
            'monthly_salary' => 346.67,
            'contract_duration_months' => 3,
            'contract_duration_days' => 90,
            'package_price_basis' => 'net',
            'package_price_after_discount' => 1320,
            'contract_value' => 1466,
        ]);

        $this->assertSame(1040.01, $amounts['salary_exempt_amount']);
        $this->assertSame(279.99, $amounts['taxable_subtotal']);
        $this->assertSame(42.0, $amounts['vat_amount']);
        $this->assertSame(1362.0, $amounts['total_due']);
    }

    public function test_it_prorates_salary_and_service_for_an_extension(): void
    {
        $contract = new RentalContract();
        $info = new RentalContractInfo([
            'monthly_salary' => 346.67,
            'contract_duration_months' => 3,
            'contract_duration_days' => 90,
            'package_price_basis' => 'net',
            'package_price_after_discount' => 1320,
            'contract_value' => 1362,
        ]);
        $info->setRelation('package', null);
        $contract->setRelation('info', $info);
        $contract->setRelation('maid', null);

        $reflection = new ReflectionClass(RentalContractFinanceService::class);
        $service = $reflection->newInstanceWithoutConstructor();
        $amounts = $service->extensionAmounts($contract, 30);

        $this->assertSame(346.67, $amounts['salary_exempt_amount']);
        $this->assertSame(93.33, $amounts['taxable_subtotal']);
        $this->assertSame(14.0, $amounts['vat_amount']);
        $this->assertSame(454.0, $amounts['total_due']);
    }

    public function test_it_recovers_imported_invoice_total_from_paid_and_remaining_balance(): void
    {
        $amounts = $this->buildInvoiceAmounts([
            'contract_value' => 0,
            'paid_amount' => 0,
            'remaining_amount' => 3335,
            'package_price_after_discount' => 0,
            'package_total_with_tax' => 0,
            'monthly_salary' => 0,
        ]);

        $this->assertSame(3335.0, $amounts['total_due']);
        $this->assertSame(2900.0, $amounts['subtotal']);
        $this->assertSame(435.0, $amounts['vat_amount']);
        $this->assertSame(3335.0, $amounts['remaining_amount']);
    }

    public function test_it_labels_worker_salary_in_constraint_statement(): void
    {
        $contract = new RentalContract();
        $contract->setRelation('maid', (object) ['name' => 'AYNALEM KEBEDE NEGASH']);

        $reflection = new ReflectionClass(RentalContractFinanceService::class);
        $service = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('buildWorkerSalaryStatement');
        $method->setAccessible(true);

        $statement = $method->invoke($service, 'قيد فاتورة عقد تأجير رقم 147', $contract, 1000);

        $this->assertStringContainsString('مرتب العامل (معفى ضريبياً)', $statement);
        $this->assertStringContainsString('AYNALEM KEBEDE NEGASH', $statement);
        $this->assertStringContainsString('1000.00 ريال', $statement);
    }

    public function test_it_splits_early_closure_credit_note_between_revenue_salary_and_vat(): void
    {
        $contract = new RentalContract();
        $info = new RentalContractInfo([
            'monthly_salary' => 900,
            'contract_duration_months' => 1,
            'contract_duration_days' => 30,
            'contract_value' => 1890.26,
        ]);
        $info->setRelation('package', null);
        $contract->setRelation('info', $info);
        $contract->setRelation('maid', null);

        $invoice = new RentalContractInvoice([
            'total_due' => 1890.26,
            'vat_amount' => 129.13,
        ]);

        $reflection = new ReflectionClass(RentalContractFinanceService::class);
        $service = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('closureAdjustmentAmounts');
        $method->setAccessible(true);

        $amounts = $method->invoke($service, $invoice, $contract, 1701.14);

        $this->assertSame(1701.14, $amounts['total']);
        $this->assertSame(
            1701.14,
            round(
                $amounts['taxable_subtotal']
                + $amounts['salary_exempt_amount']
                + $amounts['vat_amount'],
                2
            )
        );
        $this->assertGreaterThan(0, $amounts['taxable_subtotal']);
        $this->assertGreaterThan(0, $amounts['salary_exempt_amount']);
        $this->assertGreaterThan(0, $amounts['vat_amount']);
    }

    public function test_closure_reversal_debits_revenue_and_credits_client(): void
    {
        $reflection = new ReflectionClass(RentalContractFinanceService::class);
        $service = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('appendReversalPair');
        $method->setAccessible(true);
        $lines = [];

        $method->invokeArgs($service, [&$lines, 178, 220, 500.25, 'إشعار دائن', 15]);

        $this->assertSame([
            'account_id' => 178,
            'account_type' => 'debtor',
            'debtor_value' => 500.25,
            'creditor_value' => 0,
            'statement' => 'إشعار دائن',
            'cost_center_id' => 15,
        ], $lines[0]);
        $this->assertSame([
            'account_id' => 220,
            'account_type' => 'creditor',
            'debtor_value' => 0,
            'creditor_value' => 500.25,
            'statement' => 'إشعار دائن',
            'cost_center_id' => null,
        ], $lines[1]);
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
