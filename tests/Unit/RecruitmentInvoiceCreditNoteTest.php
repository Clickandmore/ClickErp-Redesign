<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\InvoiceController;
use App\Models\RecruitmentInvoiceCreditNote;
use App\Models\ingaz\Invoice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Nami\Finance\Models\ConstraintDetails;
use ReflectionMethod;
use Tests\TestCase;

class RecruitmentInvoiceCreditNoteTest extends TestCase
{
    public function test_confirmed_imported_direct_invoice_uses_the_native_item_label(): void
    {
        $invoice = new Invoice([
            'legacy_invoice_number' => 900,
            'legacy_source_payload' => ['source_row' => 901],
            'direct_recruitment_count' => 1,
            'direct_recruitment_cost' => 2000,
            'subtotal' => 2000,
            'vat_amount' => 300,
            'total_due' => 2300,
        ]);
        $items = $this->invoke(app(InvoiceController::class), 'buildInvoiceItems', $invoice);
        $this->assertSame('تكاليف الاستقدام المباشر', $items[0]['label']);
        $this->assertSame(2300.0, $items[0]['line_grand_total']);
        $this->assertSame(300.0, $items[0]['vat_amount']);
        $this->assertNotContains('legacy_taxable', array_column($items, 'key'));
    }

    public function test_imported_8150_keeps_report_totals_and_supports_both_credit_note_tax_bases(): void
    {
        $invoice = new Invoice([
            'legacy_invoice_number' => 8150,
            'legacy_source_payload' => ['source_row' => 8149],
            'direct_recruitment_count' => 1,
            'direct_recruitment_cost' => 11378,
            'subtotal' => 11378,
            'vat_amount' => 622.80,
            'total_due' => 12000.80,
        ]);
        $controller = app(InvoiceController::class);
        $items = $this->invoke($controller, 'buildInvoiceItems', $invoice);
        $this->assertCount(2, $items);
        $this->assertSame(4152.0, $items[0]['line_total']);
        $this->assertSame(7226.0, $items[1]['line_total']);
        $this->assertEqualsWithDelta(12000.80, array_sum(array_column($items, 'line_grand_total')), 0.001);
        $creditItems = $this->invoke($controller, 'buildRecruitmentCreditNoteItems', $invoice, 4152.0, 7226.0);
        $this->assertCount(2, $creditItems);
        $this->assertSame(622.8, $creditItems[0]['vat_amount']);
        $this->assertSame(0.0, $creditItems[1]['vat_amount']);
        $this->assertEqualsWithDelta(12000.80, array_sum(array_column($creditItems, 'line_total')), 0.001);
    }

    public function test_remaining_amounts_are_split_between_taxable_non_taxable_and_vat(): void
    {
        $invoice = new Invoice([
            'total_due' => 8890.45,
            'vat_amount' => 240.45,
        ]);
        $invoice->setRelation('recruitmentCreditNotes', new Collection([
            new RecruitmentInvoiceCreditNote([
                'taxable_amount' => 603,
                'non_taxable_amount' => 47,
                'subtotal' => 650,
                'vat_amount' => 90.45,
                'total' => 740.45,
            ]),
        ]));

        $remaining = $this->invoke(
            app(InvoiceController::class),
            'recruitmentCreditNoteRemaining',
            $invoice
        );

        $this->assertSame(1000.0, $remaining['taxable_amount']);
        $this->assertSame(7000.0, $remaining['non_taxable_amount']);
        $this->assertSame(150.0, $remaining['vat_amount']);
        $this->assertSame(8000.0, $remaining['subtotal']);
        $this->assertSame(8150.0, $remaining['total']);
    }

    public function test_recruitment_invoice_action_exposes_credit_note_without_contract_refund_action(): void
    {
        $admin = Mockery::mock();
        $admin->shouldReceive('can')->andReturnTrue();
        $guard = Mockery::mock();
        $guard->shouldReceive('user')->andReturn($admin);
        Auth::shouldReceive('guard')->with('admin')->andReturn($guard);

        $row = (object) [
            'row_type' => 'invoice',
            'id' => 44,
            'contract_id' => 900,
            'legacy_invoice_number' => null,
            'legacy_zatca_shared' => null,
            'finance_sale_id' => 12,
        ];

        $html = $this->invoke(app(InvoiceController::class), 'standardInvoiceActions', $row);

        $this->assertStringContainsString('/admin/invoices/44/credit-notes/create', $html);
        $this->assertStringContainsString('إنشاء إشعار دائن دون تغيير العقد أو التفويض', $html);
        $this->assertStringNotContainsString('RecruitmentContract/refund', $html);
    }

    public function test_credit_note_preserves_the_original_recruitment_invoice_lines(): void
    {
        $invoice = new Invoice([
            'direct_recruitment_cost' => 5,
            'direct_recruitment_count' => 1,
            'external_recruitment_cost' => 3745,
            'external_recruitment_count' => 1,
            'government_cost' => 0,
            'government_cost_count' => 1,
            'internal_ticket_cost' => 0,
            'internal_ticket_count' => 1,
            'vat_amount' => 0.75,
        ]);

        $items = $this->invoke(
            app(InvoiceController::class),
            'buildRecruitmentCreditNoteItems',
            $invoice,
            5.0,
            3745.0
        );

        $this->assertSame('تكاليف الاستقدام المباشر', $items[0]['label']);
        $this->assertSame(5.0, $items[0]['line_subtotal']);
        $this->assertSame(0.75, $items[0]['vat_amount']);
        $this->assertSame('تكاليف الاستقدام الخارجية', $items[1]['label']);
        $this->assertSame(3745.0, $items[1]['line_subtotal']);
        $this->assertSame(0.0, $items[1]['vat_amount']);
    }

