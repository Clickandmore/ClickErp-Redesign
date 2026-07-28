<?php

namespace Tests\Unit;

use Illuminate\Support\Collection;
use Nami\Finance\Services\TaxInvoiceStatementExportService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class TaxInvoiceStatementExportServiceTest extends TestCase
{
    public function test_excel_export_is_rtl_formatted_and_contains_totals(): void
    {
        $response = app(TaxInvoiceStatementExportService::class)->download(
            'xlsx',
            $this->rows(),
            $this->filters(),
            'شركة الاختبار',
            '310000000000003'
        );

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $path = $response->getFile()->getPathname();
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertTrue($sheet->getRightToLeft());
        $this->assertSame('كشف حساب ضريبة الفواتير', $sheet->getCell('A1')->getValue());
        $this->assertSame('رقم الفاتورة', $sheet->getCell('B5')->getValue());
        $this->assertSame('INV-001', $sheet->getCell('B6')->getValue());
        $this->assertSame('0500000000', $sheet->getCell('L6')->getValue());
        $this->assertSame(115.0, (float) $sheet->getCell('H7')->getValue());
        $this->assertSame(100.0, (float) $sheet->getCell('I7')->getValue());
        $this->assertSame(15.0, (float) $sheet->getCell('J7')->getValue());

        $spreadsheet->disconnectWorksheets();
        unlink($path);
    }

    public function test_pdf_export_returns_a_downloadable_pdf(): void
    {
        $response = app(TaxInvoiceStatementExportService::class)->download(
            'pdf',
            $this->rows(),
            $this->filters(),
            'شركة الاختبار',
            '310000000000003'
        );

        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString(
            'tax-invoice-statement-',
            (string) $response->headers->get('content-disposition')
        );
    }

    private function rows(): Collection
    {
        return collect([[
            'invoice_number' => 'INV-001',
            'date' => '2026-07-28 10:30:00',
            'source' => 'استقدام',
            'type' => 'فاتورة إيراد / طلب استقدام',
            'seller' => 'شركة الاختبار',
            'seller_tax_number' => '310000000000003',
            'total_with_tax' => 115.0,
            'total_without_tax' => 100.0,
            'vat_amount' => 15.0,
            'client_name' => 'عميل تجريبي',
            'client_phone' => '0500000000',
            'reference_number' => '123',
            'document_type' => 'فاتورة',
            'zatca_status' => 'مرحل للهيئة',
        ]]);
    }

    private function filters(): array
    {
        return [
            'from_date' => '2026-07-01',
            'to_date' => '2026-07-31',
            'source' => 'all',
        ];
    }
}
