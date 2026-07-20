<?php

namespace Tests\Unit;

use App\Http\Actions\ingaz\PackageAction;
use App\Models\ingaz\Package;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class PackageTaxCalculationTest extends TestCase
{
    public function test_taxable_package_keeps_the_current_fifteen_percent_calculation(): void
    {
        $data = $this->prepare([
            'original_price' => 100,
            'price_with_tax' => 115,
            'is_taxable' => '1',
        ]);

        $this->assertTrue($data['is_taxable']);
        $this->assertSame(100.0, $data['original_price']);
        $this->assertSame(115.0, $data['price_with_tax']);
    }

    public function test_non_taxable_package_forces_total_to_equal_base_price(): void
    {
        $data = $this->prepare([
            'original_price' => 100,
            'price_with_tax' => 999,
            'is_taxable' => '0',
        ]);

        $this->assertFalse($data['is_taxable']);
        $this->assertSame(100.0, $data['original_price']);
        $this->assertSame(100.0, $data['price_after_discount']);
        $this->assertSame(100.0, $data['price_with_tax']);
    }

    private function prepare(array $prices): array
    {
        $action = new PackageAction(new Package());
        $reflection = new ReflectionClass($action);
        $method = $reflection->getMethod('prepareData');
        $method->setAccessible(true);

        return $method->invoke($action, array_merge([
            'duration_unit' => 'month',
            'duration_count' => 1,
            'sort_order' => 0,
            'is_show' => '1',
        ], $prices));
    }
}