    public function test_ingaz_delegation_invoice_exposes_the_same_credit_note_action(): void
    {
        $admin = Mockery::mock();
        $admin->shouldReceive('can')->andReturnTrue();
        $guard = Mockery::mock();
        $guard->shouldReceive('user')->andReturn($admin);
        Auth::shouldReceive('guard')->with('admin')->andReturn($guard);

        $row = (object) [
            'row_type' => 'invoice',
            'id' => 101,
            'contract_id' => null,
            'ingaz_delegation_id' => 332,
            'legacy_invoice_number' => null,
            'legacy_zatca_shared' => null,
            'finance_sale_id' => 100,
        ];

        $html = $this->invoke(app(InvoiceController::class), 'standardInvoiceActions', $row);

        $this->assertStringContainsString('/admin/invoices/101/credit-notes/create', $html);
        $this->assertStringContainsString('دون تغيير العقد أو التفويض', $html);
    }

    public function test_general_invoice_exposes_the_same_credit_note_action(): void
    {
        $admin = Mockery::mock();
        $admin->shouldReceive('can')->andReturnTrue();
        $guard = Mockery::mock();
        $guard->shouldReceive('user')->andReturn($admin);
        Auth::shouldReceive('guard')->with('admin')->andReturn($guard);

        $row = (object) [
            'row_type' => 'invoice', 'id' => 202, 'contract_id' => null,
            'ingaz_delegation_id' => null, 'sponsorship_transfer_id' => null,
            'base_invoice_id' => 202, 'legacy_invoice_number' => null,
            'legacy_zatca_shared' => null, 'finance_sale_id' => null,
        ];

        $html = $this->invoke(app(InvoiceController::class), 'standardInvoiceActions', $row);
        $this->assertStringContainsString('/admin/invoices/202/credit-notes/create', $html);
        $this->assertStringContainsString('الفاتورة الأصلية', $html);
    }

    public function test_partial_direct_credit_reverses_the_matching_original_constraint_line(): void
    {
        $invoice = new Invoice([
            'direct_recruitment_cost' => 1000,
            'direct_recruitment_count' => 1,
            'external_recruitment_cost' => 7000,
            'external_recruitment_count' => 1,
            'vat_amount' => 150,
            'total_due' => 8150,
        ]);
        $note = new RecruitmentInvoiceCreditNote([
            'subtotal' => 400,
            'line_items' => [[
                'key' => 'direct',
                'line_subtotal' => 400,
            ]],
        ]);
        $details = new Collection([
            new ConstraintDetails(['account_id' => 10, 'cost_center_id' => 20, 'creditor_value' => 1000]),
            new ConstraintDetails(['account_id' => 11, 'cost_center_id' => 21, 'creditor_value' => 7000]),
        ]);

        $allocations = $this->invoke(
            app(InvoiceController::class),
            'recruitmentCreditNoteNetAllocations',
            $note,
            $invoice,
            $details,
            'creditor_value'
        );

        $this->assertSame([[
            'account_id' => 10,
            'cost_center_id' => 20,
            'amount' => 400.0,
        ]], $allocations);
    }

    public function test_zatca_credit_note_xml_contains_type_reference_and_reason(): void
    {
        $generator = new class {
            use \Nami\Finance\Traits\ZatcaInvoicesTrait;
        };

        $xml = $generator->getDefaultSimplifiedTaxInvoice([
            'invoice_serial_number' => '103',
            'uuid' => '00000000-0000-0000-0000-000000000103',
            'date' => '2026-08-17',
            'time' => '12:00:00',
            'previous_invoice_hash' => base64_encode(hash('sha256', 'previous', true)),
            'invoice_counter_number' => '103',
            'invoice_type' => '0211010',
            'invoice_type_no' => '381',
            'billing_reference' => '102',
            'adjustment_reason' => 'إلغاء خدمة الاستقدام',
            'CRN_number' => '1010000000',
            'VAT_number' => '311447466300003',
            'VAT_name' => 'شركة اختبار',
            'street' => 'شارع الاختبار',
            'building' => '1234',
            'plot_identification' => '5678',
            'city_subdivision' => 'حي الاختبار',
            'city' => 'الرياض',
            'postal' => '12345',
            'CRN_number_CUSTOMER' => '1000000000',
            'street_CUSTOMER' => 'شارع العميل',
            'building_CUSTOMER' => '4321',
            'plot_identification_CUSTOMER' => '8765',
            'city_subdivision_CUSTOMER' => 'حي العميل',
            'city_CUSTOMER' => 'الرياض',
            'postal_CUSTOMER' => '54321',
            'sale' => (object) [
                'total' => 100,
                'grand_total' => 115,
                'order_discount' => 0,
                'product_tax' => 15,
            ],
            'details' => [
                (object) [
                    'product_name' => 'مرتجع خدمة استقدام',
                    'unit_quantity' => 1,
                    'net_unit_price' => 100,
                    'item_tax' => 15,
                    'subtotal' => 115,
                    'city_tax' => 0,
                ],
            ],
        ]);

        $this->assertStringContainsString('<cbc:InvoiceTypeCode name="0211010">381</cbc:InvoiceTypeCode>', $xml);
        $this->assertStringContainsString('<cbc:ID>102</cbc:ID>', $xml);
        $this->assertStringContainsString('<cbc:InstructionNote>إلغاء خدمة الاستقدام</cbc:InstructionNote>', $xml);
        $this->assertStringNotContainsString('Invoice Number: 348', $xml);
    }

    private function invoke(object $object, string $method, mixed ...$arguments): mixed
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($object, ...$arguments);
    }
}
