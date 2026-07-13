<?php

namespace Tests\Unit;

use App\Services\ingaz\AlrafedinTransferServicesImportService;
use App\Services\ingaz\DataImportTemplateService;
use PHPUnit\Framework\TestCase;

class AlrafedinTransferReportParserTest extends TestCase
{
    private function service(): AlrafedinTransferServicesImportService
    {
        return new AlrafedinTransferServicesImportService(new DataImportTemplateService());
    }

    public function test_it_reads_every_row_from_the_combined_legacy_report(): void
    {
        $rows = $this->service()->parseReport(
            dirname(__DIR__, 2) . '/migration_system/data/alrafedin/transferservice/تقرير-مدمج-نقل-الكفالة-والخدمات.xls'
        );

        $this->assertCount(155, $rows);
        $this->assertCount(66, array_filter($rows, fn ($row) => $row['source'] === 'legacy_sponsorship'));
        $this->assertCount(89, array_filter($rows, fn ($row) => $row['source'] === 'transfer_service'));

        $firstTransferService = $rows[66];
        $this->assertSame('89', $firstTransferService['data']['operation_number']);
        $this->assertSame(2362, $firstTransferService['data']['worker_number']);
        $this->assertSame('X6584346', $firstTransferService['data']['worker_passport']);
        $this->assertSame(366, $firstTransferService['data']['old_client_number']);
        $this->assertSame(2723, $firstTransferService['data']['new_client_number']);
        $this->assertSame(29000.0, $firstTransferService['data']['total_cost']);
        $this->assertSame('2026-07-05', $firstTransferService['data']['transfer_date']);
    }

    public function test_it_reads_the_standalone_report_without_duplicating_headers(): void
    {
        $rows = $this->service()->parseReport(
            dirname(__DIR__, 2) . '/migration_system/data/alrafedin/transferservice/تقرير-نقل-الخدمات.xls'
        );

        $this->assertCount(89, $rows);
        $this->assertSame(['transfer_service'], array_values(array_unique(array_column($rows, 'source'))));
    }
}
