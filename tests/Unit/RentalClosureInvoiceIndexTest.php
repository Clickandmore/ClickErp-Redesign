<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\InvoiceController;
use App\Support\RentalContractPricing;
use ReflectionClass;
use Tests\TestCase;

class RentalClosureInvoiceIndexTest extends TestCase
{
    public function test_credit_note_is_presented_as_client_credit_with_document_links(): void
    {
        $row = (object) [
            'row_type' => 'rental_adjustment',
            'id' => 12,
            'adjustment_type' => 'expense',
            'adjustment_constraint_id' => 103672,
            'rental_contract_id' => 5819,
            'rental_contract_number' => '1931',
        ];

        $controller = app(InvoiceController::class);

        $this->assertStringContainsString('إشعار دائن #12', $this->invoke($controller, 'standardInvoiceNumberLink', $row));
        $this->assertStringContainsString('رصيد لصالح العميل', $this->invoke($controller, 'standardInvoicePaymentBadge', $row));
        $this->assertStringContainsString('عقد تأجير #1931', $this->invoke($controller, 'standardInvoiceSourceBadge', $row));

        $actions = $this->invoke($controller, 'standardInvoiceActions', $row);
        $this->assertStringContainsString('RentContract/5819', $actions);
        $this->assertStringContainsString('add-constraint/103672', $actions);
        $this->assertStringContainsString('rental-credit-notes/download/12', $actions);
    }

    public function test_debit_note_is_presented_as_amount_due_from_client(): void
    {
        $row = (object) [
            'row_type' => 'rental_adjustment',
            'id' => 13,
            'adjustment_type' => 'income',
            'adjustment_constraint_id' => 103673,
            'rental_contract_id' => 5843,
            'rental_contract_number' => '1907',
        ];

        $controller = app(InvoiceController::class);

        $this->assertStringContainsString('إشعار مدين #13', $this->invoke($controller, 'standardInvoiceNumberLink', $row));
        $this->assertStringContainsString('مستحق على العميل', $this->invoke($controller, 'standardInvoicePaymentBadge', $row));
    }

    public function test_rental_invoice_can_be_queued_for_zatca_from_the_unified_invoice_screen(): void
    {
        $row = (object) [
            'row_type' => 'rental',
            'id' => 29,
            'rental_contract_id' => 91,
            'finance_sale_id' => null,
            'zatca_status' => null,
        ];

        $controller = app(InvoiceController::class);

        $this->assertStringContainsString('جاهزة للمشاركة', $this->invoke($controller, 'standardInvoiceZatcaBadge', $row));
        $actions = $this->invoke($controller, 'standardInvoiceActions', $row);
        $this->assertStringContainsString('rental-invoices/29/queue-zatca-sale', $actions);
        $this->assertStringContainsString('إدراج فاتورة التأجير في فواتير الزكاة', $actions);
    }

    public function test_queued_rental_invoice_links_to_its_financial_sale(): void
    {
        $row = (object) [
            'row_type' => 'rental',
            'id' => 29,
            'rental_contract_id' => 91,
            'finance_sale_id' => 55,
            'zatca_status' => null,
        ];

        $controller = app(InvoiceController::class);

        $this->assertStringContainsString('معلق في المبيعات', $this->invoke($controller, 'standardInvoiceZatcaBadge', $row));
        $this->assertStringContainsString('finance/bill-sales/55', $this->invoke($controller, 'standardInvoiceActions', $row));
    }

    public function test_rental_zatca_amount_keeps_worker_salary_as_non_taxable_line(): void
    {
        $amounts = $this->invoke(
            app(InvoiceController::class),
            'rentalZatcaAmounts',
            RentalContractPricing::fromGrossPrice(1840, 1000, 30, 1)
        );

        $this->assertSame(1000.0, $amounts['salary_exempt_amount']);
        $this->assertSame(730.43, $amounts['taxable_subtotal']);
        $this->assertSame(109.57, $amounts['vat_amount']);
        $this->assertSame(1730.43, $amounts['invoice_subtotal']);
        $this->assertSame(840.0, $amounts['service_total']);
        $this->assertSame(1840.0, $amounts['total']);
    }

    public function test_rental_zatca_amount_recalculates_tax_when_legacy_invoice_stored_zero_vat(): void
    {
        $amounts = $this->invoke(
            app(InvoiceController::class),
            'rentalZatcaAmounts',
            RentalContractPricing::fromGrossPrice(100, 1100, 1, 1)
        );

        $this->assertSame(36.67, $amounts['salary_exempt_amount']);
        $this->assertSame(55.07, $amounts['taxable_subtotal']);
        $this->assertSame(8.26, $amounts['vat_amount']);
        $this->assertSame(91.74, $amounts['invoice_subtotal']);
        $this->assertSame(63.33, $amounts['service_total']);
        $this->assertSame(100.0, $amounts['total']);
    }

    private function invoke(object $target, string $method, mixed ...$arguments): mixed
    {
        $reflection = new ReflectionClass($target);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invoke($target, ...$arguments);
    }
}
