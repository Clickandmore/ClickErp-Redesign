<?php

namespace Tests\Feature;

use App\Http\Controllers\ingaz\InvoiceController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InvoiceIndexFiltersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'invoice_index_test', 'database.connections.invoice_index_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        // جدول يحاكي أعمدة الاستعلام المجمّع invoice_rows في شاشة الفواتير.
        Schema::create('invoice_rows', function (Blueprint $t) {
            $t->string('key');
            $t->string('row_type');
            foreach (['invoice_sort_number', 'legacy_order_number', 'contract_id', 'ingaz_delegation_id', 'sponsorship_transfer_id',
                         'rental_contract_id', 'is_paid', 'send_to_zatca', 'finance_sale_id', 'legacy_zatca_shared'] as $column) {
                $t->integer($column)->nullable();
            }
            foreach (['rental_contract_number', 'adjustment_type', 'client_name', 'client_phone', 'zatca_status'] as $column) {
                $t->string($column)->nullable();
            }
            $t->decimal('total_due', 12, 2)->default(0);
            $t->dateTime('created_at')->nullable();
        });

        $row = fn (array $values) => array_merge([
            'invoice_sort_number' => 1, 'legacy_order_number' => null, 'contract_id' => null, 'ingaz_delegation_id' => null,
            'sponsorship_transfer_id' => null, 'rental_contract_id' => null, 'is_paid' => 0, 'send_to_zatca' => 1,
            'finance_sale_id' => null, 'legacy_zatca_shared' => null, 'rental_contract_number' => null, 'adjustment_type' => null,
            'client_name' => '-', 'client_phone' => '', 'zatca_status' => null, 'total_due' => 100, 'created_at' => '2026-10-10 10:00:00',
        ], $values);

        DB::table('invoice_rows')->insert([
            $row(['key' => 'recruitment_paid', 'row_type' => 'invoice', 'contract_id' => 5, 'is_paid' => 1, 'finance_sale_id' => 9,
                'zatca_status' => 'REPORTED', 'client_name' => 'سارة', 'total_due' => 5000]),
            $row(['key' => 'delegation_unpaid', 'row_type' => 'invoice', 'ingaz_delegation_id' => 7, 'finance_sale_id' => 10,
                'zatca_status' => 'NOT_CLEARED', 'created_at' => '2026-09-01 08:00:00']),
            $row(['key' => 'general', 'row_type' => 'invoice', 'send_to_zatca' => 0, 'client_phone' => '0551234567']),
            $row(['key' => 'rental', 'row_type' => 'rental', 'rental_contract_id' => 3, 'rental_contract_number' => 'R-88', 'send_to_zatca' => 0]),
            $row(['key' => 'credit', 'row_type' => 'recruitment_credit_note', 'contract_id' => 5, 'is_paid' => 1, 'adjustment_type' => 'credit_note',
                'finance_sale_id' => 11]),
            $row(['key' => 'rental_debit', 'row_type' => 'rental_adjustment', 'rental_contract_id' => 3, 'rental_contract_number' => 'R-88', 'adjustment_type' => 'income']),
        ]);
    }

    private function keys(array $params): array
    {
        $controller = app(InvoiceController::class);
        $request = Request::create('/', 'GET', $params);
        $query = DB::table('invoice_rows');
        (function () use ($query, $request) {
            $this->applyStandardInvoiceFilters($query, $request);
            $this->applyStandardInvoiceSearch($query, $request);
        })->call($controller);

        return $query->orderBy('key')->pluck('key')->all();
    }

    public function test_document_type_filter(): void
    {
        $this->assertSame(['recruitment_paid'], $this->keys(['doc_type' => 'recruitment']));
        $this->assertSame(['delegation_unpaid'], $this->keys(['doc_type' => 'delegation']));
        $this->assertSame(['general'], $this->keys(['doc_type' => 'general']));
        $this->assertSame(['rental'], $this->keys(['doc_type' => 'rental']));
        $this->assertSame(['credit'], $this->keys(['doc_type' => 'credit_note']));
        $this->assertSame(['rental_debit'], $this->keys(['doc_type' => 'rental_adjustment']));
    }

    public function test_payment_and_zatca_filters(): void
    {
        $this->assertSame(['recruitment_paid'], $this->keys(['payment' => 'paid']));
        $this->assertSame(['delegation_unpaid', 'general', 'rental'], $this->keys(['payment' => 'unpaid']));
        $this->assertSame(['credit'], $this->keys(['payment' => 'customer_credit']));
        $this->assertSame(['rental_debit'], $this->keys(['payment' => 'customer_debit']));

        $this->assertSame(['recruitment_paid'], $this->keys(['zatca' => 'reported']));
        $this->assertSame(['delegation_unpaid'], $this->keys(['zatca' => 'rejected']));
        $this->assertSame(['credit'], $this->keys(['zatca' => 'pending']));
        $this->assertSame(['rental', 'rental_debit'], $this->keys(['zatca' => 'not_queued']));
        $this->assertSame(['general'], $this->keys(['zatca' => 'not_required']));
    }

    public function test_dates_amounts_and_search(): void
    {
        $this->assertSame(['delegation_unpaid'], $this->keys(['date_from' => '2026-09-01', 'date_to' => '2026-09-30']));
        $this->assertSame(['recruitment_paid'], $this->keys(['amount_min' => 1000]));
        $this->assertSame(['recruitment_paid'], $this->keys(['search' => ['value' => 'سارة']]));
        $this->assertSame(['general'], $this->keys(['search' => ['value' => '0551234567']]));
        $this->assertSame(['rental', 'rental_debit'], $this->keys(['search' => ['value' => 'R-88']]));
        $this->assertCount(6, $this->keys(['doc_type' => 'unknown', 'date_from' => 'bad', 'amount_min' => 'x']));
    }
}
