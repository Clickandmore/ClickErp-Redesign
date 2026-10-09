<?php

namespace Tests\Feature\Finance;

use App\Models\auth\Admin;
use App\Models\Permission;
use App\PermKeys;
use App\Support\InvoiceSupplierWorkflow;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvoiceSupplierWorkflowSettingsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $actor = Admin::factory()->create();
        Permission::withoutGlobalScopes()->updateOrCreate(
            ['name' => 'finance_module', 'guard_name' => 'admin'],
            ['display_name' => 'finance_module', 'group_name' => 'finance', 'section_name' => 'finance', 'is_active' => true]
        );
        $actor->givePermissionTo('finance_module');
        $actor->givePermissionTo(Permission::firstOrCreate(['name' => PermKeys::INVOICES_CREATE, 'guard_name' => 'admin']));
        $this->actingAs($actor, 'admin');
        DB::table('fn_confirm_settings')->where('title', InvoiceSupplierWorkflow::SETTING_KEY)->delete();
    }

    public function test_page_defaults_to_legacy_and_persists_switch_both_ways(): void
    {
        $this->get(route('finance.confirm-settings.index'))->assertOk()
            ->assertSee('name="data[invoice_supplier_workflow][value]" value="legacy" checked', false);
        $this->assertFalse(InvoiceSupplierWorkflow::enabled());
        foreach (['updated', 'legacy'] as $mode) {
            $this->postJson(route('finance.confirm-settings.store'), ['data' => [
                InvoiceSupplierWorkflow::SETTING_KEY => ['title' => InvoiceSupplierWorkflow::SETTING_KEY, 'value' => $mode],
            ]])->assertOk();
            $this->assertSame($mode === 'updated', InvoiceSupplierWorkflow::enabled());
            $this->get(route('finance.confirm-settings.index'))->assertOk()
                ->assertSee('name="data[invoice_supplier_workflow][value]" value="' . $mode . '" checked', false);
        }
    }

    public function test_invalid_workflow_is_rejected_without_enabling_update(): void
    {
        $this->postJson(route('finance.confirm-settings.store'), ['data' => [
            InvoiceSupplierWorkflow::SETTING_KEY => ['title' => InvoiceSupplierWorkflow::SETTING_KEY, 'value' => 'invalid'],
        ]])->assertUnprocessable()->assertJsonValidationErrors('data.invoice_supplier_workflow.value');
        $this->assertFalse(InvoiceSupplierWorkflow::enabled());
    }

    public function test_supplier_invoice_options_are_hidden_in_legacy_and_visible_when_enabled(): void
    {
        $this->get(route('invoices.create'))->assertOk()->assertDontSee('id="supplierInvoiceOptions"', false)
            ->assertDontSee('id="musaned_commission_amount"', false);
        DB::table('fn_confirm_settings')->insert([
            'title' => InvoiceSupplierWorkflow::SETTING_KEY, 'value' => InvoiceSupplierWorkflow::UPDATED,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->get(route('invoices.create'))->assertOk()->assertSee('id="supplierInvoiceOptions"', false)
            ->assertSee('id="musaned_commission_amount"', false);
    }
}
