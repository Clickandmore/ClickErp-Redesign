<?php

namespace Tests\Unit;

use App\Services\ingaz\DataImportTemplateService;
use App\Services\ingaz\LegacyActivityLogImportService;
use ReflectionMethod;
use Tests\TestCase;

class LegacyActivityLogImportTest extends TestCase
{
    public function test_activity_import_type_is_available_on_reports_import_page(): void
    {
        $template = app(DataImportTemplateService::class)->template('legacy_activity_logs');

        $this->assertNotNull($template);
        $this->assertSame('سجل النشاط القديم (الرافدين)', $template['label']);
        $this->assertFalse($template['downloadable']);
    }

    public function test_real_alrafedin_activity_report_is_read_completely(): void
    {
        $path = base_path('migration_system/data/alrafedin/logs/سجل العمل اليومي __2026-07-13.xlsx');

        $this->assertFileExists($path);

        $rows = iterator_to_array(app(LegacyActivityLogImportService::class)->readRows($path));

        $this->assertCount(44494, $rows);
        $this->assertSame('1', (string) $rows[2]['legacy_id']);
        $this->assertSame('أضاف عميل جديد: سكينة  حسن العبادي / 1', $rows[2]['note']);
        $this->assertSame('2017-12-26', $rows[2]['gregorian_date']);
        $this->assertSame('System Admin', $rows[2]['user_name']);
        $this->assertSame('44494', (string) $rows[44495]['legacy_id']);
    }

    public function test_source_date_and_time_are_combined_without_changing_timezone(): void
    {
        $service = app(LegacyActivityLogImportService::class);
        $method = new ReflectionMethod($service, 'sourceDateTime');
        $method->setAccessible(true);

        $dateTime = $method->invoke($service, '2017-12-26', '10:34:32');

        $this->assertSame('2017-12-26 10:34:32', $dateTime->format('Y-m-d H:i:s'));
    }
}
