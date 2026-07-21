<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\InvoiceController;
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

    private function invoke(object $target, string $method, mixed ...$arguments): mixed
    {
        $reflection = new ReflectionClass($target);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invoke($target, ...$arguments);
    }
}
