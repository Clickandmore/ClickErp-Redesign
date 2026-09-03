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

    /**
     * الإجمالي المُدخل هو السعر المعلن للعميل ولا يجوز إزاحته بالهللة عند الحفظ:
     * 3000 ÷ 1.15 = 2608.6957 ← 2608.70، وإعادة ضربها × 1.15 كانت تعطي 3000.01.
     *
     * @dataProvider roundedNetPricesProvider
     */
    public function test_entered_total_survives_a_net_price_that_rounds_up(
        float $enteredTotal,
        float $roundedNet
    ): void {
        $data = $this->prepare([
            'original_price' => $roundedNet,
            'price_with_tax' => $enteredTotal,
            'is_taxable' => '1',
        ]);

        $this->assertSame($enteredTotal, $data['price_with_tax']);
        $this->assertSame($roundedNet, $data['original_price']);
        $this->assertSame($roundedNet, $data['price_after_discount']);
    }

    public function roundedNetPricesProvider(): array
    {
        return [
            'باقة 30 يوم' => [3000.0, 2608.70],
            'باقة 14 يوم' => [1000.0, 869.57],
            'باقة 3 سنوات' => [16000.0, 13913.04],
        ];
    }

    public function test_total_is_derived_from_base_price_when_it_is_not_provided(): void
    {
        $data = $this->prepare([
            'original_price' => 2000,
            'is_taxable' => '1',
        ]);

        $this->assertSame(2000.0, $data['original_price']);
        $this->assertSame(2300.0, $data['price_with_tax']);
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
