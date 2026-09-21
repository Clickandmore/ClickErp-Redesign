<?php

namespace Tests\Feature;

use App\Models\ingaz\Invoice;
use App\Services\SuperAdminInvoiceCorrectionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SuperAdminInvoiceCorrectionTest extends TestCase
{
    use DatabaseTransactions;

    private int $year;
    private array $accounts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->year = DB::table('fn_years')->insertGetId(['title' => 'Correction test', 'from_date' => '2036-01-01', 'to_date' => '2036-12-31', 'status' => 1]);
        $this->accounts = [];
        foreach (['Client', 'Revenue', 'Government', 'VAT'] as $i => $name) {
            $this->accounts[] = DB::table('fn_accounts')->insertGetId(['name' => 'Correction ' . $name, 'code' => random_int(800000000, 999999999), 'type' => 1, 'status' => $i ? 'creditor' : 'debtor', 'level' => 1]);
        }
    }

    private function fixture(): Invoice
    {
        $contract = DB::table('contracts')->insertGetId(['status' => 'new', 'created_at' => now(), 'updated_at' => now()]);
        $journal = $this->journal([7, 3393, 260, 1.05]);
        $id = DB::table('invoices')->insertGetId([
            'contract_id' => $contract, 'direct_recruitment_cost' => 7, 'direct_recruitment_count' => 1,
            'external_recruitment_cost' => 3393, 'external_recruitment_count' => 1,
            'government_cost' => 260, 'government_cost_count' => 1, 'internal_ticket_cost' => 0, 'internal_ticket_count' => 0,
            'subtotal' => 3660, 'vat_amount' => 1.05, 'discount' => 0, 'total_due' => 3661.05,
            'invoice_constraint_id' => $journal, 'created_at' => '2036-08-22 10:30:00', 'updated_at' => '2036-08-22 10:30:00',
        ]);
        return Invoice::withoutGlobalScopes()->findOrFail($id);
    }

    private function journal(array $credits): int
    {
        $id = DB::table('fn_constraints')->insertGetId(['type' => 1, 'value' => array_sum($credits), 'date' => '2036-08-22', 'year_id' => $this->year, 'code' => random_int(80000000, 99999999), 'statement' => 'Correction test']);
        $base = ['constraint_id' => $id, 'date' => '2036-08-22', 'year_id' => $this->year];
        DB::table('fn_constraint_details')->insert($base + ['type' => 'debtor', 'account_id' => $this->accounts[0], 'debtor_value' => array_sum($credits), 'creditor_value' => 0]);
        foreach ($credits as $i => $amount) {
            DB::table('fn_constraint_details')->insert($base + ['type' => 'creditor', 'account_id' => $this->accounts[min($i, 2) + 1], 'debtor_value' => 0, 'creditor_value' => $amount]);
        }
        return $id;
    }

    private function input(Invoice $invoice, array $changes = []): array
    {
        $data = ['invoice_date' => '2036-08-22', 'discount' => '0.00'];
        foreach (SuperAdminInvoiceCorrectionService::LINES as $line) {
            $data[$line['cost']] = number_format($invoice->{$line['cost']}, 2, '.', '');
            $data[$line['count']] = (int) $invoice->{$line['count']};
        }
        return array_replace($data, $changes);
    }

    private function preview(Invoice $invoice, array $data): array
    {
        return $this->withSession(['dev_superadmin_authenticated' => true])->post(route('dev-superadmin.invoice-corrections.preview', $invoice->id), $data)
            ->assertOk()->viewData('plan');
    }

    public function test_all_endpoints_require_the_independent_superadmin_session(): void
    {
        $this->get(route('dev-superadmin.invoice-corrections.index'))->assertRedirect(route('superadmin.login'));
        $this->get(route('dev-superadmin.invoice-corrections.show', 1))->assertRedirect(route('superadmin.login'));
        $this->post(route('dev-superadmin.invoice-corrections.preview', 1))->assertRedirect(route('superadmin.login'));
        $this->put(route('dev-superadmin.invoice-corrections.update', 1))->assertRedirect(route('superadmin.login'));
    }

    public function test_search_by_contract_and_invoice_and_form_render(): void
    {
        $invoice = $this->fixture();
        foreach (['invoice' => $invoice->id, 'contract' => $invoice->contract_id] as $kind => $number) {
            $this->withSession(['dev_superadmin_authenticated' => true])->get(route('dev-superadmin.invoice-corrections.index', ['search_by' => $kind, 'number' => $number]))->assertOk()->assertSee('جلب وتعديل');
        }
        $this->get(route('dev-superadmin.invoice-corrections.show', $invoice->id))->assertOk()->assertSee('تكاليف الاستقدام المباشر')->assertSee('name="government_cost"', false);
    }

    public function test_date_change_updates_invoice_and_all_primary_lines_without_logs(): void
    {
        $invoice = $this->fixture();
        $data = $this->input($invoice, ['invoice_date' => '2036-08-02']);
        $logs = DB::table('activity_log')->count();
        $plan = $this->preview($invoice, $data);
        $this->assertSame('2036-08-22', $invoice->fresh()->created_at->toDateString());
        $this->put(route('dev-superadmin.invoice-corrections.update', $invoice->id), $data + ['preview_token' => $plan['token']])->assertRedirect();
        $this->assertSame('2036-08-02 10:30:00', $invoice->fresh()->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2036-08-02', DB::table('fn_constraints')->where('id', $invoice->invoice_constraint_id)->value('date'));
        $this->assertSame(0, DB::table('fn_constraint_details')->where('constraint_id', $invoice->invoice_constraint_id)->where('date', '!=', '2036-08-02')->count());
        $this->assertSame($logs, DB::table('activity_log')->count());
    }

    public function test_direct_cost_change_recalculates_tax_and_synchronizes_balanced_journal(): void
    {
        $invoice = $this->fixture();
        $data = $this->input($invoice, ['direct_recruitment_cost' => '737.00']);
        $beforeAccounts = DB::table('fn_constraint_details')->where('constraint_id', $invoice->invoice_constraint_id)->pluck('account_id', 'id')->all();
        $logs = DB::table('activity_log')->count();
        $plan = $this->preview($invoice, $data);
        $this->put(route('dev-superadmin.invoice-corrections.update', $invoice->id), $data + ['preview_token' => $plan['token']])->assertRedirect()->assertSessionHasNoErrors();
        $invoice->refresh();
        $this->assertEquals(737, $invoice->direct_recruitment_cost);
        $this->assertEquals(110.55, $invoice->vat_amount);
        $this->assertEquals(4500.55, $invoice->total_due);
        $rows = DB::table('fn_constraint_details')->where('constraint_id', $invoice->invoice_constraint_id)->get();
        $this->assertEqualsWithDelta(4500.55, $rows->sum('debtor_value'), 0.001);
        $this->assertEqualsWithDelta(4500.55, $rows->sum('creditor_value'), 0.001);
        $this->assertSame($beforeAccounts, $rows->pluck('account_id', 'id')->all());
        $this->assertSame($logs, DB::table('activity_log')->count());
    }

    public function test_arrival_revenue_is_updated_without_changing_its_event_date(): void
    {
        $invoice = $this->fixture();
        $arrival = $this->journal([7, 3653]);
        DB::table('invoices')->where('id', $invoice->id)->update(['arrival_revenue_constraint_id' => $arrival]);
        $data = $this->input($invoice, ['direct_recruitment_cost' => '737.00', 'invoice_date' => '2036-08-02']);
        $plan = $this->preview($invoice, $data);
        $this->put(route('dev-superadmin.invoice-corrections.update', $invoice->id), $data + ['preview_token' => $plan['token']])->assertSessionHasNoErrors();
        $this->assertEquals(4390, DB::table('fn_constraints')->where('id', $arrival)->value('value'));
        $this->assertSame('2036-08-22', DB::table('fn_constraints')->where('id', $arrival)->value('date'));
    }

    public function test_stale_or_tampered_preview_cannot_change_anything(): void
    {
        $invoice = $this->fixture();
        $data = $this->input($invoice, ['direct_recruitment_cost' => '737.00']);
        $plan = $this->preview($invoice, $data);
        $this->putJson(route('dev-superadmin.invoice-corrections.update', $invoice->id), array_replace($data, ['direct_recruitment_cost' => '900.00', 'preview_token' => $plan['token']]))->assertUnprocessable();
        DB::table('fn_constraints')->where('id', $invoice->invoice_constraint_id)->update(['statement' => 'Changed after preview']);
        $this->putJson(route('dev-superadmin.invoice-corrections.update', $invoice->id), $data + ['preview_token' => $plan['token']])->assertUnprocessable();
        $this->assertEquals(7, $invoice->fresh()->direct_recruitment_cost);
    }

    public function test_missing_unbalanced_and_shared_journals_are_rejected(): void
    {
        $invoice = $this->fixture();
        $data = $this->input($invoice, ['direct_recruitment_cost' => '737.00']);
        $this->withSession(['dev_superadmin_authenticated' => true]);
        DB::table('fn_constraint_details')->where('constraint_id', $invoice->invoice_constraint_id)->where('type', 'debtor')->update(['debtor_value' => 1]);
        $this->postJson(route('dev-superadmin.invoice-corrections.preview', $invoice->id), $data)->assertUnprocessable();
        DB::table('fn_constraint_details')->where('constraint_id', $invoice->invoice_constraint_id)->where('type', 'debtor')->update(['debtor_value' => 3661.05]);
        $other = $this->fixture();
        DB::table('invoices')->where('id', $other->id)->update(['invoice_constraint_id' => $invoice->invoice_constraint_id]);
        $this->postJson(route('dev-superadmin.invoice-corrections.preview', $invoice->id), $data)->assertUnprocessable();
        DB::table('invoices')->where('id', $invoice->id)->update(['invoice_constraint_id' => null]);
        $this->postJson(route('dev-superadmin.invoice-corrections.preview', $invoice->id), $data)->assertUnprocessable();
    }

    public function test_closed_period_and_external_copy_block_local_only_correction(): void
    {
        $invoice = $this->fixture();
        $data = $this->input($invoice, ['direct_recruitment_cost' => '737.00']);
        $this->withSession(['dev_superadmin_authenticated' => true]);
        DB::table('fn_fiscal_year_closures')->insert(['title' => 'Test closure', 'closing_date' => '2036-12-31']);
        $this->postJson(route('dev-superadmin.invoice-corrections.preview', $invoice->id), $data)->assertUnprocessable();
        DB::table('invoices')->where('id', $invoice->id)->update(['send_to_zatca' => true]);
        $this->postJson(route('dev-superadmin.invoice-corrections.preview', $invoice->id), $data)->assertUnprocessable();
    }

    public function test_amounts_quantities_and_discount_are_saved_together(): void
    {
        $invoice = $this->fixture();
        $data = $this->input($invoice, ['direct_recruitment_cost' => '100.00', 'direct_recruitment_count' => 2,
            'external_recruitment_cost' => '300.00', 'external_recruitment_count' => 3,
            'government_cost' => '50.00', 'government_cost_count' => 4, 'discount' => '100.00',
            'visa_recruited_count' => 2, 'insured_count' => 2]);
        $plan = $this->preview($invoice, $data);
        $this->put(route('dev-superadmin.invoice-corrections.update', $invoice->id), $data + ['preview_token' => $plan['token']])->assertSessionHasNoErrors();
        $invoice->refresh();
        $this->assertEquals(1300, $invoice->subtotal);
        $this->assertEquals(1230, $invoice->total_due);
        $this->assertEquals(30, $invoice->vat_amount);
        $this->assertEquals(2, $invoice->insured_count);
        $rows = DB::table('fn_constraint_details')->where('constraint_id', $invoice->invoice_constraint_id)->get();
        $this->assertEqualsWithDelta(1230, $rows->sum('creditor_value'), 0.001);
        $this->assertEqualsWithDelta(1230, $rows->sum('debtor_value'), 0.001);
    }

    public function test_write_failure_rolls_back_previously_updated_journals(): void
    {
        $invoice = $this->fixture();
        $arrival = $this->journal([7, 3653]);
        DB::table('invoices')->where('id', $invoice->id)->update(['arrival_revenue_constraint_id' => $arrival]);
        $data = $this->input($invoice, ['direct_recruitment_cost' => '737.00']);
        $service = app(SuperAdminInvoiceCorrectionService::class);
        $plan = $service->preview($invoice->id, $data);
        $dispatcher = \Nami\Finance\Models\Constraint::getEventDispatcher();
        \Nami\Finance\Models\Constraint::setEventDispatcher(clone $dispatcher);
        \Nami\Finance\Models\Constraint::updating(function ($journal) use ($arrival) {
            if ($journal->id === $arrival) throw new \RuntimeException('Simulated second journal failure');
        });
        try {
            $service->apply($invoice->id, $data, $plan['token']);
            $this->fail('Expected write failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated second journal failure', $exception->getMessage());
        } finally {
            \Nami\Finance\Models\Constraint::setEventDispatcher($dispatcher);
        }
        $this->assertEquals(7, $invoice->fresh()->direct_recruitment_cost);
        $this->assertEquals(3661.05, DB::table('fn_constraints')->where('id', $invoice->invoice_constraint_id)->value('value'));
        $this->assertEqualsWithDelta(3661.05, DB::table('fn_constraint_details')->where('constraint_id', $invoice->invoice_constraint_id)->sum('debtor_value'), 0.001);
    }

    public function test_oversized_computed_total_is_rejected_before_writing(): void
    {
        $invoice = $this->fixture();
        $this->withSession(['dev_superadmin_authenticated' => true])->postJson(route('dev-superadmin.invoice-corrections.preview', $invoice->id),
            $this->input($invoice, ['direct_recruitment_cost' => '99999999.99', 'direct_recruitment_count' => 10000]))->assertUnprocessable();
        $this->assertEquals(7, $invoice->fresh()->direct_recruitment_cost);
    }

    private function pendingSaleFixture(Invoice $invoice): int
    {
        $category = DB::table('fn_client_categories')->insertGetId(['name' => 'Correction']);
        $client = DB::table('fn_clients')->insertGetId(['name' => 'Correction', 'code' => 'correction', 'address' => '-', 'phone' => '-', 'email' => '-', 'tax_number' => '-', 'days_credit_period' => '0', 'credit_limit' => '0', 'client_category_id' => $category]);
        $unit = DB::table('fn_units')->insertGetId(['title' => 'Correction']);
        $category = DB::table('fn_categories')->insertGetId(['title' => 'Correction']);
        $item = DB::table('fn_items')->insertGetId(['name' => 'Correction', 'code' => random_int(100000,999999), 'type' => 'service', 'main_category_id' => $category, 'sale_unit_id' => $unit, 'purchase_unit_id' => $unit, 'details' => '-', 'sale_account_id' => $this->accounts[1], 'stock_account_id' => $this->accounts[1]]);
        $sale = DB::table('fn_sales')->insertGetId(['code' => random_int(100000,999999), 'client_id' => $client, 'debit_client_id' => $this->accounts[0], 'date' => '2036-08-22', 'total_item' => 3660, 'discount' => 0, 'total_tax_value' => 1.05, 'final_total' => 3661.05, 'type' => 'sales', 'payment_type' => 'cash']);
        DB::table('fn_sale_items')->insert(['sale_id' => $sale, 'item_id' => $item, 'price' => 3660, 'amount' => 1, 'total_price_before' => 3660, 'tax_value' => 1.05, 'tax_per' => 15, 'final_total' => 3661.05, 'status' => 'new', 'stock_id' => null]);
        DB::table('invoices')->where('id', $invoice->id)->update(['finance_sale_id' => $sale, 'send_to_zatca' => true]);
        return $sale;
    }

    public function test_pending_sale_date_and_amounts_are_synchronized_without_activity_logs(): void
    {
        $invoice = $this->fixture();
        $sale = $this->pendingSaleFixture($invoice);
        $data = $this->input($invoice, ['invoice_date' => '2036-08-02', 'direct_recruitment_cost' => '737.00']);
        $logs = DB::table('activity_log')->count();
        $plan = $this->preview($invoice, $data);
        $this->assertSame($sale, $plan['sale']['before']['id']);
        $this->put(route('dev-superadmin.invoice-corrections.update', $invoice->id), $data + ['preview_token' => $plan['token']])->assertSessionHasNoErrors();
        $this->assertSame('2036-08-02', DB::table('fn_sales')->where('id', $sale)->value('date'));
        $this->assertEquals(4500.55, DB::table('fn_sales')->where('id', $sale)->value('final_total'));
        $this->assertEquals(4500.55, DB::table('fn_sale_items')->where('sale_id', $sale)->value('final_total'));
        $this->assertEquals(110.55, DB::table('fn_sale_items')->where('sale_id', $sale)->value('tax_value'));
        $this->assertSame($logs, DB::table('activity_log')->count());
    }

    public function test_sale_change_after_preview_invalidates_confirmation(): void
    {
        $invoice = $this->fixture();
        $sale = $this->pendingSaleFixture($invoice);
        $data = $this->input($invoice, ['invoice_date' => '2036-08-02']);
        $plan = $this->preview($invoice, $data);
        DB::table('fn_sales')->where('id', $sale)->update(['notes' => 'Changed']);
        $this->putJson(route('dev-superadmin.invoice-corrections.update', $invoice->id), $data + ['preview_token' => $plan['token']])->assertUnprocessable();
        $this->assertSame('2036-08-22', $invoice->fresh()->created_at->format('Y-m-d'));
    }

    public function test_date_only_correction_updates_pending_sale_and_preserves_amounts(): void
    {
        $invoice = $this->fixture();
        $sale = $this->pendingSaleFixture($invoice);
        $data = $this->input($invoice, ['invoice_date' => '2036-08-02']);
        $items = DB::table('fn_sale_items')->where('sale_id', $sale)->get()->toJson();
        $plan = $this->preview($invoice, $data);
        $this->put(route('dev-superadmin.invoice-corrections.update', $invoice->id), $data + ['preview_token' => $plan['token']])->assertSessionHasNoErrors();
        $this->assertSame('2036-08-02', DB::table('fn_sales')->where('id', $sale)->value('date'));
        $this->assertEquals(3661.05, DB::table('fn_sales')->where('id', $sale)->value('final_total'));
        $this->assertSame($items, DB::table('fn_sale_items')->where('sale_id', $sale)->get()->toJson());
    }

    public function test_zatca_document_blocks_correction_even_if_sale_reference_is_missing(): void
    {
        $invoice = $this->fixture();
        $sale = $this->pendingSaleFixture($invoice);
        DB::table('zatca_invoices')->insert(['sale_id' => $sale, 'zatca_status' => 'REPORTED']);
        $this->withSession(['dev_superadmin_authenticated' => true])->postJson(route('dev-superadmin.invoice-corrections.preview', $invoice->id),
            $this->input($invoice, ['invoice_date' => '2036-08-02']))->assertUnprocessable()->assertJsonValidationErrors('invoice');
        $this->assertSame('2036-08-22', $invoice->fresh()->created_at->format('Y-m-d'));
    }

    public function test_send_intent_without_sales_copy_does_not_block_correction(): void
    {
        $invoice = $this->fixture();
        DB::table('invoices')->where('id', $invoice->id)->update(['send_to_zatca' => true]);
        $plan = $this->preview($invoice, $this->input($invoice, ['invoice_date' => '2036-08-02']));
        $this->assertNull($plan['sale']);
    }

    public function test_already_corrected_journal_is_preserved_while_invoice_and_sale_catch_up(): void
    {
        $invoice = $this->fixture();
        $sale = $this->pendingSaleFixture($invoice);
        $journal = $this->journal([737, 3653, 110.55]);
        DB::table('invoices')->where('id', $invoice->id)->update(['invoice_constraint_id' => $journal]);
        $rows = DB::table('fn_constraint_details')->where('constraint_id', $journal)->get()->toJson();
        $header = DB::table('fn_constraints')->where('id', $journal)->first();
        $data = $this->input($invoice, ['direct_recruitment_cost' => '737.00']);
        $plan = $this->preview($invoice, $data);
        $this->assertTrue($plan['journals'][0]['already_matches_target']);
        $this->assertFalse($plan['journals'][0]['changed']);
        $this->put(route('dev-superadmin.invoice-corrections.update', $invoice->id), $data + ['preview_token' => $plan['token']])->assertSessionHasNoErrors();
        $this->assertEquals(4500.55, $invoice->fresh()->total_due);
        $this->assertEquals(4500.55, DB::table('fn_sales')->where('id', $sale)->value('final_total'));
        $this->assertSame($rows, DB::table('fn_constraint_details')->where('constraint_id', $journal)->get()->toJson());
        $this->assertEquals($header, DB::table('fn_constraints')->where('id', $journal)->first());
    }

    public function test_target_total_without_matching_component_distribution_is_rejected(): void
    {
        $invoice = $this->fixture();
        $journal = $this->journal([1000, 3500.55]);
        DB::table('invoices')->where('id', $invoice->id)->update(['invoice_constraint_id' => $journal]);
        $this->withSession(['dev_superadmin_authenticated' => true])->postJson(route('dev-superadmin.invoice-corrections.preview', $invoice->id),
            $this->input($invoice, ['direct_recruitment_cost' => '737.00']))->assertUnprocessable();
        $this->assertEquals(7, $invoice->fresh()->direct_recruitment_cost);
    }
}
