<?php

namespace Tests\Unit;

use App\Services\ingaz\DataImportExecutionService;
use App\Services\ingaz\DataImportTemplateService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ReflectionMethod;
use Tests\TestCase;

class ComplaintImportHeaderMappingTest extends TestCase
{
    public function test_legacy_complaint_headers_keep_every_source_field_separate(): void
    {
        $headers = [
            'م', 'رقم تعريف', 'نوع الشكوى', 'نوع الشاكي', 'اسم الشاكي', 'هاتف الشاكي',
            'نوع المشتكى عليه', 'اسم المشتكى عليه', 'هاتف المشتكى عليه', 'محتوى الشكوى',
            'آخر رد', 'تاريخ الشكوى', 'أنشئ بواسطة', 'تاريخ الانشاء', 'حالة', 'متعلق',
            'تاريخ الاغلاق', 'أغلق بواسطة', 'طريقة التقديم', 'الدولة', 'أنشئ في',
        ];
        $values = [
            '1', 'CW-1', 'شكوى عميل ضد عاملة', 'عميل', 'اسم العميل', '0500000000',
            'عاملـ/ـة', 'اسم العاملة', '0511111111', 'محتوى الشكوى', 'آخر رد', '2026-07-01',
            'منشئ الشكوى', '2026-07-02', 'مغلقة', 'طلب استقدام 1', '2026-07-03',
            'مغلق الشكوى', 'زيارة مقر', 'كينيا', '2026-07-04',
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray($values, null, 'A2');
        $path = tempnam(sys_get_temp_dir(), 'complaints-import-') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        try {
            $service = app(DataImportExecutionService::class);
            $method = new ReflectionMethod($service, 'readRows');
            $method->setAccessible(true);
            $columns = app(DataImportTemplateService::class)->template('complaints')['columns'];
            $rows = iterator_to_array($method->invoke($service, $path, $columns));

            $this->assertCount(1, $rows);
            $row = reset($rows);
            $this->assertSame('1', $row['source_sequence']);
            $this->assertSame('عميل', $row['complainant_type']);
            $this->assertSame('عاملـ/ـة', $row['against_type']);
            $this->assertSame('0511111111', $row['against_phone']);
            $this->assertSame('2026-07-01', $row['complaint_date']);
            $this->assertSame('منشئ الشكوى', $row['source_created_by']);
            $this->assertSame('2026-07-02', $row['source_created_at']);
            $this->assertSame('2026-07-03', $row['closed_at']);
            $this->assertSame('مغلق الشكوى', $row['closed_by_name']);
            $this->assertSame('2026-07-04', $row['created_at']);
        } finally {
            @unlink($path);
        }
    }
}
