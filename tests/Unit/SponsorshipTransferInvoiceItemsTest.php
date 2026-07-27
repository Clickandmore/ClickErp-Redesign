<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\InvoiceController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

class SponsorshipTransferInvoiceItemsTest extends TestCase
{
    public function test_core_tax_rules_are_enforced_and_additional_tax_is_selectable(): void
    {
        $items = $this->normalize([
            [
                'key' => 'licensee_cost',
                'label' => 'تكاليف المرخص له',
                'quantity' => 1,
                'unit_price' => 1000,
                'taxable' => false,
            ],
            [
                'key' => 'government_cost',
                'label' => 'رسوم حكومية',
                'quantity' => 1,
                'unit_price' => 200,
                'taxable' => true,
            ],
            [
                'key' => 'additional',
                'label' => 'خدمة إضافية',
                'quantity' => 2,
                'unit_price' => 100,
                'taxable' => true,
            ],
        ]);

        $this->assertTrue($items[0]['taxable']);
        $this->assertSame(150.0, $items[0]['vat_amount']);
        $this->assertFalse($items[1]['taxable']);
        $this->assertSame(0.0, $items[1]['vat_amount']);
        $this->assertTrue($items[2]['taxable']);
        $this->assertSame(30.0, $items[2]['vat_amount']);
    }

    public function test_licensee_cost_item_is_required(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('بند تكاليف المرخص له مطلوب');

        $this->normalize([
            [
                'key' => 'additional',
                'label' => 'خدمة إضافية',
                'quantity' => 1,
                'unit_price' => 100,
                'taxable' => false,
            ],
        ]);
    }

    private function normalize(array $items): array
    {
        $method = new ReflectionMethod(InvoiceController::class, 'normalizeSponsorshipTransferItems');
        $method->setAccessible(true);

        return $method->invoke(new InvoiceController(), $items);
    }
}
