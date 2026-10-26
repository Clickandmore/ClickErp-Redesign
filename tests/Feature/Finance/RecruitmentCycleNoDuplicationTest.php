<?php

namespace Tests\Feature\Finance;

use App\Enums\ingaz\ContractStatusEnum;
use App\Http\Controllers\ingaz\MusanedTransferController;
use App\Http\Controllers\ingaz\RecruitmentContractExternalOfficeDispatchController;
use App\Models\Contract;
use App\Models\MusanedTransferItem;
use App\Models\auth\Admin;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\User as ClientUser;
use App\Services\ingaz\RecruitmentContractRefundService;
use App\Services\ingaz\RecruitmentTripAccountingService;
use App\Support\InvoiceSupplierWorkflow;
use App\Support\MusanedCommissionPosting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\BondTypes;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Bond;
use Nami\Finance\Models\ConfirmSetting;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\FnYear;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;
use Tests\TestCase;

/**
 * الدورة كاملة بالتسلسل الجديد: الفاتورة ← التكاليف البنكية ← فاتورة المورد ← الوصول ←
 * تحويل مساند أو الاسترداد. كل مرحلة تُشغَّل مرتين، والمطلوب أن يُرحَّل كل شيء مرة واحدة
 * وأن تعود حسابات الوسيط إلى صفر في نهاية الدورة.
 */
class RecruitmentCycleNoDuplicationTest extends TestCase
{
    use DatabaseTransactions;

    private const TOTAL = 3496.10;
    private const COMMISSION = 83.91;
    private const COMMISSION_TAX = 10.94;
    private const NET = 3412.19;
    private const SUPPLIER_USD = 800.0;
    private const SUPPLIER_SAR = 3000.0;

