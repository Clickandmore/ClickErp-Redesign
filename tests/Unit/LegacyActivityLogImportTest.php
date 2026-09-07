<?php

namespace Tests\Unit;

use App\Services\ingaz\DataImportTemplateService;
use App\Services\ingaz\LegacyActivityLogImportService;
use ReflectionMethod;
use Tests\TestCase;

class LegacyActivityLogImportTest extends TestCase
{
    public function test_activity_import_type_is_not_available_on_reports_import_page(): void
    {
        $template = app(DataImportTemplateService::class)->template('legacy_activity_logs');

        $this->assertNull($template);
        $this->assertArrayNotHasKey('activitylog:import-legacy', \Illuminate\Support\Facades\Artisan::all());
    }

    public function test_activity_report_reader_handles_rows_and_skips_blank_rows(): void
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['م', 'ملاحظة', 'تاريخ هجري', 'تاريخ ميلادي', 'الوقت', 'بواسطة المستخدم'],
            [1, 'إضافة سجل تجريبي', '', '2020-01-02', '10:34:32', 'Test Actor'],
            ['', '', '', '', '', ''],
            [2, 'تحديث سجل تجريبي', '', '2020-01-03', '11:22:33', 'Test Actor'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'activity-reader-');
        try {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);
            $rows = iterator_to_array(app(LegacyActivityLogImportService::class)->readRows($path));
            $this->assertCount(2, $rows);
            $this->assertSame('1', (string) $rows[2]['legacy_id']);
            $this->assertSame('إضافة سجل تجريبي', $rows[2]['note']);
            $this->assertSame('2020-01-02', $rows[2]['gregorian_date']);
            $this->assertSame('Test Actor', $rows[2]['user_name']);
            $this->assertSame('2', (string) $rows[4]['legacy_id']);
        } finally {
            $spreadsheet->disconnectWorksheets();
            unlink($path);
        }
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
