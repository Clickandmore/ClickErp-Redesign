<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ContractReturnRequest;
use App\Models\ContractReturnSettlement;
use App\Models\ReturnReasonCode;
use App\Models\auth\Admin;
use App\Models\commissions\CommissionBalanceTransaction;
use App\Models\commissions\CommissionBatch;
use App\Models\commissions\CommissionBatchItem;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\User;
use App\Http\Controllers\ingaz\PostArrivalRefundController;
use App\Services\ingaz\PostArrivalRefundService;
use App\Services\ingaz\RecruitmentContractWarrantyReturnService;
use App\Services\commissions\CommissionOperationService;
use App\Support\AdminRoutePermissionMatrix;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\FnYear;
use Tests\TestCase;

class PostArrivalRefundTest extends TestCase
{
    use DatabaseTransactions;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
        $this->actingAs($this->admin, 'admin');
        if (! FnYear::query()->whereDate('from_date', '<=', now())->whereDate('to_date', '>=', now())->where('status', 1)->exists()) {
            FnYear::query()->create(['title' => 'اختبار ' . now()->year, 'from_date' => now()->startOfYear()->toDateString(), 'to_date' => now()->endOfYear()->toDateString(), 'status' => 1, 'created_by_id' => $this->admin->id]);
        }
        DB::table('fn_constraint_code_sequences')->updateOrInsert(['id' => 1], ['last_code' => (int) Constraint::query()->max('code'), 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_draft_creation_writes_no_financial_document(): void
    {
        $contract = $this->contractWithArrivalInvoice();
        $reason = ReturnReasonCode::query()->firstOrCreate(['code' => 'test'], ['name' => 'اختبار']);
        $beforeConstraints = Constraint::query()->count();
        $beforeNotes = \App\Models\RecruitmentInvoiceCreditNote::query()->count();

        $settlement = app(PostArrivalRefundService::class)->createDraft($contract, [
            'settlement_date' => now()->toDateString(),
            'return_reason_code_id' => $reason->id,
            'reason' => 'اختبار إنشاء المسودة',
            'lines' => [[
                'enabled' => true,
                'type' => 'credit_note',
                'approved_amount' => 3980.75,
            ]],
        ]);

        $this->assertSame('draft', $settlement->status);
        $this->assertSame(1, $settlement->lines()->count());
        $this->assertSame($beforeConstraints, Constraint::query()->count());
        $this->assertSame($beforeNotes, \App\Models\RecruitmentInvoiceCreditNote::query()->count());
    }

    public function test_manual_mode_skips_legacy_warranty_return_posting(): void
    {
        $contract = new Contract(['id' => 991]);
        $request = new ContractReturnRequest(['id' => 992]);

        $this->assertNull(app(RecruitmentContractWarrantyReturnService::class)->record($contract, $request));
    }

    public function test_posting_uses_selected_company_accounts_and_snapshots_them(): void
    {
        $contract = $this->contractWithArrivalInvoice();
        $clientAccount = $this->account('عميل الاسترداد', 'debtor');
        $incomeAccount = $this->account('إيراد خصم الاسترداد', 'creditor');
        $contract->user->update(['account_id' => $clientAccount->id]);
        $reason = ReturnReasonCode::query()->firstOrCreate(['code' => 'test-post'], ['name' => 'اختبار نشر']);
        $proposal = app(PostArrivalRefundService::class)->proposal($contract->fresh(['invoice', 'info', 'user']), now()->toDateString());
        $before = Constraint::query()->count();

        $settlement = app(PostArrivalRefundService::class)->createDraft($contract, [
            'settlement_date' => now()->toDateString(), 'return_reason_code_id' => $reason->id, 'reason' => 'اختبار الحسابات',
            'lines' => [[
                'enabled' => true, 'type' => 'client_deduction',
                'approved_amount' => $proposal['client_deduction'], 'credit_account_id' => $incomeAccount->id,
            ]],
        ]);
        $posted = app(PostArrivalRefundService::class)->post($settlement);
        $line = $posted->lines->first();

        $this->assertSame('posted', $posted->status);
        $this->assertSame('posted', $line->status);
        $this->assertSame($before + 1, Constraint::query()->count());
        $this->assertSame($clientAccount->id, (int) $line->debit_account_id);
        $this->assertSame($incomeAccount->id, (int) $line->credit_account_id);
        $this->assertCount(2, $line->account_snapshot);
    }

    public function test_new_routes_have_dedicated_permissions(): void
    {
        $this->assertSame([PermKeys::POST_ARRIVAL_REFUND_VIEW], AdminRoutePermissionMatrix::resolve('post-arrival-refunds.index', Request::create('/')));
        $this->assertSame([PermKeys::POST_ARRIVAL_REFUND_POST], AdminRoutePermissionMatrix::resolve('post-arrival-refunds.post', Request::create('/')));
        $this->assertSame([PermKeys::POST_ARRIVAL_REFUND_PAYOUT], AdminRoutePermissionMatrix::resolve('post-arrival-refunds.payout', Request::create('/')));
        $this->assertSame([PermKeys::POST_ARRIVAL_REFUND_REVERSE], AdminRoutePermissionMatrix::resolve('post-arrival-refunds.lines.reverse', Request::create('/')));
    }

    public function test_create_screen_accepts_selected_contract_on_laravel_eight(): void
    {
        $contract = $this->contractWithArrivalInvoice();
        $request = Request::create('/admin/post-arrival-refunds/create', 'GET', ['contract_id' => $contract->id]);

        $response = app(PostArrivalRefundController::class)->create($request, app(PostArrivalRefundService::class));

        $this->assertSame('admin.ingaz.post_arrival_refunds.create', $response->name());
        $this->assertSame($contract->id, $response->getData()['selected']->id);
    }

    public function test_arrived_contract_can_prepare_draft_but_cannot_post_before_return_approval(): void
    {
        $contract = $this->contractWithArrivalInvoice();
        $contract->update(['status' => 'arrival']);
        $reason = ReturnReasonCode::query()->firstOrCreate(['code' => 'arrival-draft'], ['name' => 'اختبار مسودة الوصول']);

        $settlement = app(PostArrivalRefundService::class)->createDraft($contract->fresh(), [
            'settlement_date' => now()->toDateString(),
            'return_reason_code_id' => $reason->id,
            'reason' => 'تجهيز المسودة قبل اعتماد الإرجاع',
            'lines' => [[
                'enabled' => true,
                'type' => 'credit_note',
                'approved_amount' => (float) $contract->invoice->total_due,
            ]],
        ]);

        $this->assertSame('draft', $settlement->status);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(PostArrivalRefundService::class)->post($settlement);
    }

    public function test_shelter_refund_posts_approved_marketer_commission_reversal(): void
    {
        $contract = $this->contractWithArrivalInvoice();
        $contract->update(['status' => 'shelter']);
        $batch = CommissionBatch::query()->create([
            'marketer_id' => $this->admin->id,
            'total_requests' => 1,
            'total_base_amount' => 3980.75,
            'total_commission' => 100,
            'status' => CommissionBatch::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
        $item = CommissionBatchItem::query()->create([
            'batch_id' => $batch->id,
            'source_type' => CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT,
            'source_id' => $contract->id,
            'contract_id' => $contract->id,
            'service_type' => 'recruitment',
            'base_amount' => 3980.75,
            'method' => 'fixed',
            'rate_or_amount' => 100,
            'commission_amount' => 100,
        ]);
        CommissionBalanceTransaction::query()->create([
            'marketer_id' => $this->admin->id,
            'type' => CommissionBalanceTransaction::TYPE_CREDIT,
            'amount' => 100,
            'reference_type' => 'commission_batch',
            'reference_id' => $batch->id,
            'balance_after' => 100,
        ]);
        $reason = ReturnReasonCode::query()->firstOrCreate(
            ['code' => 'shelter-commission-refund'],
            ['name' => 'اختبار عمولة الإيواء']
        );
        $settlement = app(PostArrivalRefundService::class)->createDraft($contract->fresh(), [
            'settlement_date' => now()->toDateString(),
            'return_reason_code_id' => $reason->id,
            'reason' => 'اختبار استرداد عمولة عقد داخل الإيواء',
            'lines' => [[
                'enabled' => true,
                'type' => 'marketer_commission',
                'approved_amount' => 100,
            ]],
        ]);

        $posted = app(PostArrivalRefundService::class)->post($settlement);

        $this->assertSame('posted', $posted->status);
        $this->assertSame('posted', $posted->lines->first()->status);
        $this->assertNotNull($item->fresh()->commission_reversal_transaction_id);
    }

    public function test_commission_already_reversed_from_commissions_screen_is_linked_not_reversed_twice(): void
    {
        $contract = $this->contractWithArrivalInvoice();
        $contract->update(['status' => 'back']);
        $batch = CommissionBatch::query()->create([
            'marketer_id' => $this->admin->id,
            'total_requests' => 1,
            'total_base_amount' => 3980.75,
            'total_commission' => 100,
            'status' => CommissionBatch::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
        $item = CommissionBatchItem::query()->create([
            'batch_id' => $batch->id,
            'source_type' => CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT,
            'source_id' => $contract->id,
            'contract_id' => $contract->id,
            'service_type' => 'recruitment',
            'base_amount' => 3980.75,
            'method' => 'fixed',
            'rate_or_amount' => 100,
            'commission_amount' => 100,
        ]);
        $reversal = CommissionBalanceTransaction::query()->create([
            'marketer_id' => $this->admin->id,
            'type' => CommissionBalanceTransaction::TYPE_DEBIT,
            'amount' => 100,
            'reference_type' => 'commission_reversal',
            'reference_id' => $item->id,
            'balance_after' => 0,
        ]);
        $item->update(['commission_reversal_transaction_id' => $reversal->id, 'commission_reversed_at' => now()]);
        $reason = ReturnReasonCode::query()->firstOrCreate(
            ['code' => 'shelter-commission-refund'],
            ['name' => 'اختبار عمولة الإيواء']
        );
        $settlement = app(PostArrivalRefundService::class)->createDraft($contract->fresh(), [
            'settlement_date' => now()->toDateString(),
            'return_reason_code_id' => $reason->id,
            'reason' => 'اختبار عمولة معكوسة مسبقاً',
            'lines' => [[
                'enabled' => true,
                'type' => 'marketer_commission',
                'approved_amount' => 100,
                'reason' => 'سبق عكسها من شاشة العمولات',
            ]],
        ]);

        $posted = app(PostArrivalRefundService::class)->post($settlement);

        $line = $posted->lines->first();
        $this->assertSame('posted', $line->status);
        $this->assertSame($reversal->id, $line->metadata['commission_reversal_transaction_id']);
        $this->assertTrue($line->metadata['already_reversed']);
        $this->assertSame(1, CommissionBalanceTransaction::query()
            ->where('reference_type', 'commission_reversal')->where('reference_id', $item->id)->count());
    }

    public function test_draft_requires_leaf_credit_account_for_client_deduction(): void
    {
        $contract = $this->contractWithArrivalInvoice();
        $reason = ReturnReasonCode::query()->firstOrCreate(['code' => 'missing-credit'], ['name' => 'اختبار الحساب الدائن']);

        try {
            app(PostArrivalRefundService::class)->createDraft($contract, [
                'settlement_date' => now()->toDateString(),
                'return_reason_code_id' => $reason->id,
                'reason' => 'اختبار منع الحساب المفقود',
                'lines' => [[
                    'enabled' => true,
                    'type' => 'client_deduction',
                    'approved_amount' => 100,
                    'credit_account_id' => null,
                ]],
            ]);
            $this->fail('كان يجب رفض المسودة التي لا تملك حساباً دائناً طرفياً.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertSame(
                'اختر حساباً دائناً طرفياً لبند خصم العميل قبل حفظ المسودة.',
                $exception->errors()['lines.0.credit_account_id'][0]
            );
        }
    }

    public function test_account_search_box_value_resolves_to_the_selected_leaf_account(): void
    {
        $contract = $this->contractWithArrivalInvoice();
        $reason = ReturnReasonCode::query()->firstOrCreate(['code' => 'account-box'], ['name' => 'اختبار مربع الحساب']);
        $creditAccount = $this->account('شركة ولاء لاختبار البحث', 'creditor');
        $proposal = app(PostArrivalRefundService::class)->proposal($contract, now()->toDateString());
        $request = Request::create('/admin/post-arrival-refunds', 'POST', [
            'contract_id' => $contract->id,
            'return_reason_code_id' => $reason->id,
            'settlement_date' => now()->toDateString(),
            'reason' => 'اختبار اختيار الحساب من مربع البحث',
            'lines' => [[
                'enabled' => true,
                'type' => 'client_deduction',
                'approved_amount' => $proposal['client_deduction'],
                'credit_account_query' => $creditAccount->code . ' — ' . $creditAccount->name . ' [#' . $creditAccount->id . ']',
            ]],
        ]);

        app(PostArrivalRefundController::class)->store($request, app(PostArrivalRefundService::class));

        $settlement = ContractReturnSettlement::query()->where('contract_id', $contract->id)->latest('id')->firstOrFail();
        $this->assertSame($creditAccount->id, (int) $settlement->lines()->firstOrFail()->credit_account_id);
    }

    public function test_draft_update_preserves_identity_and_creates_no_financial_documents(): void
    {
        $service = app(PostArrivalRefundService::class);
        $contract = $this->contractWithArrivalInvoice();
        $reason = ReturnReasonCode::query()->firstOrCreate(['code' => 'test-edit'], ['name' => 'اختبار تعديل']);
        $data = ['settlement_date' => now()->toDateString(), 'return_reason_code_id' => $reason->id,
            'reason' => 'المسودة الأصلية', 'lines' => [['enabled' => true, 'type' => 'credit_note', 'approved_amount' => 3980.75]]];
        $draft = $service->createDraft($contract, $data);
        $beforeConstraints = Constraint::query()->count();
        $beforeNotes = \App\Models\RecruitmentInvoiceCreditNote::query()->count();
        $data['reason'] = 'المسودة المعدلة';
        $data['lines'][0]['approved_amount'] = 3000;
        $data['lines'][0]['reason'] = 'تعديل المبلغ بطلب العميل';
        $updated = $service->updateDraft($draft, $data);
        $this->assertSame($draft->id, $updated->id);
        $this->assertSame($draft->created_by_id, $updated->created_by_id);
        $this->assertSame('المسودة المعدلة', $updated->reason);
        $this->assertSame(3000.0, (float) $updated->approved_total);
        $this->assertSame(1, $updated->lines()->count());
        $this->assertSame($beforeConstraints, Constraint::query()->count());
        $this->assertSame($beforeNotes, \App\Models\RecruitmentInvoiceCreditNote::query()->count());

        $view = app(PostArrivalRefundController::class)->edit(Request::create('/'), $updated, $service);
        $view->with('errors', new \Illuminate\Support\ViewErrorBag());
        $html = $view->render();
        $this->assertStringContainsString('value="3000.00"', $html);
        $this->assertStringContainsString('المسودة المعدلة', $html);
    }

    public function test_invalid_draft_update_rolls_back_and_posted_document_cannot_be_edited(): void
    {
        $service = app(PostArrivalRefundService::class);
        $reason = ReturnReasonCode::query()->firstOrCreate(['code' => 'test-edit-invalid'], ['name' => 'اختبار']);
        $data = ['settlement_date' => now()->toDateString(), 'return_reason_code_id' => $reason->id,
            'reason' => 'المسودة الأصلية', 'lines' => [['enabled' => true, 'type' => 'credit_note', 'approved_amount' => 3980.75]]];
        $draft = $service->createDraft($this->contractWithArrivalInvoice(), $data);
        $originalLine = $draft->lines->first()->id;
        try {
            $service->updateDraft($draft, array_replace($data, ['reason' => 'لا يحفظ', 'lines' => []]));
            $this->fail('Empty draft must be rejected.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('lines', $exception->errors());
        }
        $this->assertSame('المسودة الأصلية', $draft->fresh()->reason);
        $this->assertSame($originalLine, $draft->lines()->first()->id);
        $draft->update(['status' => 'posted']);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->updateDraft($draft, $data);
    }

    public function test_edit_routes_require_the_separate_edit_permission(): void
    {
        foreach (['post-arrival-refunds.edit', 'post-arrival-refunds.update'] as $route) {
            $this->assertSame([PermKeys::POST_ARRIVAL_REFUND_EDIT], AdminRoutePermissionMatrix::resolve($route, Request::create('/')));
        }
    }

    public function test_excess_credit_note_returns_clear_validation_and_keeps_the_draft_without_financial_documents(): void
    {
        $service = app(PostArrivalRefundService::class);
        $reason = ReturnReasonCode::query()->firstOrCreate(['code' => 'test-credit-mismatch'], ['name' => 'اختبار']);
        $draft = $service->createDraft($this->contractWithArrivalInvoice(), [
            'settlement_date' => now()->toDateString(),
            'return_reason_code_id' => $reason->id,
            'reason' => 'اختبار اختلاف المبلغ',
            'lines' => [['enabled' => true, 'type' => 'credit_note', 'approved_amount' => 3979.75, 'reason' => 'اختبار تخفيض ريال واحد']],
        ]);
        $beforeConstraints = Constraint::query()->count();
        $beforeNotes = \App\Models\RecruitmentInvoiceCreditNote::query()->count();
        $draft->lines()->first()->update(['approved_amount' => 3981.75]);
        try {
            $service->post($draft);
            $this->fail('A mismatched credit note must not be posted.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $message = $exception->errors()['credit_note'][0];
            $this->assertStringContainsString('3,981.75 ريال', $message);
            $this->assertStringContainsString('3,980.75 ريال', $message);
            $this->assertStringContainsString('ألا يتجاوز', $message);
        }
        $this->assertSame('draft', $draft->fresh()->status);
        $this->assertSame('draft', $draft->lines()->first()->status);
        $this->assertSame($beforeConstraints, Constraint::query()->count());
        $this->assertSame($beforeNotes, \App\Models\RecruitmentInvoiceCreditNote::query()->count());
    }

    public function test_departure_with_zero_shelter_balance_uses_cost_returns_without_reducing_shelter_again(): void
    {
        $contract = $this->contractWithArrivalInvoice();
        $nationality = \App\Models\ingaz\Nationality::query()->create(['ar' => ['title' => 'اختبار الاسترداد', 'sub_title' => 'اختبار الاسترداد']]);
        $maid = \App\Models\ingaz\Maid::query()->create(['name' => 'عاملة مسافرة', 'passport_number' => uniqid('refund'), 'nationality_id' => $nationality->id]);
        $contract->update(['maid_id' => $maid->id]);
        $asset = $this->account('أصل الإيواء للاختبار', 'debtor');
        $returns = $this->account('مردودات تكلفة للاختبار', 'creditor');
        $office = $this->account('مكتب الاختبار', 'creditor');
        \Nami\Finance\Models\ConfirmSetting::query()->create(['title' => 'nationality_account_' . $nationality->id . '_recruitment_cost_return', 'value' => $returns->id]);
        $receipt = \App\Models\ShelterCostEntry::query()->create([
            'contract_id' => $contract->id, 'maid_id' => $maid->id,
            'kind' => \App\Models\ShelterCostEntry::KIND_CAPITALIZE_OFFICE_COST,
            'amount' => 3375, 'asset_account_id' => $asset->id,
        ]);
        $summary = ['lots' => [$receipt->id => ['remaining' => 3375.0]], 'issues' => [], 'recovered' => 0.0];
        $this->mock(\App\Services\ingaz\ShelterAgentCostRecoveryService::class, function ($mock) use (&$summary) {
            $mock->shouldReceive('summary')->andReturnUsing(function () use (&$summary) { return $summary; });
        });
        $service = app(PostArrivalRefundService::class);
        $proposal = $service->proposal($contract->fresh(), now()->toDateString());
        $this->assertSame($asset->id, $proposal['office_charge_credit_account_id']);
        \App\Models\ShelterCostEntry::query()->create([
            'contract_id' => $contract->id, 'maid_id' => $maid->id,
            'kind' => \App\Models\ShelterCostEntry::KIND_RELEASE_TO_DEPORTATION, 'amount' => -3375,
        ]);
        $summary['lots'][$receipt->id]['remaining'] = 0.0;
        $proposal = $service->proposal($contract->fresh(), now()->toDateString());
        $this->assertSame($returns->id, $proposal['office_charge_credit_account_id']);
        $this->assertNull($proposal['office_charge_receipt_entry_id']);
        $this->assertSame(0.0, $proposal['shelter_cost']);
        $settlement = ContractReturnSettlement::query()->create([
            'contract_id' => $contract->id, 'invoice_id' => $contract->invoice->id,
            'settlement_date' => now()->toDateString(), 'status' => 'draft', 'reason' => 'اختبار السفر',
        ]);
        $line = $settlement->lines()->create([
            'type' => 'office_charge', 'status' => 'draft', 'approved_amount' => 3375,
            'credit_account_id' => $returns->id,
            'metadata' => ['shelter_receipt_entry_id' => $receipt->id],
        ]);
        $method = new \ReflectionMethod($service, 'postLine');
        $method->setAccessible(true);
        // Supply the original office source independently of the shelter receipt.
        $supplier = Constraint::query()->create(['code' => random_int(1000000, 9999999), 'type' => 1, 'date' => now()->toDateString(), 'statement' => 'اختبار المورد', 'value' => 3375]);
        \Nami\Finance\Models\ConstraintDetails::query()->create(['constraint_id' => $supplier->id, 'account_id' => $office->id, 'creditor_value' => 3375, 'debtor_value' => 0]);
        $contract->invoice->update(['external_office_cost_constraint_id' => $supplier->id]);
        $receipt->update(['supplier_constraint_id' => $supplier->id]);
        $settlement->setRelation('contract', $contract->fresh(['invoice', 'maid']));
        $before = \App\Models\ShelterCostEntry::query()->count();
        $line->update(['credit_account_id' => $asset->id]);
        try {
            $method->invoke($service, $settlement, $line);
            $this->fail('A cleared shelter asset must not be credited again.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertStringContainsString('رصيد الإيواء تمت تصفيته', $exception->errors()['office_charge'][0]);
            $this->assertStringContainsString('تعديل المسودة', $exception->errors()['office_charge'][0]);
        }
        $line->update(['credit_account_id' => $returns->id, 'metadata' => ['shelter_receipt_entry_id' => $receipt->id]]);
        $method->invoke($service, $settlement, $line);
        $this->assertSame('posted', $line->fresh()->status);
        $this->assertNull($line->fresh()->metadata['shelter_receipt_entry_id']);
        $this->assertSame($before, \App\Models\ShelterCostEntry::query()->count());
        $summary['recovered'] = 3375.0;
        $this->assertSame($asset->id, $service->proposal($contract->fresh(), now()->toDateString())['office_charge_credit_account_id']);
        $summary['recovered'] = 0.0;
        $summary['issues'] = ['اختلاف في الرصيد'];
        $this->assertSame($asset->id, $service->proposal($contract->fresh(), now()->toDateString())['office_charge_credit_account_id']);
    }

    public function test_imported_supplier_closure_does_not_replace_customer_revenue_invoice(): void
    {
        $contract = $this->contractWithArrivalInvoice();
        $revenue = $contract->invoice;
        $revenue->update(['legacy_invoice_number' => 7581, 'legacy_invoice_type' => 'فاتورة ايراد / طلب استقدام']);
        $closure = Invoice::query()->create([
            'contract_id' => $contract->id, 'legacy_invoice_number' => 7892,
            'legacy_invoice_type' => 'إغلاق', 'subtotal' => 3375, 'vat_amount' => 0,
            'total_due' => 3375, 'is_paid' => true, 'send_to_zatca' => false,
        ]);

        $this->assertSame($revenue->id, $contract->fresh()->invoice->id);
        $this->assertSame($revenue->id, Contract::with('invoice')->findOrFail($contract->id)->invoice->id);
        $this->assertSame(2, $contract->invoices()->count());
        $this->assertSame([$revenue->id], $contract->blockingInvoices()->pluck('id')->all());
        $proposal = app(PostArrivalRefundService::class)->proposal($contract->fresh(), now()->toDateString());
        $this->assertSame(3980.75, $proposal['invoice_total']);
        $this->assertSame('not_recruitment_revenue', app(\App\Services\ingaz\LegacyInvoiceConstraintLinker::class)->inspect($closure)['status']);

        $draft = \App\Models\ContractReturnSettlement::query()->create([
            'contract_id' => $contract->id, 'invoice_id' => $closure->id,
            'settlement_date' => now()->toDateString(), 'status' => 'draft', 'reason' => 'Legacy closure draft',
        ]);
        try {
            app(PostArrivalRefundService::class)->post($draft);
            $this->fail('A draft based on supplier cost must be reviewed before posting.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('invoice_id', $exception->errors());
        }
        $this->assertSame('draft', $draft->fresh()->status);
    }

    public function test_supplier_closure_alone_is_not_a_customer_invoice(): void
    {
        $contract = $this->contractWithArrivalInvoice();
        $contract->invoice->update(['legacy_invoice_number' => 7892, 'legacy_invoice_type' => 'إغلاق']);
        $this->assertNull($contract->fresh()->invoice);
        $this->assertFalse($contract->blockingInvoices()->exists());
        $this->assertNull(Contract::with('invoice')->findOrFail($contract->id)->invoice);
    }

    private function contractWithArrivalInvoice(): Contract
    {
        $user = User::query()->create([
            'name' => 'عميل اختبار الاسترداد',
            'email' => 'refund-' . uniqid() . '@example.test',
            'phone' => '05' . random_int(10000000, 99999999),
            'password' => bcrypt('secret'),
        ]);
        $contract = Contract::query()->create([
            'status' => 'back', 'type' => 'recruitment', 'user_id' => $user->id, 'marketer_id' => $this->admin->id,
        ]);
        ContractInfo::query()->create(['contract_id' => $contract->id, 'arrival_date' => now()->subDays(30)->toDateString()]);
        Invoice::query()->create([
            'contract_id' => $contract->id, 'subtotal' => 3938, 'vat_amount' => 42.75, 'total_due' => 3980.75,
            'discount' => 0, 'is_paid' => true, 'send_to_zatca' => false,
        ]);
        return $contract->fresh(['invoice', 'info', 'user']);
    }

    private function account(string $name, string $status): Account
    {
        return Account::query()->create([
            'name' => $name, 'code' => ((int) DB::table('fn_accounts')->max('code')) + random_int(10, 99),
            'parent_id' => null, 'level' => 1, 'operation_type' => 1, 'status' => $status,
            'statement_type' => 'income_statement', 'type' => 1, 'created_by_id' => $this->admin->id,
        ]);
    }
}