    private Admin $admin;
    private array $acc = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);

        $this->admin = Admin::factory()->create();
        $this->actingAs($this->admin, 'admin');
        $this->ensureActiveFiscalYear();
        $this->ensureConstraintNumberSequence();

        $this->acc['musaned'] = $this->account('بنك مساند الدورة', 'debtor', AccountTypesEnum::Bank->value);
        $this->acc['intermediate'] = $this->account('مساند وسيط الدورة', 'debtor', AccountTypesEnum::Bank->value);
        $this->acc['bank'] = $this->account('بنك الرياض الدورة', 'debtor', AccountTypesEnum::Bank->value);
        $this->acc['commission'] = $this->account('عمولات مساند الدورة', 'debtor');
        $this->acc['expense_tax'] = $this->account('ضريبة مصروفات الدورة', 'debtor');
        $this->acc['vat'] = $this->account('ضريبة مخرجات الدورة', 'creditor');

        ConfirmSetting::query()->where('title', 'tax_rate_expenditure')->delete();
        foreach ([
            'automatic_journal_entries' => 'on',
            InvoiceSupplierWorkflow::SETTING_KEY => InvoiceSupplierWorkflow::UPDATED,
            \App\Support\RecruitmentRevenueRecognition::SETTING_KEY => 'on_invoice',
            \App\Support\RecruitmentSupplierCostRecognition::SETTING_KEY => 'on_arrival',
            \App\Support\RecruitmentSupplierSettings::MUSANED_COLLECTION_MODE => 'intermediate_two_step',
            MusanedCommissionPosting::SETTING_KEY => MusanedCommissionPosting::SEPARATE,
            'musaned_account' => $this->acc['musaned']->id,
            'musaned_intermediate_account' => $this->acc['intermediate']->id,
            'musaned_commission_account' => $this->acc['commission']->id,
            'expense_tax_account' => $this->acc['expense_tax']->id,
            'revenue_tax_account' => $this->acc['vat']->id,
        ] as $title => $value) {
            $this->setting($title, (string) $value);
        }

        if (!Account::query()->where('code', 221)->exists()) {
            $this->account('الوكلاء الخارجيين', 'creditor')->update(['code' => 221]);
        }
    }

    public function test_separate_mode_full_cycle_with_musaned_transfer_posts_everything_once(): void
    {
        [$contract, $invoice] = $this->issueInvoice('اختبار الدورة التحويل');

        $this->postCommissionTwice($invoice);
        $this->createSupplierInvoiceTwice($contract);
        $this->registerArrivalTwice($contract);

        $controller = app(MusanedTransferController::class);
        $this->storeTransfer($controller, $contract, self::NET, true);
        $this->storeTransfer($controller, $contract, self::NET);

        $this->assertSame(1, MusanedTransferItem::query()->where('invoice_id', $invoice->id)->count(), 'التحويل لا يتكرر للفاتورة');

        $item = MusanedTransferItem::query()->where('invoice_id', $invoice->id)->firstOrFail();
        $settlementCommission = DB::table('fn_constraint_details')
            ->where('constraint_id', $item->transfer->settlement_constraint_id)
            ->where('account_id', $this->acc['commission']->id)
            ->count();
        $this->assertSame(0, $settlementCommission, 'قيد التسوية لا يعيد العمولة المسجلة عند الإصدار');

        // نهاية الدورة: مساند والوسيط صفر، والعمولة وضريبتها والتكلفة مرة واحدة.
        $this->assertBalance('musaned', 0.0);
        $this->assertBalance('intermediate', 0.0);
        $this->assertBalance('bank', self::NET);
        $this->assertBalance('commission', self::COMMISSION - self::COMMISSION_TAX);
        $this->assertBalance('expense_tax', self::COMMISSION_TAX);
        $this->assertBalance('office', -self::SUPPLIER_SAR);
        $this->assertBalance('cost', self::SUPPLIER_SAR);
    }

    public function test_separate_mode_refund_before_transfer_reverses_commission_and_supplier_once(): void
    {
        [$contract, $invoice] = $this->issueInvoice('اختبار الدورة الاسترداد');

        $this->postCommissionTwice($invoice);
        $this->createSupplierInvoiceTwice($contract);

        $refunds = app(RecruitmentContractRefundService::class);
        $refunds->refund($contract->fresh(), ['refund_date' => now()->toDateString(), 'reason' => 'اختبار']);

        try {
            $refunds->refund($contract->fresh(), ['refund_date' => now()->toDateString(), 'reason' => 'اختبار ثانٍ']);
            $this->fail('الاسترداد الثاني يجب أن يُرفض.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('استرداد سابق', $exception->getMessage());
        }

        $fresh = $invoice->fresh();
        $this->assertNotNull($fresh->musaned_commission_reversal_constraint_id);
        $this->assertNotNull($fresh->external_office_cost_reversal_constraint_id);

        // العمولة وضريبتها وتكلفة المورد تعود إلى صفر، ويعود لمساند كامل المبلغ ليُرد للعميل.
        $this->assertBalance('commission', 0.0);
        $this->assertBalance('expense_tax', 0.0);
        $this->assertBalance('office', 0.0);
        $this->assertBalance('cost', 0.0);
        $this->assertBalance('musaned', self::TOTAL);
        $this->assertSame(1, $this->countByStatement(RecruitmentContractRefundService::musanedCommissionReversalStatement($contract)));
    }

    public function test_two_entry_mode_full_cycle_and_refund_post_everything_once(): void
    {
        $this->setting(MusanedCommissionPosting::SETTING_KEY, MusanedCommissionPosting::WITH_RECEIPT);

        // دورة بتحويل.
        [$contract, $invoice] = $this->issueInvoice('اختبار قيدان التحويل');
        $constraintsBefore = Constraint::query()->count();
        $this->postCommissionTwice($invoice, expectSeparateEntry: false);
        $this->assertSame($constraintsBefore, Constraint::query()->count(), 'القيدان: لا قيد مستقل للعمولة');
        $this->createSupplierInvoiceTwice($contract);
        $this->registerArrivalTwice($contract);
        $controller = app(MusanedTransferController::class);
        $this->storeTransfer($controller, $contract, self::NET, true);
        $this->storeTransfer($controller, $contract, self::NET);
        $this->assertSame(1, MusanedTransferItem::query()->where('invoice_id', $invoice->id)->count());
        $this->assertBalance('musaned', 0.0);
        $this->assertBalance('intermediate', 0.0);
        $this->assertBalance('commission', self::COMMISSION - self::COMMISSION_TAX);
        $this->assertBalance('expense_tax', self::COMMISSION_TAX);

        // دورة باسترداد قبل التحويل على عقد ثانٍ: تُعكس العمولة وحدها من قيد السند.
        [$second, $secondInvoice] = $this->issueInvoice('اختبار قيدان الاسترداد');
        $this->postCommissionTwice($secondInvoice, expectSeparateEntry: false);
        app(RecruitmentContractRefundService::class)
            ->refund($second->fresh(), ['refund_date' => now()->toDateString(), 'reason' => 'اختبار']);

        // الحسابات مشتركة بين العقدين: العمولة الباقية هي عمولة العقد الأول وحده.
        $this->assertBalance('commission', self::COMMISSION - self::COMMISSION_TAX);
        $this->assertBalance('expense_tax', self::COMMISSION_TAX);
        $this->assertBalance('musaned', self::TOTAL);
    }

    // ── مراحل الدورة ───────────────────────────────────────────────────────

    public function test_reviewed_musaned_import_reuses_settlement_accounting_and_does_not_repeat_commission(): void
    {
        \Illuminate\Support\Facades\Http::fake();
        $this->admin->givePermissionTo(\App\Models\Permission::firstOrCreate(['name' => \App\PermKeys::MUSANED_TRANSFERS_CREATE, 'guard_name' => 'admin']));
        [$contract, $invoice] = $this->issueInvoice('اختبار تحويل مساند المستورد');
        $this->postCommissionTwice($invoice);
        $row = ['reference_number' => 'IMPORT-ACCOUNTING', 'reference_type' => 'عقد استقدام',
            'reference_contract_number' => (string) $contract->id,
            'transfer_date' => now()->toDateString(), 'transferred_amount' => (string) self::NET,
            'bank_account_code' => (string) $this->acc['bank']->code];
        $resolved = app(\App\Services\ingaz\ImportReferenceResolver::class)->resolve('musaned_transfers', $row);
        $this->assertSame([], $resolved['issues']);
        $this->assertSame([], app(\App\Services\ingaz\RecruitmentOperationsImportService::class)->validate('musaned_transfers', $resolved['row']));
        $this->assertSame('created', app(\App\Services\ingaz\DataImportExecutionService::class)->importReviewedRow('musaned_transfers', $row, $this->admin->id));
        $item = MusanedTransferItem::query()->where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertNotNull($item->transfer->settlement_constraint_id);
        $this->assertNotNull($item->transfer->bank_transfer_constraint_id);
        $this->assertBalance('musaned', 0.0);
        $this->assertBalance('intermediate', 0.0);
        $this->assertBalance('bank', self::NET);
        $this->assertBalance('commission', self::COMMISSION - self::COMMISSION_TAX);
        $this->assertBalance('expense_tax', self::COMMISSION_TAX);
        $this->assertNotEmpty(app(\App\Services\ingaz\RecruitmentOperationsImportService::class)->validate('musaned_transfers', $resolved['row']));
    }

    private function issueInvoice(string $nationalityName): array
    {
        $nationality = Nationality::query()->create(['ar' => ['title' => $nationalityName, 'sub_title' => $nationalityName]]);
        $client = $this->account('عميل ' . $nationalityName, 'debtor');
        $user = ClientUser::query()->create([
            'name' => 'عميل ' . $nationalityName,
            'email' => 'cycle' . uniqid() . '@example.test',
            'phone' => '05' . random_int(10000000, 99999999),
            'password' => bcrypt('secret'),
            'account_id' => $client->id,
        ]);
        $contract = Contract::query()->create([
            'status' => ContractStatusEnum::New->value,
            'type' => 'recruitment',
            'user_id' => $user->id,
            'marketer_id' => $this->admin->id,
            'required_maid_nationality_id' => $nationality->id,
        ]);

        $this->acc['cost'] ??= $this->account('تكلفة مبيعات ' . $nationality->display_name, 'debtor');
        $this->acc['cost']->update(['name' => 'تكلفة مبيعات ' . $nationality->display_name]);
        $this->ensureServiceItemTemplate($this->acc['cost']->id);
        $this->acc['office'] ??= $this->account('مكتب خارجي الدورة', 'creditor');
        $office = \App\Models\office\Office::query()->create([
            'ar' => ['title' => $this->acc['office']->name],
            'account_id' => $this->acc['office']->id,
        ]);
        $contract->info()->create(['manual_maid_office_id' => $office->id]);

        $this->acc['sales'] ??= $this->account('مبيعات الدورة', 'creditor');
        $invoiceConstraint = $this->storeConstraint('قيد فاتورة الدورة ' . $contract->id, [
            [$this->acc['sales']->id, 'creditor', 0, 3149.00],
            [$this->acc['vat']->id, 'creditor', 0, 347.10],
            [$client->id, 'debtor', self::TOTAL, 0],
        ]);
        $invoice = Invoice::query()->create([
            'contract_id' => $contract->id,
            'revenue_recognition_mode' => 'on_invoice',
            'direct_recruitment_cost' => 2314,
            'direct_recruitment_count' => 1,
            'external_recruitment_cost' => 700,
            'external_recruitment_count' => 1,
            'government_cost' => 135,
            'government_cost_count' => 1,
            'internal_ticket_cost' => 0,
            'internal_ticket_count' => 0,
            'subtotal' => 3149.00,
            'discount' => 0,
            'vat_amount' => 347.10,
            'total_due' => self::TOTAL,
            'musaned_commission_rate' => 2.4,
            'is_paid' => true,
            'send_to_zatca' => false,
            'invoice_constraint_id' => $invoiceConstraint->id,
        ]);

        $receipt = $this->storeConstraint('سند قبض الدورة / الفاتورة #' . $invoice->id, [
            [$this->acc['musaned']->id, 'debtor', self::TOTAL, 0],
            [$client->id, 'creditor', 0, self::TOTAL],
        ]);
        $bond = Bond::query()->create([
            'code' => 9500000 + random_int(1, 99999),
            'type' => BondTypes::Qabd->value,
            'payment_type' => 1,
            'debit_account_id' => $this->acc['musaned']->id,
            'credit_account_id' => $client->id,
            'value' => self::TOTAL,
            'date' => now()->toDateString(),
            'statement' => 'سند قبض سداد فاتورة طلب استقدام للعقد #' . $contract->id . ' / الفاتورة #' . $invoice->id,
            'created_by_id' => $this->admin->id,
            'constraint_id' => $receipt->id,
            'is_confirm' => 1,
        ]);
        Bond::query()->whereKey($bond->id)->update(['recruitment_contract_id' => $contract->id]);

        return [$contract->fresh(), $invoice->fresh()];
    }

    private function postCommissionTwice(Invoice $invoice, bool $expectSeparateEntry = true): void
    {
        $controller = app(MusanedTransferController::class);
        $first = $controller->recordInvoiceCommission($invoice->fresh());
        $second = $controller->recordInvoiceCommission($invoice->fresh());

        $this->assertNotNull($first);
        $this->assertSame((int) $first->id, (int) $second->id, 'العمولة لا تُسجَّل مرتين');
        $this->assertSame(self::COMMISSION, (float) $invoice->fresh()->musaned_commission_amount);
        $this->assertSame(
            $expectSeparateEntry ? 1 : 0,
            $this->countByStatement('التكاليف البنكية لمساند عند إصدار الفاتورة #' . $invoice->id, separateOnly: true)
        );
    }

    private function createSupplierInvoiceTwice(Contract $contract): void
    {
        $dispatch = app(RecruitmentContractExternalOfficeDispatchController::class);
        $purchasesBefore = DB::table('fn_purchases')->count();

        foreach ([1, 2] as $attempt) {
            $dispatch->persistExternalOfficeDispatch(
                $contract->fresh(),
                now()->toDateString(),
                'RUH',
                (string) self::SUPPLIER_USD,
                $this->admin->id,
                false,
                'full',
                self::SUPPLIER_USD
            );
        }

        $this->assertSame(1, $this->supplierCostEntries($contract), 'فاتورة المورد تُثبت التكلفة مرة واحدة');
        $this->assertSame($purchasesBefore + 1, DB::table('fn_purchases')->count(), 'فاتورة مشتريات واحدة للوكيل');
        $state = $contract->fresh()->getExternalOfficeDispatchState();
        $this->assertNotSame('incomplete', $state['code'], 'فاتورة المورد بتاريخها ومحطتها: ' . $state['message']);
    }

    private function registerArrivalTwice(Contract $contract): void
    {
        $service = app(RecruitmentTripAccountingService::class);
        foreach ([1, 2] as $attempt) {
            $result = $service->recordCompletionEntries($contract->fresh());
            $this->assertFalse($result['created_external_office_cost_constraint'], 'الوصول لا يحمّل الوكيل مرة ثانية');
            $this->assertFalse($result['created_supplier_purchase']);
            $this->assertFalse($result['created_revenue_constraint'], 'الإيراد أُثبت عند الفاتورة');
        }
        $this->assertSame(1, $this->supplierCostEntries($contract));
    }

    private function storeTransfer(MusanedTransferController $controller, Contract $contract, float $amount, bool $mustSucceed = false): void
    {
        $request = Request::create('/musaned-transfers', 'POST', [
            'transfer_date' => now()->toDateString(),
            'bank_account_id' => $this->acc['bank']->id,
            'items' => [['contract_id' => $contract->id, 'transferred_amount' => $amount]],
        ]);
        $this->app->instance('request', $request);

        try {
            $controller->store($request, app(ConstraintService::class), app(YearService::class));
        } catch (\Illuminate\Validation\ValidationException $exception) {
            if ($mustSucceed) {
                $this->fail("التحويل الأول رُفض: " . json_encode($exception->errors(), JSON_UNESCAPED_UNICODE));
            }
            // المحاولة الثانية تُرفض لأن الفاتورة لم تعد مؤهلة؛ العدّ بعدها يثبت عدم التكرار.
        }
    }

    // ── أدوات ────────────────────────────────────────────────────────────

    private function supplierCostEntries(Contract $contract): int
    {
        return DB::table('fn_constraints')
            ->whereNull('deleted_at')
            ->where('statement', 'like', 'فاتورة مورد:%لعقد استقدام: ' . $contract->id . ',%')
            ->count();
    }

    private function countByStatement(string $statement, bool $separateOnly = false): int
    {
        return DB::table('fn_constraints')->whereNull('deleted_at')->where('statement', $statement)->count();
    }

    private function assertBalance(string $key, float $expected): void
    {
        $net = (float) DB::table('fn_constraint_details as d')
            ->join('fn_constraints as c', 'c.id', '=', 'd.constraint_id')
            ->whereNull('c.deleted_at')
            ->whereNull('d.deleted_at')
            ->where('d.account_id', $this->acc[$key]->id)
            ->selectRaw('COALESCE(SUM(d.debtor_value),0) - COALESCE(SUM(d.creditor_value),0) as net')
            ->value('net');

        $this->assertEqualsWithDelta($expected, $net, 0.005, 'رصيد ' . $this->acc[$key]->name);
    }

    private function setting(string $title, string $value): void
    {
        ConfirmSetting::query()->where('title', $title)->delete();
        ConfirmSetting::query()->create(['title' => $title, 'value' => $value, 'created_by_id' => $this->admin->id]);
    }

    private function account(string $name, string $status, int $type = 1): Account
    {
        return Account::query()->create([
            'name' => $name,
            'code' => ((int) DB::table('fn_accounts')->max('code')) + random_int(100, 999),
            'parent_id' => null,
            'level' => 1,
            'operation_type' => 1,
            'status' => $status,
            'statement_type' => 'income_statement',
            'type' => $type,
            'created_by_id' => $this->admin->id,
        ]);
    }

    private function storeConstraint(string $statement, array $rows): Constraint
    {
        $service = app(ConstraintService::class);
        $year = app(YearService::class)->checkDateRange(now()->toDateString());
        $data = [
            'code' => $service->lastCode(),
            'date' => now()->toDateString(),
            'statement' => $statement,
            'account_id' => array_column($rows, 0),
            'account_type' => array_column($rows, 1),
            'debtor_value' => array_column($rows, 2),
            'creditor_value' => array_column($rows, 3),
            'statement_account' => array_fill(0, count($rows), $statement),
            'cost_center_required' => 'off',
            'cost_center_id' => array_fill(0, count($rows), null),
            'value' => array_sum(array_column($rows, 2)),
        ];
        $constraint = $service->storeConstraint($data, $year);
        $service->storeConstraintDetails($data['account_id'], $constraint->id, $data);

        return $constraint;
    }

    private function ensureServiceItemTemplate(int $accountId): void
    {
        if (\Nami\Finance\Models\Item::query()->where('type', 'service')->exists()) {
            return;
        }
        $categoryId = DB::table('fn_categories')->insertGetId(['title' => 'خدمات اختبار الدورة الكاملة']);
        $unitId = DB::table('fn_units')->insertGetId(['title' => 'خدمة اختبار الدورة الكاملة']);
        \Nami\Finance\Models\Item::query()->create([
            'name' => 'خدمة اختبار الدورة الكاملة',
            'type' => 'service',
            'code' => 999997,
            'main_category_id' => $categoryId,
            'sale_unit_id' => $unitId,
            'purchase_unit_id' => $unitId,
            'details' => 'اختبار',
            'sale_account_id' => $accountId,
            'stock_account_id' => $accountId,
        ]);
    }

    private function ensureActiveFiscalYear(): void
    {
        if (!FnYear::query()->whereDate('from_date', '<=', now())->whereDate('to_date', '>=', now())->where('status', 1)->exists()) {
            FnYear::query()->create([
                'title' => 'اختبار الدورة الكاملة ' . now()->year,
                'from_date' => now()->startOfYear()->toDateString(),
                'to_date' => now()->endOfYear()->toDateString(),
                'status' => 1,
                'created_by_id' => $this->admin->id,
            ]);
        }
    }

    private function ensureConstraintNumberSequence(): void
    {
        if (!DB::table('fn_constraint_code_sequences')->where('id', 1)->exists()) {
            DB::table('fn_constraint_code_sequences')->insert([
                'id' => 1,
                'last_code' => (int) DB::table('fn_constraints')->max('code'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
