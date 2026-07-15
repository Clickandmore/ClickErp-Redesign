<?php

namespace Tests\Unit;

use App\Services\ingaz\DataImportExecutionService;
use ReflectionClass;
use Tests\TestCase;

class AlrafedinRentalContractsImportTest extends TestCase
{
    private function invoke(string $method, mixed ...$arguments): mixed
    {
        $service = app(DataImportExecutionService::class);
        $reflection = new ReflectionClass($service);
        $target = $reflection->getMethod($method);
        $target->setAccessible(true);

        return $target->invoke($service, ...$arguments);
    }

    public function test_real_alrafedin_report_is_read_completely_from_its_active_sheet(): void
    {
        $path = base_path('migration_system/data/alrafedin/rental-contracts/تقرير طلبات التأجير __2026-07-13.xlsx');

        $this->assertFileExists($path);

        $rows = iterator_to_array($this->invoke('readLegacyRentalOrderRows', $path));

        $this->assertCount(1937, $rows);
        $this->assertSame('1937', (string) $rows[2]['legacy_order_id']);
        $this->assertSame('AK1169994', $rows[2]['maid_passport']);
        $this->assertSame('1', (string) $rows[1938]['legacy_order_id']);
        $this->assertSame('569', (string) $rows[1938]['legacy_invoice_id']);
    }

    public function test_filtered_report_is_detected_as_the_authoritative_active_snapshot(): void
    {
        $matches = glob(base_path('migration_system/data/alrafedin/rental-contracts/*2026-07-15.xlsx'));

        $this->assertCount(1, $matches);

        $rows = iterator_to_array($this->invoke('readLegacyRentalOrderRows', $matches[0]));

        $this->assertCount(75, $rows);
        $this->assertTrue($this->invoke('isLegacyRentalActiveSnapshot', $rows));

        $fullReport = base_path('migration_system/data/alrafedin/rental-contracts/تقرير طلبات التأجير __2026-07-13.xlsx');
        $fullRows = iterator_to_array($this->invoke('readLegacyRentalOrderRows', $fullReport));

        $this->assertFalse($this->invoke('isLegacyRentalActiveSnapshot', $fullRows));
    }

    public function test_legacy_day_labels_and_vat_inclusive_total_are_normalized(): void
    {
        $this->assertSame(24, $this->invoke('legacyRentalDayCount', ' +24'));
        $this->assertSame(6, $this->invoke('legacyRentalDayCount', '6 حتى اللحظة'));
        $this->assertNull($this->invoke('legacyRentalDayCount', 'لا يوجد'));

        $amounts = $this->invoke('legacyRentalInvoiceAmounts', 1900.01);

        $this->assertSame(1652.18, $amounts['subtotal']);
        $this->assertSame(247.83, $amounts['vat_amount']);
        $this->assertSame(1900.01, $amounts['total_due']);
    }

    public function test_missing_cost_still_produces_a_zero_value_financial_snapshot(): void
    {
        $this->assertSame([
            'subtotal' => 0.0,
            'vat_amount' => 0.0,
            'total_due' => 0.0,
        ], $this->invoke('legacyRentalInvoiceAmounts', 0.0));
    }
}
