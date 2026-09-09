<?php

namespace Tests\Feature;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\ContractReturnRequest;
use App\Models\auth\Admin;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\User as ClientUser;
use App\Services\ingaz\RecruitmentContractRefundService;
use App\Services\ingaz\RecruitmentContractWarrantyReturnService;
use App\Services\ingaz\RecruitmentTripAccountingService;
use App\Support\RecruitmentRevenueRecognition;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\ConfirmSetting;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\ConstraintDetails;
use Nami\Finance\Models\FnYear;
use Nami\Finance\Services\ConstraintService;
use Tests\TestCase;

/**
 * إعداد «إثبات إيراد عقود الاستقدام»:
 * الافتراضي (on_arrival) يحافظ على السلوك الأصلي (التزام عند الفاتورة → مبيعات عند الوصول)،
 * والخيار الثاني (on_invoice) يُثبت المبيعات مباشرة ويعكسها مع فاتورة المورد عند الاسترداد.
 */
class RecruitmentRevenueRecognitionTest extends TestCase
{
    use DatabaseTransactions;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
        $this->actingAs($this->admin, 'admin');
        $this->ensureActiveFiscalYear();
        $this->ensureConstraintNumberSequence();
    }

    private function ensureConstraintNumberSequence(): void
    {
        if (DB::table('fn_constraint_code_sequences')->where('id', 1)->exists()) {
            return;
        }

        DB::table('fn_constraint_code_sequences')->insert([
            'id' => 1,
            'last_code' => (int) DB::table('fn_constraints')->max('code'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_default_mode_is_on_arrival_when_setting_is_missing(): void
    {
        ConfirmSetting::query()->where('title', RecruitmentRevenueRecognition::SETTING_KEY)->delete();

        $this->assertSame(RecruitmentRevenueRecognition::MODE_ON_ARRIVAL, RecruitmentRevenueRecognition::mode());
        $this->assertFalse(RecruitmentRevenueRecognition::isOnInvoice());
    }

    public function test_saved_setting_switches_mode_and_invalid_values_fall_back_to_default(): void
    {
        $this->setMode('on_invoice');
        $this->assertTrue(RecruitmentRevenueRecognition::isOnInvoice());

        $this->setMode('garbage');
        $this->assertSame(RecruitmentRevenueRecognition::MODE_ON_ARRIVAL, RecruitmentRevenueRecognition::mode());
    }

    public function test_mode_is_read_from_the_invoice_not_from_the_current_setting(): void
    {
        $this->setMode('on_arrival');

        $legacyInvoice = new Invoice();
        $onInvoice = new Invoice([RecruitmentRevenueRecognition::INVOICE_COLUMN => 'on_invoice']);

        $this->assertSame('on_arrival', RecruitmentRevenueRecognition::modeForInvoice($legacyInvoice));
        $this->assertSame('on_arrival', RecruitmentRevenueRecognition::modeForInvoice(null));
        $this->assertTrue(RecruitmentRevenueRecognition::invoiceRecognizesOnInvoice($onInvoice));
    }

    public function test_configured_nationality_sales_account_wins_over_name_matching(): void
    {
        $nationality = $this->createNationality('اثيوبيا');
        $byName = $this->createAccount('مبيعات اثيوبيا', 'creditor');
        $configured = $this->createAccount('حساب مبيعات مخصص', 'creditor');

        $service = app(RecruitmentTripAccountingService::class);

        $this->assertSame($byName->id, $service->resolveSalesAccountId('اثيوبيا', $nationality->id));

        ConfirmSetting::query()->create([
            'title' => 'nationality_account_' . $nationality->id . '_recruitment_sales',
            'value' => $configured->id,
            'created_by_id' => $this->admin->id,
        ]);

        $this->assertSame($configured->id, $service->resolveSalesAccountId('اثيوبيا', $nationality->id));
    }

    public function test_arrival_skips_the_revenue_entry_for_an_on_invoice_invoice(): void
    {
        $nationality = $this->createNationality('اثيوبيا');
        $contract = $this->createContract($nationality);
        $invoice = $this->createInvoice($contract, ['revenue_recognition_mode' => 'on_invoice']);

        $result = app(RecruitmentTripAccountingService::class)->recordCompletionEntries($contract->fresh());

        $this->assertSame('revenue_recognized_on_invoice', $result['revenue_skipped_reason']);
        $this->assertNull($invoice->fresh()->arrival_revenue_constraint_id);
        $this->assertFalse($result['created_revenue_constraint']);
    }

    public function test_arrival_still_tries_the_liability_transfer_for_a_legacy_invoice(): void
    {
        $nationality = $this->createNationality('اثيوبيا');
        $contract = $this->createContract($nationality);
        $this->createInvoice($contract, ['revenue_recognition_mode' => null]);

        $result = app(RecruitmentTripAccountingService::class)->recordCompletionEntries($contract->fresh());

        $this->assertNotSame('revenue_recognized_on_invoice', $result['revenue_skipped_reason']);
        $this->assertContains($result['revenue_skipped_reason'], ['missing_liability_account', 'missing_liability_constraint']);
    }

    public function test_external_office_cost_reversal_mirrors_the_original_entry_once(): void
    {
        $nationality = $this->createNationality('اثيوبيا');
        $contract = $this->createContract($nationality);
        $cost = $this->createAccount('تكلفة مبيعات اثيوبيا', 'debtor');
        $office = $this->createAccount('مكتب شريف ادام', 'creditor');
        $original = $this->storeConstraint('فاتورة مورد اختبار', [
            [$cost->id, 'debtor', 2250, 0],
            [$office->id, 'creditor', 0, 2250],
        ]);
        $invoice = $this->createInvoice($contract, [
            'revenue_recognition_mode' => 'on_invoice',
            'external_office_cost_constraint_id' => $original->id,
        ]);

        $service = app(RecruitmentTripAccountingService::class);
        $reversal = $service->reverseExternalOfficeCostEntries($contract, $invoice, now()->toDateString(), 'استرداد فاتورة المورد اختبار');

        $this->assertNotNull($reversal);
        $this->assertSame($reversal->id, (int) $invoice->fresh()->external_office_cost_reversal_constraint_id);
        $this->assertSame([
            [$office->id, 2250.0, 0.0],
            [$cost->id, 0.0, 2250.0],
        ], $this->lines($reversal));

        $again = $service->reverseExternalOfficeCostEntries($contract, $invoice->fresh(), now()->toDateString(), 'استرداد فاتورة المورد اختبار');
        $this->assertSame($reversal->id, $again->id);
        $this->assertSame(1, Constraint::query()->where('statement', 'استرداد فاتورة المورد اختبار')->count());
    }

    public function test_warranty_return_reverses_sales_vat_and_supplier_directly_in_on_invoice_mode(): void
    {
        $nationality = $this->createNationality('اثيوبيا');
        $contract = $this->createContract($nationality, withClientAccount: true);
        $client = $contract->user->account_id;
        $sales = $this->createAccount('مبيعات اثيوبيا', 'creditor');
        $vat = $this->createAccount('ضريبة القيمة المضافة', 'creditor');
        $cost = $this->createAccount('تكلفة مبيعات اثيوبيا', 'debtor');
        $office = $this->createAccount('مكتب شريف ادام', 'creditor');
        ConfirmSetting::query()->create(['title' => 'revenue_tax_account', 'value' => $vat->id, 'created_by_id' => $this->admin->id]);

        $invoiceConstraint = $this->storeConstraint('قيد فاتورة اختبار', [
            [$sales->id, 'creditor', 0, 285],
            [$client, 'debtor', 285, 0],
            [$sales->id, 'creditor', 0, 3653],
            [$client, 'debtor', 3653, 0],
            [$vat->id, 'creditor', 0, 42.75],
            [$client, 'debtor', 42.75, 0],
        ]);
        $supplierConstraint = $this->storeConstraint('فاتورة مورد اختبار', [
            [$cost->id, 'debtor', 2250, 0],
            [$office->id, 'creditor', 0, 2250],
        ]);
        $invoice = $this->createInvoice($contract, [
            'revenue_recognition_mode' => 'on_invoice',
            'total_due' => 3980.75,
            'vat_amount' => 42.75,
            'invoice_constraint_id' => $invoiceConstraint->id,
            'external_office_cost_constraint_id' => $supplierConstraint->id,
        ]);
        $returnRequest = ContractReturnRequest::query()->create([
            'contract_id' => $contract->id,
            'user_id' => $contract->user_id,
            'status' => 'approved',
            'notes' => 'اختبار',
            'processed_at' => now(),
        ]);

        $constraint = app(RecruitmentContractWarrantyReturnService::class)->record($contract->fresh(), $returnRequest);

        $this->assertNotNull($constraint);
        $this->assertSame([
            [$sales->id, 285.0, 0.0],
            [$sales->id, 3653.0, 0.0],
            [$vat->id, 42.75, 0.0],
            [$client, 0.0, 3980.75],
        ], $this->lines($constraint));

        $reversalId = $invoice->fresh()->external_office_cost_reversal_constraint_id;
        $this->assertNotNull($reversalId);
        $this->assertSame([
            [$office->id, 2250.0, 0.0],
            [$cost->id, 0.0, 2250.0],
        ], $this->lines(Constraint::query()->findOrFail($reversalId)));
    }

    public function test_refund_preview_shows_the_supplier_reversal_section_only_in_on_invoice_mode(): void
    {
        $nationality = $this->createNationality('اثيوبيا');
        $contract = $this->createContract($nationality, withClientAccount: true);
        $cost = $this->createAccount('تكلفة مبيعات اثيوبيا', 'debtor');
        $office = $this->createAccount('مكتب شريف ادام', 'creditor');
        $supplierConstraint = $this->storeConstraint('فاتورة مورد اختبار', [
            [$cost->id, 'debtor', 2250, 0],
            [$office->id, 'creditor', 0, 2250],
        ]);
        $invoice = $this->createInvoice($contract, [
            'revenue_recognition_mode' => 'on_invoice',
            'total_due' => 3980.75,
            'vat_amount' => 42.75,
            'external_office_cost_constraint_id' => $supplierConstraint->id,
        ]);

        $preview = app(RecruitmentContractRefundService::class)->previewDetails($contract->fresh(), now()->toDateString());
        $supplierLines = array_values(array_filter(
            $preview['lines'],
            fn (array $line) => ($line['section'] ?? null) === RecruitmentContractRefundService::EXTERNAL_OFFICE_REVERSAL_SECTION
        ));
        $this->assertCount(2, $supplierLines);
        $this->assertSame($office->id, $supplierLines[0]['account_id']);
        $this->assertSame(2250.0, $supplierLines[0]['debtor_value']);
        $this->assertSame($cost->id, $supplierLines[1]['account_id']);
        $this->assertSame(2250.0, $supplierLines[1]['creditor_value']);

        $invoice->update(['revenue_recognition_mode' => 'on_arrival']);
        $preview = app(RecruitmentContractRefundService::class)->previewDetails($contract->fresh(), now()->toDateString());
        $this->assertEmpty(array_filter(
            $preview['lines'],
            fn (array $line) => ($line['section'] ?? null) === RecruitmentContractRefundService::EXTERNAL_OFFICE_REVERSAL_SECTION
        ));
    }

    // ── helpers ────────────────────────────────────────────────────────────────

    private function setMode(string $mode): void
    {
        ConfirmSetting::query()->where('title', RecruitmentRevenueRecognition::SETTING_KEY)->delete();
        ConfirmSetting::query()->create([
            'title' => RecruitmentRevenueRecognition::SETTING_KEY,
            'value' => $mode,
            'created_by_id' => $this->admin->id,
        ]);
    }

    private function ensureActiveFiscalYear(): void
    {
        $exists = FnYear::query()
            ->whereDate('from_date', '<=', now())
            ->whereDate('to_date', '>=', now())
            ->where('status', 1)
            ->exists();

        if (!$exists) {
            FnYear::query()->create([
                'title' => 'اختبار ' . now()->year,
                'from_date' => now()->startOfYear()->toDateString(),
                'to_date' => now()->endOfYear()->toDateString(),
                'status' => 1,
                'created_by_id' => $this->admin->id,
            ]);
        }
    }

    private function createNationality(string $name): Nationality
    {
        return Nationality::query()->create([
            'ar' => ['title' => $name, 'sub_title' => $name],
        ]);
    }

    private function createContract(Nationality $nationality, bool $withClientAccount = false): Contract
    {
        $user = ClientUser::query()->create([
            'name' => 'العميل اكس',
            'email' => 'client' . uniqid() . '@example.test',
            'phone' => '05' . random_int(10000000, 99999999),
            'password' => bcrypt('secret'),
            'account_id' => $withClientAccount ? $this->createAccount('العميل اكس', 'debtor')->id : null,
        ]);

        return Contract::query()->create([
            'status' => ContractStatusEnum::New->value,
            'type' => 'recruitment',
            'user_id' => $user->id,
            'marketer_id' => $this->admin->id,
            'required_maid_nationality_id' => $nationality->id,
        ]);
    }

    private function createInvoice(Contract $contract, array $overrides = []): Invoice
    {
        return Invoice::query()->create(array_merge([
            'contract_id' => $contract->id,
            'direct_recruitment_cost' => 285,
            'direct_recruitment_count' => 1,
            'external_recruitment_cost' => 3653,
            'external_recruitment_count' => 1,
            'government_cost' => 0,
            'government_cost_count' => 0,
            'internal_ticket_cost' => 0,
            'internal_ticket_count' => 0,
            'subtotal' => 3938,
            'discount' => 0,
            'vat_amount' => 42.75,
            'total_due' => 3980.75,
            'is_paid' => true,
            'send_to_zatca' => false,
        ], $overrides));
    }

    private function createAccount(string $name, string $status): Account
    {
        $code = ((int) DB::table('fn_accounts')->max('code')) + random_int(100, 999);

        return Account::query()->create([
            'name' => $name,
            'code' => $code,
            'parent_id' => null,
            'level' => 1,
            'operation_type' => 1,
            'status' => $status,
            'statement_type' => 'income_statement',
            'type' => 1,
            'created_by_id' => $this->admin->id,
        ]);
    }

    /**
     * @param array<int, array{0:int,1:string,2:float|int,3:float|int}> $rows [account_id, type, debtor, creditor]
     */
    private function storeConstraint(string $statement, array $rows): Constraint
    {
        $service = app(ConstraintService::class);
        $year = app(\Nami\Finance\Services\YearService::class)->checkDateRange(now()->toDateString());
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

    private function lines(Constraint $constraint): array
    {
        return ConstraintDetails::query()
            ->where('constraint_id', $constraint->id)
            ->orderBy('id')
            ->get()
            ->map(fn (ConstraintDetails $d) => [(int) $d->account_id, (float) $d->debtor_value, (float) $d->creditor_value])
            ->all();
    }
}
