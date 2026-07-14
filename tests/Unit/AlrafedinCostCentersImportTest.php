<?php

namespace Tests\Unit;

use App\Services\ingaz\DataImportExecutionService;
use App\Services\ingaz\DataImportTemplateService;
use ReflectionMethod;
use Tests\TestCase;

class AlrafedinCostCentersImportTest extends TestCase
{
    private function invoke(string $method, mixed ...$arguments): mixed
    {
        $service = app(DataImportExecutionService::class);
        $reflection = new ReflectionMethod($service, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($service, ...$arguments);
    }

    public function test_real_cost_center_export_maps_all_source_columns_and_rows(): void
    {
        $path = base_path('migration_system/data/alrafedin/finance/costcenter/cost_centers_full.csv');
        $columns = app(DataImportTemplateService::class)->template('finance_cost_centers')['columns'];
        $rows = iterator_to_array($this->invoke('readRows', $path, $columns));

        $this->assertCount(2213, $rows);
        $this->assertSame('1', (string) $rows[2]['cost_center_code']);
        $this->assertSame('الموظفين', $rows[2]['cost_center_name']);
        $this->assertSame('2', (string) $rows[3]['cost_center_code']);
        $this->assertSame('3,155,772.74', $rows[3]['legacy_balance']);

        $requestCenter = collect($rows)->firstWhere('cost_center_code', '15');
        $this->assertSame('طلب استقدام 445', $requestCenter['cost_center_name']);
        $this->assertSame('3', (string) $requestCenter['parent_code']);
        $this->assertSame('6550', (string) $requestCenter['legacy_balance']);
    }

    public function test_request_number_is_only_inferred_from_an_explicit_request_phrase(): void
    {
        $this->assertSame(445, $this->invoke('extractLegacyRequestNumber', 'فاتورة تخص طلب استقدام رقم: 445'));
        $this->assertSame(22, $this->invoke('extractLegacyRequestNumber', 'إغلاق طلب: 22، فاتورة رقم 2'));
        $this->assertNull($this->invoke('extractLegacyRequestNumber', 'فاتورة العميل رقم 445'));
        $this->assertNull($this->invoke('extractLegacyRequestNumber', 'طلب تأجير, رقم الطلب: 496'));
        $this->assertSame(496, $this->invoke('extractLegacyRentalRequestNumber', 'طلب تأجير, رقم الطلب: 496'));
        $this->assertSame(1591, $this->invoke('extractLegacyRentalRequestNumber', 'إنهاء طلب تأجير رقم: 1591'));
    }
}
