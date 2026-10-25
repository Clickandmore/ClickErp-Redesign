<?php

namespace Tests\Unit;

use App\Models\ingaz\Package;
use App\Models\RentalContract;
use App\Models\RentalContractInfo;
use App\Models\RentalContractInvoice;
use App\Services\ingaz\RentalContractFinanceService;
use App\Support\RentalContractPricing;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class RentalContractFinanceServiceTest extends TestCase
{
    public function test_new_package_prices_only_the_extension_and_preserves_the_original_contract(): void
    {
        $service = (new ReflectionClass(RentalContractFinanceService::class))->newInstanceWithoutConstructor();
        $contract = $this->extensionContract();
        $original = $contract->info->getAttributes();
        $package = $this->package(['days_count' => 90, 'price_with_tax' => 6900]);
        $full = $service->extensionAmounts($contract, 90, 0, $package);
        $partial = $service->extensionAmounts($contract, 30, 100, $package);
        $this->assertSame(6900.0, $full['total_due']);
        $this->assertSame(2200.0, $partial['total_due']);
        $this->assertSame(2300.0, $partial['gross_total_before_discount']);
        $this->assertSame(100.0, $partial['discount']);
        $this->assertSame($partial['total_due'], round($partial['salary_exempt_amount'] + $partial['taxable_subtotal'] + $partial['vat_amount'], 2));
        $this->assertSame($original, $contract->info->getAttributes());

        $extension = new \App\Models\RentalContractExtension(['pricing_snapshot' => $partial]);
        $contract->info->contract_value = 99999;
        $this->assertEquals($partial, $service->extensionAmountsFor($extension));
    }

    public function test_new_package_can_receive_a_full_discount(): void
    {
        $service = (new ReflectionClass(RentalContractFinanceService::class))->newInstanceWithoutConstructor();
        $amounts = $service->extensionAmounts($this->extensionContract(), 30, 2300,
            $this->package(['days_count' => 90, 'price_with_tax' => 6900]));
        $this->assertSame(0.0, $amounts['total_due']);
        $this->assertSame(0.0, $amounts['vat_amount']);
    }

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

    public function test_extension_uses_the_agreed_gross_contract_daily_rate(): void
    {
        $contract = new RentalContract();
        $info = new RentalContractInfo([
            'monthly_salary' => 900,
            'contract_duration_months' => 1,
            'contract_duration_days' => 30,
            'package_price_basis' => 'net',
            'package_price_after_discount' => 1478.26,
            'package_total_with_tax' => 1700,
            'contract_value' => 1700,
        ]);
        $info->setRelation('package', null);
        $contract->setRelation('info', $info);
        $contract->setRelation('maid', null);

        $reflection = new ReflectionClass(RentalContractFinanceService::class);
        $service = $reflection->newInstanceWithoutConstructor();
        $amounts = $service->extensionAmounts($contract, 32);

        $this->assertSame(1813.33, $amounts['total_due']);
        $this->assertSame(960.0, $amounts['salary_exempt_amount']);
        $this->assertSame(
            $amounts['total_due'],
            round($amounts['taxable_subtotal'] + $amounts['salary_exempt_amount'] + $amounts['vat_amount'], 2)
        );
    }

    public function test_a_percentage_discount_reduces_the_extension_total_and_its_vat(): void
    {
        $service = $this->financeService();
        $contract = $this->extensionContract();

        $amounts = $service->extensionAmounts($contract, 30, RentalContractPricing::discountAmount(
            'percentage',
            10,
            (float) $service->extensionAmounts($contract, 30)['total_due']
        ));

        // إجمالي التمديد 1700 × 10% = 170 خصماً، والخصم يخرج من الخدمة الخاضعة للضريبة
        $this->assertSame(170.0, $amounts['discount']);
        $this->assertSame(1700.0, $amounts['gross_total_before_discount']);
        $this->assertSame(1530.0, $amounts['total_due']);
        $this->assertSame(900.0, $amounts['salary_exempt_amount']);
        $this->assertSame(
            $amounts['total_due'],
            round($amounts['taxable_subtotal'] + $amounts['salary_exempt_amount'] + $amounts['vat_amount'], 2)
        );
        // الضريبة تنخفض مع الخصم: 104.35 قبل الخصم مقابل 82.17 بعده
        $this->assertSame(82.17, $amounts['vat_amount']);
        $this->assertLessThan(
            $service->extensionAmounts($contract, 30)['vat_amount'],
            $amounts['vat_amount']
        );
    }

    public function test_a_fixed_discount_is_capped_at_the_extension_total(): void
    {
        $service = $this->financeService();
        $contract = $this->extensionContract();

        $discount = RentalContractPricing::discountAmount('amount', 5000, 1700.0);
        $amounts = $service->extensionAmounts($contract, 30, $discount);

        $this->assertSame(1700.0, $discount);
        $this->assertSame(0.0, $amounts['total_due']);
        $this->assertSame(0.0, $amounts['vat_amount']);
    }

    public function test_extension_amounts_are_unchanged_without_a_discount(): void
    {
        $service = $this->financeService();
        $amounts = $service->extensionAmounts($this->extensionContract(), 30);

        $this->assertSame(0.0, $amounts['discount']);
        $this->assertSame(1700.0, $amounts['total_due']);
        $this->assertSame(1700.0, $amounts['gross_total_before_discount']);
    }

    public function test_discount_amount_helper_handles_types_and_bounds(): void
    {
        $this->assertSame(100.0, RentalContractPricing::discountAmount('amount', 100, 1000));
        $this->assertSame(250.0, RentalContractPricing::discountAmount('percentage', 25, 1000));
        $this->assertSame(1000.0, RentalContractPricing::discountAmount('percentage', 150, 1000));
        $this->assertSame(0.0, RentalContractPricing::discountAmount(null, 100, 1000));
        $this->assertSame(0.0, RentalContractPricing::discountAmount('amount', 0, 1000));
        $this->assertSame(0.0, RentalContractPricing::discountAmount('amount', 100, 0));
    }

    private function financeService(): RentalContractFinanceService
    {
        return (new ReflectionClass(RentalContractFinanceService::class))->newInstanceWithoutConstructor();
    }

    public function test_next_extension_is_priced_from_the_latest_extension_value(): void
    {
        $service = (new ReflectionClass(RentalContractFinanceService::class))->newInstanceWithoutConstructor();
        $contract = $this->extensionContract();
        $contract->setRelation('extensions', new \Illuminate\Database\Eloquent\Collection([
            (new \App\Models\RentalContractExtension())->forceFill([
                'id' => 1, 'extension_days' => 30, 'gross_value' => 1800, 'extension_value' => 1800,
            ]),
            (new \App\Models\RentalContractExtension())->forceFill([
                'id' => 2, 'extension_days' => 30, 'gross_value' => 1900, 'extension_value' => 1700, 'discount_amount' => 200,
            ]),
        ]));

        $basis = $service->currentPricingBasis($contract);

        $this->assertSame('extension', $basis['source']);
        $this->assertSame(1900.0, $basis['gross_total']);
        $this->assertSame(30, $basis['days']);
        $this->assertSame(1900.0, (float) $service->extensionAmounts($contract, 30, 0, null, $basis)['total_due']);
        // بدون أساس التسعير يبقى السلوك القديم (قيمة العقد الأصلية) لإعادة حساب التمديدات القديمة كما هي
        $this->assertSame(1700.0, (float) $service->extensionAmounts($contract, 30)['total_due']);
    }

    public function test_pricing_basis_falls_back_to_the_original_contract_without_extensions(): void
    {
        $service = (new ReflectionClass(RentalContractFinanceService::class))->newInstanceWithoutConstructor();
        $contract = $this->extensionContract();
        $contract->setRelation('extensions', new \Illuminate\Database\Eloquent\Collection());

        $basis = $service->currentPricingBasis($contract);

        $this->assertSame('contract', $basis['source']);
        $this->assertSame(1700.0, $basis['gross_total']);
        $this->assertSame(1700.0, (float) $service->extensionAmounts($contract, 30, 0, null, $basis)['total_due']);
    }

    private function extensionContract(): RentalContract
    {
        $contract = new RentalContract();
        $info = new RentalContractInfo([
            'monthly_salary' => 900,
            'contract_duration_months' => 1,
            'contract_duration_days' => 30,
            'package_price_basis' => 'net',
            'contract_value' => 1700,
        ]);
        $info->setRelation('package', null);
        $contract->setRelation('info', $info);
        $contract->setRelation('maid', null);

        return $contract;
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

    public function test_invoice_revenue_accounts_are_not_swapped(): void
    {
        $reflection = new ReflectionClass(RentalContractFinanceService::class);
        $service = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('invoiceRevenueAllocations');
        $method->setAccessible(true);

        $allocations = $method->invoke($service, 320.00, 1000.00, 4214, 4216);

        $this->assertSame([
            ['kind' => 'service', 'account_id' => 4214, 'amount' => 320.00],
            ['kind' => 'salary', 'account_id' => 4216, 'amount' => 1000.00],
        ], $allocations);
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
