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
