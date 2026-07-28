<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\RentContractController;
use App\Models\ingaz\Package;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class RentContractPackagePriceSnapshotTest extends TestCase
{
    public function test_rental_contract_forms_display_the_catalog_total_as_tax_inclusive(): void
    {
        foreach (['create.blade.php', 'edit.blade.php'] as $view) {
            $contents = file_get_contents(
                dirname(__DIR__, 2) . '/resources/views/admin/ingaz/RentContract/' . $view
            );

            $this->assertStringContainsString(
                'number_format((float) $package->price_with_tax, 2) }} ريال شامل الضريبة',
                $contents
            );
            $this->assertStringNotContainsString(
                'number_format((float) $package->price_after_discount, 2) }} ريال بدون الضريبة',
                $contents
            );
        }
    }

    public function test_selected_package_keeps_its_catalog_total_when_salary_is_tax_exempt(): void
    {
        $package = new class([
            'original_price' => 86.96,
            'price_after_discount' => 86.96,
            'price_with_tax' => 100,
        ]) extends Package {
            public function __construct(private array $testAttributes)
            {
            }

            public function getAttribute($key)
            {
                return $this->testAttributes[$key] ?? null;
            }
        };

        $reflection = new ReflectionClass(RentContractController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('buildPackagePriceSnapshot');
        $method->setAccessible(true);

        $snapshot = $method->invoke($controller, $package, 95, 1000, 1, 1);

        $this->assertSame(86.96, $snapshot['package_original_price']);
        $this->assertSame(86.96, $snapshot['package_price_after_discount']);
        $this->assertSame(100.0, $snapshot['package_total_with_tax']);
        $this->assertSame(100.0, $snapshot['contract_total_due']);
        $this->assertSame('gross', $snapshot['package_price_basis']);
    }
}
