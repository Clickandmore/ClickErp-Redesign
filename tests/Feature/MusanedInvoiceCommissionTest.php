<?php

namespace Tests\Feature;

use App\Http\Controllers\ingaz\MusanedTransferController;
use App\Http\Controllers\ingaz\RecruitmentContractExternalOfficeDispatchController;
use App\Models\Contract;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\Maid;
use App\Models\RecruitmentOffices;
use App\Models\MusanedTransfer;
use App\Models\MusanedTransferItem;
use App\Models\auth\Admin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mockery;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\ConfirmSetting;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MusanedInvoiceCommissionTest extends TestCase
{
    use DatabaseTransactions;
    use \Tests\Concerns\InteractsWithBranches;

    private array $journals = [];
    private Account $source;
    private Account $expense;
    private Account $tax;

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
        $this->source = $this->account('مساند اختبار', 15);
        $this->expense = $this->account('عمولة مساند اختبار', 1);
        $this->tax = $this->account('ضريبة مشتريات اختبار', 10);
        foreach ([
            'automatic_journal_entries' => 'on',
            \App\Support\InvoiceSupplierWorkflow::SETTING_KEY => \App\Support\InvoiceSupplierWorkflow::UPDATED,
            'musaned_account' => $this->source->id,
            'musaned_intermediate_account' => $this->source->id,
            'musaned_commission_account' => $this->expense->id,
            'expense_tax_account' => $this->tax->id,
        ] as $key => $value) {
            DB::table('fn_confirm_settings')->where('title', $key)->delete();
            ConfirmSetting::query()->create(['title' => $key, 'value' => (string) $value]);
        }
        DB::table('fn_confirm_settings')->where('title', 'tax_rate_expenditure')->delete();
        $service = Mockery::mock(ConstraintService::class);
        $service->shouldReceive('lastCode')->andReturn('999999');
        $service->shouldReceive('storeConstraint')->andReturnUsing(function ($data) {
            $this->journals[] = $data;
            return Constraint::withoutEvents(fn () => Constraint::query()->create([
                'type' => 1, 'code' => 9000000 + count($this->journals), 'date' => $data['date'], 'value' => $data['value'], 'statement' => $data['statement'],
            ]));
        });
        $service->shouldReceive('storeConstraintDetails')->andReturnNull();
        $this->app->instance(ConstraintService::class, $service);
        $year = Mockery::mock(YearService::class);
        $year->shouldReceive('checkDateRange')->andReturn((object) ['id' => 1]);
        $this->app->instance(YearService::class, $year);
    }

    public function test_new_transfer_and_items_use_document_branch(): void
    {
        $this->enableBranchesModule();
        $branch = $this->makeBranch();
        $invoice = $this->invoice();
        DB::table('contracts')->where('id', $invoice->contract_id)->update(['branch_id' => $branch->id]);
        DB::table('invoices')->where('id', $invoice->id)->update(['branch_id' => $branch->id]);
        DB::table('fn_confirm_settings')->where('title', 'automatic_journal_entries')->update(['value' => 'off']);
        $transfer = app(MusanedTransferController::class)->createTransfer(new \Illuminate\Http\Request([
            'transfer_date' => now()->toDateString(),
            'items' => [['contract_id' => $invoice->contract_id, 'transferred_amount' => 10000]],
        ]), app(ConstraintService::class), app(YearService::class));
        $this->assertSame($branch->id, $transfer->branch_id);
        $this->assertSame($branch->id, $transfer->items->first()->branch_id);
    }

    public function test_mixed_branch_transfer_is_rejected_without_creating_records(): void
    {
        $this->enableBranchesModule();
        $invoices = [$this->invoice(), $this->invoice()];
        foreach ($invoices as $invoice) {
            $branch = $this->makeBranch();
            DB::table('contracts')->where('id', $invoice->contract_id)->update(['branch_id' => $branch->id]);
            DB::table('invoices')->where('id', $invoice->id)->update(['branch_id' => $branch->id]);
        }
        DB::table('fn_confirm_settings')->where('title', 'automatic_journal_entries')->update(['value' => 'off']);
        $before = DB::table('musaned_transfers')->count();
        try {
            app(MusanedTransferController::class)->createTransfer(new \Illuminate\Http\Request([
                'transfer_date' => now()->toDateString(),
                'items' => array_map(fn ($invoice) => ['contract_id' => $invoice->contract_id, 'transferred_amount' => 10000], $invoices),
            ]), app(ConstraintService::class), app(YearService::class));
            $this->fail('Expected mixed branches to be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('items', $e->errors());
            $this->assertStringContainsString('فروع مختلفة', $e->errors()['items'][0]);
        }
        $this->assertSame($before, DB::table('musaned_transfers')->count());
    }

    public function test_both_transfer_journals_receive_transfer_branch(): void
    {
        $invoice = $this->invoice();
        $item = (new MusanedTransferItem)->forceFill([
            'contract_id' => $invoice->contract_id, 'transferred_amount' => 100,
            'commission_amount' => 0, 'expense_tax_amount' => 0, 'source_account_id' => $this->source->id,
        ])->setRelation('invoice', $invoice);
        $transfer = (new MusanedTransfer)->forceFill([
            'branch_id' => 2, 'reference_number' => 'BRANCH-TEST', 'transfer_date' => now(), 'transferred_total' => 100,
        ])->setRelation('items', collect([$item]));
        $controller = app(MusanedTransferController::class);
        $settlement = new \ReflectionMethod($controller, 'storeSettlementConstraint');
        $settlement->setAccessible(true);
        $settlement->invoke($controller, $transfer, app(ConstraintService::class), (object) ['id' => 1], 999, $this->expense->id, null);
        $bank = new \ReflectionMethod($controller, 'storeBankTransferConstraint');
        $bank->setAccessible(true);
        $bank->invoke($controller, $transfer, app(ConstraintService::class), (object) ['id' => 1], 999, $this->source->id);
        $this->assertCount(2, $this->journals);
        $this->assertSame(2, $this->journals[0]['branch_id']);
        $this->assertSame(2, $this->journals[1]['branch_id']);
    }

    public function test_disabled_branches_preserve_existing_transfer_behavior(): void
    {
        $this->enableBranchesModule(false);
        $invoices = [$this->invoice(), $this->invoice()];
        foreach ($invoices as $invoice) {
            $branch = $this->makeBranch();
            DB::table('contracts')->where('id', $invoice->contract_id)->update(['branch_id' => $branch->id]);
            DB::table('invoices')->where('id', $invoice->id)->update(['branch_id' => $branch->id]);
        }
        DB::table('fn_confirm_settings')->where('title', 'automatic_journal_entries')->update(['value' => 'off']);
        $transfer = app(MusanedTransferController::class)->createTransfer(new \Illuminate\Http\Request([
            'transfer_date' => now()->toDateString(),
            'items' => array_map(fn ($invoice) => ['contract_id' => $invoice->contract_id, 'transferred_amount' => 10000], $invoices),
        ]), app(ConstraintService::class), app(YearService::class));
        $this->assertNull($transfer->branch_id);
        $this->assertCount(2, $transfer->items);
    }

    private function account(string $name, int $type): Account
    {
        return Account::withoutEvents(fn () => Account::query()->create([
            'name' => $name, 'type' => $type, 'status' => 'debtor', 'code' => random_int(80000000, 99999999),
        ]));
    }

    private function invoice(): Invoice
    {
        $contract = Contract::withoutEvents(fn () => Contract::query()->create(['status' => 'new']));
        return Invoice::withoutEvents(fn () => Invoice::query()->create([
            'contract_id' => $contract->id, 'total_due' => 10000, 'created_at' => now(),
        ]));
    }

    public function test_fully_credited_recruitment_invoice_is_replaced_in_selection_and_transfer(): void
    {
        $old = $this->invoice();
        $branch = $this->makeBranch();
        DB::table('contracts')->where('id', $old->contract_id)->update(['branch_id' => $branch->id]);
        DB::table('recruitment_invoice_credit_notes')->insert([
            'invoice_id' => $old->id, 'contract_id' => $old->contract_id,
            'issue_date' => now()->toDateString(), 'reason' => 'Replacement', 'total' => 10000,
        ]);
        $new = Invoice::withoutEvents(fn () => Invoice::create(['contract_id' => $old->contract_id, 'branch_id' => $branch->id, 'total_due' => 7000]));
        $controller = app(MusanedTransferController::class);
        $this->assertEquals([$new->id], $controller->getEligibleInvoicesQuery()->where('contract_id', $old->contract_id)->pluck('id')->all());
        DB::table('fn_confirm_settings')->where('title', 'automatic_journal_entries')->update(['value' => 'off']);
        $transfer = $controller->createTransfer(new \Illuminate\Http\Request([
            'transfer_date' => now()->toDateString(),
            'items' => [['contract_id' => $old->contract_id, 'invoice_id' => $new->id, 'transferred_amount' => 6800]],
        ]), app(ConstraintService::class), app(YearService::class));
        $this->assertEquals($new->id, $transfer->items->first()->invoice_id);
        $this->assertEquals(7000, $transfer->items->first()->customer_paid_amount);
        $this->assertEquals(200, $transfer->items->first()->commission_amount);
    }

    public function test_stale_or_wrong_contract_invoice_is_rejected_instead_of_silently_replaced(): void
    {
        $old = $this->invoice();
        DB::table('recruitment_invoice_credit_notes')->insert([
            'invoice_id' => $old->id, 'contract_id' => $old->contract_id,
            'issue_date' => now()->toDateString(), 'reason' => 'Replacement', 'total' => 10000,
        ]);
        Invoice::withoutEvents(fn () => Invoice::create(['contract_id' => $old->contract_id, 'total_due' => 7000]));
        $other = $this->invoice();
        DB::table('fn_confirm_settings')->where('title', 'automatic_journal_entries')->update(['value' => 'off']);
        $before = MusanedTransfer::count();
        foreach ([$old->id, $other->id] as $invoiceId) {
            try {
                app(MusanedTransferController::class)->createTransfer(new \Illuminate\Http\Request([
                    'transfer_date' => now()->toDateString(),
                    'items' => [['contract_id' => $old->contract_id, 'invoice_id' => $invoiceId, 'transferred_amount' => 6800]],
                ]), app(ConstraintService::class), app(YearService::class));
                $this->fail('An invalid invoice selection was accepted.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('items', $e->errors());
            }
        }
        $this->assertSame($before, MusanedTransfer::count());
    }

    public function test_partial_credit_keeps_invoice_eligible_until_total_credit_is_reached(): void
    {
        $invoice = $this->invoice();
        $controller = app(MusanedTransferController::class);
        foreach ([9999, 1] as $index => $amount) {
            DB::table('recruitment_invoice_credit_notes')->insert([
                'invoice_id' => $invoice->id, 'contract_id' => $invoice->contract_id,
                'issue_date' => now()->toDateString(), 'reason' => 'Credit', 'total' => $amount,
            ]);
            $this->assertSame($index === 0, $controller->getEligibleInvoicesQuery()->whereKey($invoice->id)->exists());
        }
    }

    public function test_commission_defaults_to_two_point_four_percent_of_gross_and_posts_only_once(): void
    {
        $invoice = $this->invoice();
        $controller = app(MusanedTransferController::class);
        $firstConstraint = Invoice::withoutEvents(fn () => $controller->recordInvoiceCommission($invoice));
        $secondConstraint = Invoice::withoutEvents(fn () => $controller->recordInvoiceCommission($invoice));
        $this->assertSame(2.4, (float) $invoice->fresh()->musaned_commission_rate);
        $this->assertSame(240.0, (float) $invoice->fresh()->musaned_commission_amount);
        $this->assertSame(31.3, (float) $invoice->fresh()->musaned_commission_tax);
        $this->assertCount(1, $this->journals);
        $this->assertNotNull($firstConstraint);
        $this->assertSame($firstConstraint->id, $secondConstraint?->id);
        $this->assertSame($firstConstraint->id, $invoice->fresh()->musanedCommissionConstraint?->id);
        $journal = $this->journals[0];
        $this->assertEquals(240, array_sum($journal['debtor_value']));
        $this->assertEquals(240, array_sum($journal['creditor_value']));
        $this->assertSame([$this->expense->id, $this->tax->id, $this->source->id], $journal['account_id']);
    }

    public function test_invoice_custom_commission_amount_and_rate_are_preserved(): void
    {
        $invoice = $this->invoice();
        Invoice::withoutEvents(fn () => $invoice->update([
            'musaned_commission_rate' => 2.35,
            'musaned_commission_amount' => 235,
        ]));

        Invoice::withoutEvents(fn () => app(MusanedTransferController::class)->recordInvoiceCommission($invoice));

        $fresh = $invoice->fresh();
        $this->assertSame(2.35, (float) $fresh->musaned_commission_rate);
        $this->assertSame(235.0, (float) $fresh->musaned_commission_amount);
        $this->assertSame(30.65, (float) $fresh->musaned_commission_tax);
        $this->assertStringContainsString('التكاليف البنكية لمساند', $this->journals[0]['statement']);
        $this->assertStringNotContainsString('%', $this->journals[0]['statement']);
        $this->assertEquals(235, array_sum($this->journals[0]['debtor_value']));
        $this->assertEquals(235, array_sum($this->journals[0]['creditor_value']));
    }

    public function test_invoice_line_and_musaned_edit_permissions_are_independent_and_control_overrides_both(): void
    {
        $admin = Admin::factory()->create();
        $lineItems = Permission::firstOrCreate([
            'name' => \App\PermKeys::INVOICES_EDIT_LINE_ITEMS,
            'guard_name' => 'admin',
        ]);
        $commission = Permission::firstOrCreate([
            'name' => \App\PermKeys::INVOICES_EDIT_MUSANED_COMMISSION,
            'guard_name' => 'admin',
        ]);
        $control = Permission::firstOrCreate([
            'name' => \App\PermKeys::INVOICES_CONTROL,
            'guard_name' => 'admin',
        ]);

        $this->actingAs($admin, 'admin');
        $admin->givePermissionTo($lineItems);
        $this->assertTrue(\App\Support\InvoiceAuthorization::canEditLineItems());
        $this->assertFalse(\App\Support\InvoiceAuthorization::canEditMusanedCommission());

        $admin->givePermissionTo($commission);
        $this->assertTrue(\App\Support\InvoiceAuthorization::canEditMusanedCommission());

        $admin->revokePermissionTo($lineItems);
        $admin->revokePermissionTo($commission);
        $admin->givePermissionTo($control);
        $this->assertTrue(\App\Support\InvoiceAuthorization::canEditLineItems());
        $this->assertTrue(\App\Support\InvoiceAuthorization::canEditMusanedCommission());
    }

    public function test_missing_workflow_setting_defaults_to_legacy_without_invoice_fee(): void
    {
        DB::table('fn_confirm_settings')->where('title', \App\Support\InvoiceSupplierWorkflow::SETTING_KEY)->delete();
        $this->assertFalse(\App\Support\InvoiceSupplierWorkflow::enabled());
        $invoice = $this->invoice();
        Invoice::withoutEvents(fn () => app(MusanedTransferController::class)->recordInvoiceCommission($invoice));
        $this->assertNull($invoice->fresh()->musaned_commission_amount);
        $this->assertNull($invoice->fresh()->musaned_commission_constraint_id);
        $this->assertCount(0, $this->journals);
        $this->test_legacy_invoices_still_post_commission_at_settlement();
    }

    public function test_switching_back_to_legacy_keeps_existing_invoice_fee_without_reposting(): void
    {
        $invoice = $this->invoice();
        Invoice::withoutEvents(fn () => app(MusanedTransferController::class)->recordInvoiceCommission($invoice));
        DB::table('fn_confirm_settings')->where('title', \App\Support\InvoiceSupplierWorkflow::SETTING_KEY)->update(['value' => 'legacy']);
        Invoice::withoutEvents(fn () => app(MusanedTransferController::class)->recordInvoiceCommission($invoice));
        $this->assertSame(240.0, (float) $invoice->fresh()->musaned_commission_amount);
        $item = (new MusanedTransferItem)->forceFill([
            'contract_id' => $invoice->contract_id, 'transferred_amount' => 9760,
            'commission_amount' => 240, 'expense_tax_amount' => 31.3, 'source_account_id' => $this->source->id,
        ])->setRelation('invoice', $invoice->fresh());
        $transfer = (new MusanedTransfer)->forceFill(['reference_number' => 'SWITCH', 'transfer_date' => now()]);
        $transfer->setRelation('items', collect([$item]));
        $method = new \ReflectionMethod(MusanedTransferController::class, 'storeSettlementConstraint');
        $method->setAccessible(true);
        $method->invoke(app(MusanedTransferController::class), $transfer, app(ConstraintService::class), (object) ['id' => 1], 999, $this->expense->id, $this->tax->id);
        $this->assertCount(2, $this->journals);
        $this->assertSame([999, $this->source->id], $this->journals[1]['account_id']);
        $freshInvoice = $this->invoice();
        Invoice::withoutEvents(fn () => app(MusanedTransferController::class)->recordInvoiceCommission($freshInvoice));
        $this->assertNull($freshInvoice->fresh()->musaned_commission_amount);
    }

    public function test_legacy_uses_office_cost_and_rejects_new_supplier_options(): void
    {
        DB::table('fn_confirm_settings')->where('title', \App\Support\InvoiceSupplierWorkflow::SETTING_KEY)->update(['value' => 'legacy']);
        $office = (new RecruitmentOffices)->forceFill(['cost_of_dollar' => 1200]);
        $maid = (new Maid)->forceFill(['supplier_cost_usd' => 900]);
        $maid->setRelation('office', $office)->setRelation('recruitmentOffice', $office)->setRelation('religion', null);
        $contract = (new Contract)->setRelation('maid', $maid)->setRelation('info', null)->setRelation('user', null)->setRelation('externalOfficeDispatch', null);
        $this->assertSame('1200.00', $contract->buildExternalOfficeDispatchSourceData()['office_contract_cost_usd']);
        $this->expectException(ValidationException::class);
        app(RecruitmentContractExternalOfficeDispatchController::class)->persistExternalOfficeDispatch($contract, now()->toDateString(), 'الرياض', '300', null, false, 'partial', 1000);
    }

    public function test_settlement_does_not_post_commission_or_tax_again(): void
    {
        $invoice = $this->invoice();
        $controller = app(MusanedTransferController::class);
        Invoice::withoutEvents(fn () => $controller->recordInvoiceCommission($invoice));
        $item = (new MusanedTransferItem)->forceFill([
            'contract_id' => $invoice->contract_id, 'transferred_amount' => 9760,
            'commission_amount' => 240, 'expense_tax_amount' => 31.3, 'source_account_id' => $this->source->id,
        ])->setRelation('invoice', $invoice->fresh());
        $transfer = (new MusanedTransfer)->forceFill(['reference_number' => 'TEST', 'transfer_date' => now()]);
        $transfer->setRelation('items', collect([$item]));
        $method = new \ReflectionMethod($controller, 'storeSettlementConstraint');
        $method->setAccessible(true);
        $method->invoke($controller, $transfer, app(ConstraintService::class), (object) ['id' => 1], 999, $this->expense->id, $this->tax->id);
        $journal = $this->journals[1];
        $this->assertSame([999, $this->source->id], $journal['account_id']);
        $this->assertEquals(9760, array_sum($journal['debtor_value']));
        $this->assertEquals(9760, array_sum($journal['creditor_value']));
    }

    public function test_disabled_automatic_entries_save_fee_without_posting(): void
    {
        DB::table('fn_confirm_settings')->where('title', 'automatic_journal_entries')->update(['value' => 'off']);
        $invoice = $this->invoice();
        Invoice::withoutEvents(fn () => app(MusanedTransferController::class)->recordInvoiceCommission($invoice));
        $this->assertSame(240.0, (float) $invoice->fresh()->musaned_commission_amount);
        $this->assertNull($invoice->fresh()->musaned_commission_constraint_id);
        $this->assertCount(0, $this->journals);
    }

    public function test_legacy_invoices_still_post_commission_at_settlement(): void
    {
        $invoice = $this->invoice();
        $item = (new MusanedTransferItem)->forceFill([
            'contract_id' => $invoice->contract_id, 'transferred_amount' => 9750,
            'commission_amount' => 250, 'expense_tax_amount' => 32.61, 'source_account_id' => $this->source->id,
        ])->setRelation('invoice', $invoice);
        $transfer = (new MusanedTransfer)->forceFill(['reference_number' => 'LEGACY', 'transfer_date' => now()]);
        $transfer->setRelation('items', collect([$item]));
        $method = new \ReflectionMethod(MusanedTransferController::class, 'storeSettlementConstraint');
        $method->setAccessible(true);
        $method->invoke(app(MusanedTransferController::class), $transfer, app(ConstraintService::class), (object) ['id' => 1], 999, $this->expense->id, $this->tax->id);
        $journal = $this->journals[0];
        $this->assertContains($this->expense->id, $journal['account_id']);
        $this->assertContains($this->tax->id, $journal['account_id']);
        $this->assertEquals(array_sum($journal['debtor_value']), array_sum($journal['creditor_value']));
        $this->assertNull($invoice->fresh()->musaned_commission_amount);
    }

    public function test_cv_cost_overrides_supplier_default_and_blank_inherits(): void
    {
        $office = (new RecruitmentOffices)->forceFill(['cost_of_dollar' => 1200]);
        $maid = (new Maid)->forceFill(['supplier_cost_usd' => 900]);
        $maid->setRelation('office', $office)->setRelation('recruitmentOffice', $office)->setRelation('religion', null);
        $contract = (new Contract)->setRelation('maid', $maid)->setRelation('info', null)->setRelation('user', null);
        $this->assertSame('900.00', $contract->buildExternalOfficeDispatchSourceData()['office_contract_cost_usd']);
        $this->assertSame('800.00', $contract->buildExternalOfficeDispatchSourceData(800)['office_contract_cost_usd']);
        $maid->supplier_cost_usd = null;
        $this->assertSame('1200.00', $contract->buildExternalOfficeDispatchSourceData()['office_contract_cost_usd']);
    }

    public function test_partial_cost_cannot_equal_or_exceed_total(): void
    {
        $this->expectException(ValidationException::class);
        app(RecruitmentContractExternalOfficeDispatchController::class)->persistExternalOfficeDispatch(
            new Contract, now()->toDateString(), '', '1200', null, false, 'partial', 1000
        );
    }

    /** @dataProvider supplierAmounts */
    public function test_supplier_invoice_saves_only_selected_cost(string $mode, string $amount, string $expected): void
    {
        $contract = Contract::withoutEvents(fn () => Contract::query()->create(['status' => 'new']));
        $service = Mockery::mock(\App\Services\ingaz\RecruitmentTripAccountingService::class);
        $service->shouldReceive('recordDispatchCostEntries')->once()->with(Mockery::type(Contract::class), true)->andReturn([]);
        $this->app->instance(\App\Services\ingaz\RecruitmentTripAccountingService::class, $service);
        app(RecruitmentContractExternalOfficeDispatchController::class)->persistExternalOfficeDispatch(
            $contract, now()->toDateString(), 'الرياض', $amount, null, false, $mode, 1000
        );
        $dispatch = $contract->externalOfficeDispatch()->firstOrFail();
        $this->assertSame($expected, (string) $dispatch->office_contract_cost_usd);
        $this->assertSame(1000.0, (float) $dispatch->supplier_total_cost_usd);
        $this->assertSame($mode, $dispatch->supplier_recognition_mode);
    }

    public function supplierAmounts(): array
    {
        return [['full', '300', '1000.00'], ['partial', '300', '300.00']];
    }
}
