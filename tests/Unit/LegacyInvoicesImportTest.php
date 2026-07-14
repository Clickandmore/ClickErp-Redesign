<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\InvoiceController;
use App\Models\ingaz\Invoice;
use App\Services\ingaz\DataImportExecutionService;
use App\Services\ingaz\DataImportTemplateService;
use ReflectionClass;
use Tests\TestCase;

class LegacyInvoicesImportTest extends TestCase
{
    private function invoke(object $target, string $method, mixed ...$arguments): mixed
    {
        $reflection = new ReflectionClass($target);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invoke($target, ...$arguments);
    }

    public function test_real_current_report_reads_every_invoice_and_all_source_fields(): void
    {
        $path = base_path('migration_system/data/alrafedin/finance/invoices/تقرير الفواتير __2026-07-14.xlsx');
        $service = app(DataImportExecutionService::class);
        $columns = app(DataImportTemplateService::class)->template('legacy_invoices')['columns'];

        $rows = iterator_to_array($this->invoke($service, 'readLegacyInvoiceRows', $path, $columns));

        $this->assertCount(8227, $rows);
        $first = reset($rows);
        $this->assertSame('1', (string) $first['invoice_number']);
        $this->assertSame('فاتورة ايراد / طلب استقدام', $first['invoice_type']);
        $this->assertSame('فاتورة', $first['document_type']);
        $this->assertSame('لا', $first['legacy_zatca_shared']);
        $this->assertSame('Arabia2web.com', $first['source_sheet']);
        $this->assertSame(2, $first['source_row']);
    }

    public function test_source_payload_preserves_fields_without_changing_values(): void
    {
        $service = app(DataImportExecutionService::class);
        $row = [
            'invoice_number' => 6008,
            'legacy_order_number' => 'ألغيت من الطلب',
            'legacy_zatca_shared' => 'نعم',
            'source_sheet' => 'فواتير 6001-6500',
            'source_row' => 2,
        ];

        $this->assertSame($row, $this->invoke($service, 'legacyInvoiceSourcePayload', $row));
        $this->assertTrue($this->invoke($service, 'truthy', 'نعم', false));
        $this->assertFalse($this->invoke($service, 'truthy', 'لا', true));
    }

    public function test_rental_invoice_number_is_not_mistaken_for_a_recruitment_contract(): void
    {
        $service = app(DataImportExecutionService::class);
        $context = [
            'has_contract_legacy_numbers' => true,
            'contract_ids_by_legacy_number' => [1359 => 91],
            'contract_ids' => [1359 => true],
        ];

        $this->assertNull($this->invoke($service, 'resolveLegacyInvoiceContract', [
            'invoice_type' => 'تأجير',
            'legacy_order_number' => 1359,
        ], $context));
        $this->assertSame(91, $this->invoke($service, 'resolveLegacyInvoiceContract', [
            'invoice_type' => 'فاتورة ايراد / طلب استقدام',
            'legacy_order_number' => 1359,
        ], $context));
    }

    public function test_legacy_invoice_is_paid_by_management_decision_and_is_not_requeued(): void
    {
        $controller = app(InvoiceController::class);
        $invoice = new Invoice([
            'id' => 6008,
            'legacy_invoice_number' => 6008,
            'legacy_zatca_shared' => true,
            'is_paid' => true,
            'total_due' => 1900.01,
        ]);

        $this->assertSame(
            'مدفوعة',
            $this->invoke($controller, 'invoicePaymentStatusLabel', $invoice)
        );
        $this->assertSame(
            'مشارك سابقاً مع الهيئة',
            $this->invoke($controller, 'exportZatcaStatusLabel', $invoice)
        );
    }

    public function test_invoice_number_is_clear_and_original_pdf_is_used_for_printing(): void
    {
        $controller = app(InvoiceController::class);
        $invoice = new Invoice([
            'id' => 8231,
            'legacy_invoice_number' => 8231,
            'legacy_order_number' => 2372,
            'legacy_source_file' => 'original-8231.pdf',
        ]);

        $number = $this->invoke($controller, 'invoiceNumberLink', $invoice);
        $source = $this->invoke($controller, 'invoiceSourceBadge', $invoice);

        $this->assertStringContainsString('>8231</a>', $number);
        $this->assertStringNotContainsString('#8231', $number);
        $this->assertStringNotContainsString('رافدين', $number);
        $this->assertStringContainsString('invoices/download/8231', $number);
        $this->assertStringContainsString('autoprint=1', $number);
        $this->assertStringContainsString('طلب رقم 2372', $source);
    }
}
