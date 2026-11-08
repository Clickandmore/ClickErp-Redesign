<?php

namespace Tests\Feature;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ContractReturnRequest;
use App\Models\DeportationProcedure;
use App\Models\ShelterCostEntry;
use App\Models\auth\Admin;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\User as ClientUser;
use App\Services\ingaz\DeportationCompletionService;
use App\Services\ingaz\RecruitmentContractWarrantyReturnService;
use App\Services\ingaz\ShelterCostLedgerService;
use App\Support\ShelterAccountingSettings;
use App\Support\PostArrivalRefundSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\ConfirmSetting;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\ConstraintDetails;
use Nami\Finance\Models\FnYear;
use Nami\Finance\Services\ConstraintService;
use Tests\TestCase;

/**
 * دفتر الإيواء المستقل: استلام العاملة (S1) عبر الإرجاع داخل الضمان مع إشعار دائن،
 * الإفراج عند الترحيل (القيد 18)، الإفراج عند إعادة البيع (S4)، حارس الخروج، والتصفية.
 */
class ShelterCostLedgerTest extends TestCase
{
    use DatabaseTransactions;

    private Admin $admin;
    private Nationality $nationality;
    private Account $asset;
    private Account $costReturns;
    private Account $salesReturns;
    private Account $vat;
    private Account $salesRecovery;
    private Account $office;
    private Account $costOfSales;
    private Account $sales;
    private Account $liability;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
        $this->actingAs($this->admin, 'admin');
        $this->ensureActiveFiscalYear();
        $this->ensureConstraintNumberSequence();

        // الأسماء الفعلية على شجرة الحسابات (بلا همزة وبصيغة الجمع) لضمان أن الحل يعمل عليها.
        $this->nationality = Nationality::query()->create(['ar' => ['title' => 'اثيوبيا', 'sub_title' => 'اثيوبيا']]);
        $this->asset = $this->createAccount('مصروفات مدفوعة مقدما ايواء اثيوبيا', 'debtor');
        $this->costReturns = $this->createAccount('مردودات تكلفة اثيوبيا', 'creditor');
        $this->salesReturns = $this->createAccount('مردودات مبيعات استقدام اثيوبيا', 'debtor');
        $this->vat = $this->createAccount('حساب الضريبة على الايرادات', 'creditor');
        $this->salesRecovery = $this->createAccount('إيراد استرداد مبيعات', 'creditor');
        $this->office = $this->createAccount('مكتب اختبار خارجي', 'creditor');
        $this->costOfSales = $this->createAccount('تكاليف مبيعات (اثيوبيا)', 'debtor');
        $this->sales = $this->createAccount('مبيعات (اثيوبيا)', 'creditor');
        $this->liability = $this->createAccount('التزامات عقود (اثيوبيا)', 'creditor');

        ConfirmSetting::query()->create(['title' => 'revenue_tax_account', 'value' => $this->vat->id, 'created_by_id' => $this->admin->id]);
        ConfirmSetting::query()->create(['title' => 'nationality_account_' . $this->nationality->id . '_recruitment_cost', 'value' => $this->costOfSales->id, 'created_by_id' => $this->admin->id]);
        ConfirmSetting::query()->create(['title' => 'nationality_account_' . $this->nationality->id . '_recruitment_sales', 'value' => $this->sales->id, 'created_by_id' => $this->admin->id]);
        ConfirmSetting::query()->create(['title' => 'nationality_account_' . $this->nationality->id . '_contract_liability', 'value' => $this->liability->id, 'created_by_id' => $this->admin->id]);
        ConfirmSetting::query()->create(['title' => ShelterAccountingSettings::SALES_RECOVERY_INCOME_ACCOUNT, 'value' => $this->salesRecovery->id, 'created_by_id' => $this->admin->id]);
    }

    public function test_nationality_accounts_resolve_by_normalized_name_without_settings(): void
    {
        $resolver = \App\Support\NationalityAccountResolver::class;

        $this->assertSame($this->asset->id, $resolver::forNationality($this->nationality, 'shelter_prepaid_expense'));
        $this->assertSame($this->salesReturns->id, $resolver::forNationality($this->nationality, 'recruitment_sales_return'));
        $this->assertSame($this->costReturns->id, $resolver::forNationality($this->nationality, 'recruitment_cost_return'));
    }

    public function test_warranty_return_after_arrival_issues_credit_note_stay_deduction_and_shelter_capitalization(): void
    {
        ConfirmSetting::query()->create(['title' => PostArrivalRefundSettings::POSTING_MODE, 'value' => 'auto', 'created_by_id' => $this->admin->id]);
        [$contract, $invoice] = $this->arrivedContractWithInvoice(officeCostUsd: 600);
        $client = $contract->user->account_id;

        $returnRequest = ContractReturnRequest::query()->create([
            'contract_id' => $contract->id,
            'user_id' => $contract->user_id,
            'status' => 'approved',
            'notes' => 'اختبار',
            'processed_at' => now(),
        ]);

        $constraint = app(RecruitmentContractWarrantyReturnService::class)->record($contract->fresh(), $returnRequest);
        $returnRequest->refresh();

        // 1. إشعار دائن بمردود المبيعات (وليس المبيعات مباشرة) والضريبة على العميل.
        $this->assertNotNull($returnRequest->credit_note_id);
        $creditNote = $returnRequest->creditNote;
        $this->assertSame([
            [$this->salesReturns->id, 3938.0, 0.0],
            [$client, 0.0, 3938.0],
            [$this->vat->id, 42.75, 0.0],
            [$client, 0.0, 42.75],
        ], $this->lines(Constraint::query()->findOrFail($creditNote->constraint_id)));
        $this->assertSame(3980.75, (float) $creditNote->total);

        // 2. خصم مدة البقاء: 30 يوماً من 90 على صافي 3938 = 1312.67
        $this->assertNotNull($returnRequest->return_constraint_id);
        $this->assertSame($constraint->id, (int) $returnRequest->return_constraint_id);
        $this->assertSame([
            [$client, 1312.67, 0.0],
            [$this->salesRecovery->id, 0.0, 1312.67],
        ], $this->lines($constraint));

        // 3. قيد إيواء مستقل S1: مدين أصل الإيواء / دائن مردودات التكلفة بتكلفة المكتب 600 × 3.75
        $this->assertNotNull($returnRequest->shelter_constraint_id);
        $shelter = Constraint::query()->findOrFail($returnRequest->shelter_constraint_id);
        $this->assertSame([
            [$this->asset->id, 2250.0, 0.0],
            [$this->costReturns->id, 0.0, 2250.0],
        ], $this->lines($shelter));
        $this->assertNotNull(ConstraintDetails::query()->where('constraint_id', $shelter->id)->where('account_id', $this->asset->id)->value('cost_center_id'));
        $this->assertSame(2250.0, app(ShelterCostLedgerService::class)->balance($contract->maid_id));

        // إعادة المعالجة لا تكرر شيئاً.
        app(RecruitmentContractWarrantyReturnService::class)->record($contract->fresh(), $returnRequest->fresh());
        $this->assertSame(1, ShelterCostEntry::query()->where('maid_id', $contract->maid_id)->count());
        $this->assertSame(1, $invoice->fresh()->recruitmentCreditNotes()->count());
    }

    public function test_deportation_completion_posts_entry_18_and_marks_maid_deported(): void
    {
        [$contract] = $this->arrivedContractWithInvoice(officeCostUsd: 600);
        $ledger = app(ShelterCostLedgerService::class);
        $this->capitalize($ledger, $contract, 2250);

        $contract->update(['status' => ContractStatusEnum::Shelter->value]);
        $contract->maid->update(['status' => 'available', 'available' => true]);

        $procedure = DeportationProcedure::query()->create([
            'contract_id' => $contract->id,
            'maid_id' => $contract->maid_id,
            'status' => 'in_progress',
            'procedures_confirmed' => true,
            'trip_scheduled' => true,
            'started_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        $result = app(DeportationCompletionService::class)->complete($procedure);

        $this->assertNotNull($result['constraint']);
        $this->assertSame(2250.0, $result['amount']);
        $this->assertSame('المكتب الخارجي', $result['debit']);
        $this->assertSame([
            [$this->office->id, 2250.0, 0.0],
            [$this->asset->id, 0.0, 2250.0],
        ], $this->lines($result['constraint']));
        $this->assertSame(0.0, $ledger->balance($contract->maid_id));

        $procedure->refresh();
        $this->assertSame('completed', $procedure->status);
        $this->assertSame($result['constraint']->id, (int) $procedure->constraint_id);
        $this->assertSame(ContractStatusEnum::Back->value, $contract->fresh()->status);
        $this->assertSame('deported', $contract->maid->fresh()->status);
        $this->assertFalse((bool) $contract->maid->fresh()->available);
        $this->assertSame('shelter', $contract->fresh()->contract_status_logs()->latest('id')->value('previous_status'));

        // الإنهاء مرة ثانية يعيد نفس القيد بلا تكرار.
        $again = app(DeportationCompletionService::class)->complete($procedure->fresh());
        $this->assertSame($result['constraint']->id, $again['constraint']->id);
        $this->assertTrue($again['already_completed']);
        $this->assertSame(1, ShelterCostEntry::query()->where('kind', ShelterCostEntry::KIND_RELEASE_TO_DEPORTATION)->where('maid_id', $contract->maid_id)->count());
    }

    public function test_deportation_uses_loss_account_when_office_charge_is_off(): void
    {
        [$contract] = $this->arrivedContractWithInvoice(officeCostUsd: 600);
        $ledger = app(ShelterCostLedgerService::class);
        $this->capitalize($ledger, $contract, 2250);
        $loss = $this->createAccount('خسائر إيواء', 'debtor');
        ConfirmSetting::query()->create(['title' => ShelterAccountingSettings::DEPORTATION_CHARGE_EXTERNAL_OFFICE, 'value' => 'off', 'created_by_id' => $this->admin->id]);
        ConfirmSetting::query()->create(['title' => ShelterAccountingSettings::DEPORTATION_LOSS_ACCOUNT, 'value' => $loss->id, 'created_by_id' => $this->admin->id]);
        $contract->update(['status' => ContractStatusEnum::Shelter->value]);

        $procedure = DeportationProcedure::query()->create([
            'contract_id' => $contract->id, 'maid_id' => $contract->maid_id, 'status' => 'in_progress',
            'procedures_confirmed' => true, 'trip_scheduled' => true, 'started_at' => now(), 'created_by' => $this->admin->id,
        ]);

        $result = app(DeportationCompletionService::class)->complete($procedure);
        $this->assertSame('خسائر الإيواء', $result['debit']);
        $this->assertSame([[$loss->id, 2250.0, 0.0], [$this->asset->id, 0.0, 2250.0]], $this->lines($result['constraint']));
    }

    public function test_deportation_cancel_is_refused_after_completion(): void
    {
        [$contract] = $this->arrivedContractWithInvoice();
        $procedure = DeportationProcedure::query()->create([
            'contract_id' => $contract->id, 'maid_id' => $contract->maid_id, 'status' => 'completed',
            'procedures_confirmed' => true, 'trip_scheduled' => true, 'started_at' => now(), 'created_by' => $this->admin->id,
        ]);

        $this->expectException(\DomainException::class);
        app(DeportationCompletionService::class)->cancel($procedure, 'خطأ');
    }

    public function test_deportation_cancel_keeps_contract_in_shelter(): void
    {
        [$contract] = $this->arrivedContractWithInvoice();
        $contract->update(['status' => ContractStatusEnum::Shelter->value]);
        $procedure = DeportationProcedure::query()->create([
            'contract_id' => $contract->id, 'maid_id' => $contract->maid_id, 'status' => 'pending',
            'started_at' => now(), 'created_by' => $this->admin->id,
        ]);

        $cancelled = app(DeportationCompletionService::class)->cancel($procedure, 'بدأ بالخطأ');

        $this->assertSame('cancelled', $cancelled->status);
        $this->assertSame('بدأ بالخطأ', $cancelled->cancel_reason);
        $this->assertSame(ContractStatusEnum::Shelter->value, $contract->fresh()->status);
    }

    public function test_resale_from_shelter_releases_balance_to_cost_of_sales(): void
    {
        [$contract] = $this->arrivedContractWithInvoice(officeCostUsd: 600);
        $ledger = app(ShelterCostLedgerService::class);
        $this->capitalize($ledger, $contract, 2250);

        $newContract = Contract::query()->create([
            'status' => ContractStatusEnum::New->value,
            'type' => 'recruitment',
            'user_id' => $contract->user_id,
            'maid_id' => $contract->maid_id,
            'marketer_id' => $this->admin->id,
            'required_maid_nationality_id' => $this->nationality->id,
        ]);

        $result = $ledger->releaseToSale($newContract, now()->toDateString(), null);

        $this->assertSame(2250.0, $result['amount']);
        $this->assertSame([[$this->costOfSales->id, 2250.0, 0.0], [$this->asset->id, 0.0, 2250.0]], $this->lines($result['constraint']));
        $this->assertSame(0.0, $ledger->balance($contract->maid_id));
        $this->assertSame(2250.0, $ledger->releaseToSale($newContract, now()->toDateString(), null)['amount'], 'إعادة الاستدعاء تعيد نفس الحركة بلا تكرار');
        $this->assertSame(1, ShelterCostEntry::query()->where('kind', ShelterCostEntry::KIND_RELEASE_TO_SALE)->count());
    }

    public function test_manual_shelter_exit_is_refused_with_open_balance_and_written_off_with_reason(): void
    {
        [$contract] = $this->arrivedContractWithInvoice(officeCostUsd: 600);
        $ledger = app(ShelterCostLedgerService::class);
        $this->capitalize($ledger, $contract, 2250);
        $contract->update(['status' => ContractStatusEnum::Shelter->value]);
        ContractInfo::query()->updateOrCreate(['contract_id' => $contract->id], ['shelter_id' => 1]);
        $this->grantShelterPermissions();

        $refused = $this->postJson(route('ShelterContract.back', $contract->id));
        $refused->assertStatus(422);
        $this->assertStringContainsString('رصيد إيواء', $refused->json('message'));
        $this->assertSame(ContractStatusEnum::Shelter->value, $contract->fresh()->status);

        $writeOff = $this->createAccount('تصفية أرصدة الإيواء', 'debtor');
        ConfirmSetting::query()->create(['title' => ShelterAccountingSettings::WRITE_OFF_ACCOUNT, 'value' => $writeOff->id, 'created_by_id' => $this->admin->id]);

        $ok = $this->postJson(route('ShelterContract.back', $contract->id), ['write_off_reason' => 'عاملة غادرت بلا إجراء']);
        $ok->assertOk();
        $this->assertSame(ContractStatusEnum::Completed->value, $contract->fresh()->status);
        $this->assertSame(0.0, $ledger->balance($contract->maid_id));
        $entry = ShelterCostEntry::query()->where('kind', ShelterCostEntry::KIND_WRITE_OFF)->firstOrFail();
        $this->assertSame([[$writeOff->id, 2250.0, 0.0], [$this->asset->id, 0.0, 2250.0]], $this->lines($entry->constraint));
    }

    public function test_direct_entry_maid_returning_to_her_customer_reverses_the_receipt_without_write_off(): void
    {
        [$contract] = $this->arrivedContractWithInvoice(officeCostUsd: 600);
        $ledger = app(ShelterCostLedgerService::class);
        $ledger->capitalizeOfficeCost($contract, 2250, now()->toDateString(), $contract, 'دخول مباشر');
        $contract->update(['status' => ContractStatusEnum::Shelter->value]);
        ContractInfo::query()->updateOrCreate(['contract_id' => $contract->id], ['shelter_id' => 1]);
        $this->grantShelterPermissions();

        $this->postJson(route('ShelterContract.back', $contract->id))->assertOk();

        $this->assertSame(ContractStatusEnum::Completed->value, $contract->fresh()->status);
        $this->assertSame(0.0, $ledger->balance($contract->maid_id));
        $entry = ShelterCostEntry::query()->where('kind', ShelterCostEntry::KIND_RETURN_TO_CUSTOMER)->sole();
        $this->assertSame([[$this->costReturns->id, 2250.0, 0.0], [$this->asset->id, 0.0, 2250.0]], $this->lines($entry->constraint));
        $this->assertSame(0, ShelterCostEntry::query()->where('kind', ShelterCostEntry::KIND_WRITE_OFF)->count());
    }

    public function test_return_to_customer_is_refused_until_the_agent_recovery_is_reversed(): void
    {
        [$contract] = $this->arrivedContractWithInvoice(officeCostUsd: 600);
        $ledger = app(ShelterCostLedgerService::class);
        $receipt = $ledger->capitalizeOfficeCost($contract, 2250, now()->toDateString(), $contract, 'دخول مباشر')['entry'];
        $contract->update(['status' => ContractStatusEnum::Shelter->value]);
        ContractInfo::query()->updateOrCreate(['contract_id' => $contract->id], ['shelter_id' => 1, 'is_outside_shelter' => false]);
        $this->grantShelterPermissions();
        $service = app(\App\Services\ingaz\ShelterAgentCostRecoveryService::class);
        $recovery = $service->recover($contract, $receipt->id, now()->toDateString(), 'مطالبة', (string) \Illuminate\Support\Str::uuid());
        $this->assertSame(0.0, $ledger->balance($contract->maid_id));

        $refused = $this->postJson(route('ShelterContract.back', $contract->id));
        $refused->assertStatus(422);
        $this->assertStringContainsString('اعكس الاسترداد', $refused->json('message'));
        $this->assertSame(ContractStatusEnum::Shelter->value, $contract->fresh()->status);

        $service->reverse($contract, $recovery->id, 'العاملة رجعت لعميلها');
        $this->postJson(route('ShelterContract.back', $contract->id))->assertOk();
        $this->assertSame(0.0, $ledger->balance($contract->maid_id));
        $this->assertSame(1, ShelterCostEntry::query()->where('kind', ShelterCostEntry::KIND_RETURN_TO_CUSTOMER)->count());
    }

    public function test_explicit_customer_return_exits_shelter_reverses_receipt_and_is_not_repeatable(): void
    {
        [$contract] = $this->arrivedContractWithInvoice(officeCostUsd: 600);
        $ledger = app(ShelterCostLedgerService::class);
        $ledger->capitalizeOfficeCost($contract, 2250, now()->toDateString(), $contract, 'دخول مباشر');
        $contract->update(['status' => ContractStatusEnum::Shelter->value]);
        $contract->info->update(['shelter_id' => 1, 'is_available_for_instant' => true, 'is_outside_shelter' => false]);
        $contract->maid->update(['status' => 'available', 'available' => true, 'is_show' => true]);
        $this->grantShelterPermissions();
        $this->postJson(route('ShelterContract.returnToCustomer', $contract->id))->assertOk();
        $this->assertSame('completed', $contract->fresh()->status);
        $this->assertNull($contract->info->fresh()->shelter_id);
        $this->assertFalse((bool) $contract->info->fresh()->is_available_for_instant);
        $this->assertFalse((bool) $contract->maid->fresh()->available);
        $this->assertSame('contracted', $contract->maid->fresh()->status);
        $this->assertSame(0.0, $ledger->balance($contract->maid_id));
        $entry = ShelterCostEntry::query()->where('kind', ShelterCostEntry::KIND_RETURN_TO_CUSTOMER)->sole();
        $this->assertSame([[$this->costReturns->id, 2250.0, 0.0], [$this->asset->id, 0.0, 2250.0]], $this->lines($entry->constraint));
        $this->postJson(route('ShelterContract.returnToCustomer', $contract->id))->assertStatus(422);
        $this->assertSame(1, ShelterCostEntry::query()->where('kind', ShelterCostEntry::KIND_RETURN_TO_CUSTOMER)->count());
        if (Schema::hasTable('shelter_stays')) {
            $this->assertFalse(DB::table('shelter_stays')->where('contract_id', $contract->id)->whereNull('exited_at')->exists());
            $this->assertSame('return_to_customer', DB::table('shelter_stays')->where('contract_id', $contract->id)->latest('id')->value('exit_reason'));
        }
    }

    public function test_explicit_customer_return_without_receipt_creates_no_financial_movement(): void
    {
        [$contract] = $this->arrivedContractWithInvoice();
        $contract->update(['status' => ContractStatusEnum::Shelter->value]);
        $this->grantShelterPermissions();
        $before = Constraint::query()->count();
        $this->postJson(route('ShelterContract.returnToCustomer', $contract->id))->assertOk();
        $this->assertSame($before, Constraint::query()->count());
        $this->assertSame(0, ShelterCostEntry::query()->where('maid_id', $contract->maid_id)->count());
    }

    public function test_explicit_customer_return_cannot_bypass_a_cancelled_contract_even_without_cost(): void
    {
        [$contract] = $this->arrivedContractWithInvoice();
        $contract->update(['status' => ContractStatusEnum::Shelter->value]);
        $contract->contract_status_logs()->create(['status' => 'shelter', 'previous_status' => 'back', 'date' => now()]);
        $this->grantShelterPermissions();
        $this->postJson(route('ShelterContract.returnToCustomer', $contract->id), ['write_off_reason' => 'تجاوز'])->assertStatus(422);
        $this->assertSame('shelter', $contract->fresh()->status);
    }

    public function test_explicit_customer_return_rejects_worker_already_outside(): void
    {
        [$contract] = $this->arrivedContractWithInvoice();
        $contract->update(['status' => ContractStatusEnum::Shelter->value]);
        $contract->info->update(['is_outside_shelter' => true, 'outside_shelter_reason' => 'vacation']);
        $this->grantShelterPermissions();
        $this->postJson(route('ShelterContract.returnToCustomer', $contract->id))->assertStatus(422);
        $this->assertSame('shelter', $contract->fresh()->status);
    }

    public function test_explicit_customer_return_cannot_clear_posted_cost_when_automatic_entries_are_disabled(): void
    {
        [$contract] = $this->arrivedContractWithInvoice(officeCostUsd: 600);
        $ledger = app(ShelterCostLedgerService::class);
        $ledger->capitalizeOfficeCost($contract, 2250, now()->toDateString(), $contract, 'دخول مباشر');
        $contract->update(['status' => 'shelter']);
        ConfirmSetting::query()->updateOrCreate(['title' => \App\Support\AutomaticJournalEntries::SETTING_KEY], ['value' => 'off']);
        $this->grantShelterPermissions();
        $this->postJson(route('ShelterContract.returnToCustomer', $contract->id))->assertStatus(422);
        $this->assertSame('shelter', $contract->fresh()->status);
        $this->assertSame(2250.0, $ledger->balance($contract->maid_id));
    }

    public function test_capitalized_living_expense_debits_shelter_asset_and_records_ledger(): void
    {
        [$contract] = $this->arrivedContractWithInvoice();
        $contract->update(['status' => ContractStatusEnum::Shelter->value]);
        ContractInfo::query()->updateOrCreate(['contract_id' => $contract->id], ['shelter_id' => 1, 'is_outside_shelter' => false]);
        $bank = $this->createAccount('بنك اختبار', 'debtor', \Nami\Finance\Enums\AccountTypesEnum::Bank->value);
        $expenseAccount = $this->createAccount('مصروف اعاشة اختبار', 'debtor');

        $expense = app(\App\Services\ingaz\ShelterLivingExpenseAccountingService::class)->create([
            'contract_id' => $contract->id,
            'expense_date' => now()->toDateString(),
            'title' => 'إعاشة',
            'total_amount' => 150,
            'expense_account_id' => $expenseAccount->id,
            'payment_source_type' => 'bank',
            'payment_account_id' => $bank->id,
        ]);

        $this->assertSame([[$this->asset->id, 150.0, 0.0], [$bank->id, 0.0, 150.0]], $this->lines($expense->constraint));
        $this->assertSame(150.0, app(ShelterCostLedgerService::class)->balance($contract->maid_id));

        // عكس المصروف يصفّر الرصيد بقيد مقابل ولا يحذف شيئاً.
        $reversal = app(\App\Services\ingaz\ShelterLivingExpenseAccountingService::class)->reverse($expense, 'خطأ إدخال');
        $this->assertSame([[$bank->id, 150.0, 0.0], [$this->asset->id, 0.0, 150.0]], $this->lines($reversal));
        $this->assertSame(0.0, app(ShelterCostLedgerService::class)->balance($contract->maid_id));
        $this->assertNotNull($expense->fresh()->constraint_id);
    }

    public function test_agent_recovery_excludes_living_cost_and_deportation_does_not_charge_office_twice(): void
    {
        [$contract] = $this->arrivedContractWithInvoice(600);
        $ledger = app(ShelterCostLedgerService::class);
        $this->capitalize($ledger, $contract, 2250);
        $contract->update(['status' => 'shelter']);
        $receipt = ShelterCostEntry::query()->where('maid_id', $contract->maid_id)->firstOrFail();
        $loss = $this->createAccount('خسائر اختبار', 'debtor');
        ConfirmSetting::query()->create(['title' => ShelterAccountingSettings::DEPORTATION_LOSS_ACCOUNT, 'value' => $loss->id]);
        $livingJournal = $this->storeConstraint('إعاشة', [[$this->asset->id, 'debtor', 750, 0], [$loss->id, 'creditor', 0, 750]]);
        ShelterCostEntry::query()->create(['maid_id' => $contract->maid_id, 'contract_id' => $contract->id,
            'kind' => ShelterCostEntry::KIND_LIVING_EXPENSE, 'amount' => 750, 'constraint_id' => $livingJournal->id]);
        $service = app(\App\Services\ingaz\ShelterAgentCostRecoveryService::class);
        $key = (string) \Illuminate\Support\Str::uuid();
        $recovery = $service->recover($contract, $receipt->id, now()->toDateString(), 'مطالبة أصلية', $key);
        $this->assertSame(2250.0, (float) $recovery->amount);
        $this->assertSame([[$this->office->id, 2250.0, 0.0], [$this->asset->id, 0.0, 2250.0]], $this->lines($recovery->constraint));
        $this->assertSame($recovery->id, $service->recover($contract, $receipt->id, now()->toDateString(), 'مطالبة أصلية', $key)->id);
        $this->assertSame(750.0, $ledger->balance($contract->maid_id));
        $this->assertSame('shelter', $contract->fresh()->status);
        $this->assertTrue(\App\Support\ConstraintReferenceGuard::isReferenced($recovery->constraint_id));
        $procedure = DeportationProcedure::query()->create(['contract_id' => $contract->id, 'maid_id' => $contract->maid_id,
            'status' => 'in_progress', 'started_at' => now(), 'created_by' => $this->admin->id]);
        $result = $ledger->releaseToDeportation($procedure, now()->toDateString());
        $this->assertSame([[$loss->id, 750.0, 0.0], [$this->asset->id, 0.0, 750.0]], $this->lines($result['constraint']));
        $this->assertSame(0.0, $ledger->balance($contract->maid_id));
    }

    public function test_agent_recovery_uses_original_supplier_account_and_can_be_reversed_once(): void
    {
        [$contract] = $this->arrivedContractWithInvoice(600);
        $ledger = app(ShelterCostLedgerService::class);
        $this->capitalize($ledger, $contract, 2250);
        $contract->update(['status' => 'shelter']);
        $receipt = ShelterCostEntry::query()->where('maid_id', $contract->maid_id)->firstOrFail();
        $contract->maid->update(['office_id' => null]);
        $service = app(\App\Services\ingaz\ShelterAgentCostRecoveryService::class);
        $recovery = $service->recover($contract, $receipt->id, now()->toDateString(), 'مطالبة', (string) \Illuminate\Support\Str::uuid());
        $this->assertSame((int) $this->office->id, (int) $recovery->office_account_id);
        $reversed = $service->reverse($contract, $recovery->id, 'إلغاء المطالبة');
        $this->assertSame(2250.0, $ledger->balance($contract->maid_id));
        $this->assertSame([[$this->asset->id, 2250.0, 0.0], [$this->office->id, 0.0, 2250.0]], $this->lines($reversed->reversalConstraint));
        $this->assertSame($reversed->reversal_constraint_id, $service->reverse($contract, $recovery->id, 'إلغاء المطالبة')->reversal_constraint_id);
        $this->assertSame(0.0, $service->summary($contract)['recovered']);
    }

    public function test_agent_recovery_refuses_missing_cost_and_reversed_supplier(): void
    {
        [$contract, $invoice] = $this->arrivedContractWithInvoice(600);
        $contract->update(['status' => 'shelter']);
        $service = app(\App\Services\ingaz\ShelterAgentCostRecoveryService::class);
        try {
            $service->recover($contract, 99999999, now()->toDateString(), 'مطالبة', (string) \Illuminate\Support\Str::uuid());
            $this->fail('Unposted cost must be refused');
        } catch (\RuntimeException $e) { $this->assertStringContainsString('لا تكلفة', $e->getMessage()); }
        $this->capitalize(app(ShelterCostLedgerService::class), $contract, 2250);
        $receipt = ShelterCostEntry::query()->where('maid_id', $contract->maid_id)->firstOrFail();
        $invoice->update(['external_office_cost_reversal_constraint_id' => $invoice->external_office_cost_constraint_id]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('تمت تسوية');
        $service->recover($contract->fresh(), $receipt->id, now()->toDateString(), 'مطالبة', (string) \Illuminate\Support\Str::uuid());
    }

    public function test_agent_recovery_reversal_is_refused_after_sale_even_when_release_amount_is_zero(): void
    {
        [$contract] = $this->arrivedContractWithInvoice(600);
        $ledger = app(ShelterCostLedgerService::class);
        $this->capitalize($ledger, $contract, 2250);
        $contract->update(['status' => 'shelter']);
        $receipt = ShelterCostEntry::query()->where('maid_id', $contract->maid_id)->firstOrFail();
        $service = app(\App\Services\ingaz\ShelterAgentCostRecoveryService::class);
        $recovery = $service->recover($contract, $receipt->id, now()->toDateString(), 'مطالبة', (string) \Illuminate\Support\Str::uuid());
        $ledger->releaseToSale($contract, now()->toDateString());
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('لا يمكن العكس بعد خروج');
        $service->reverse($contract, $recovery->id, 'خطأ');
    }

    public function test_sold_original_cost_cannot_be_recovered_from_later_living_balance(): void
    {
        [$contract] = $this->arrivedContractWithInvoice(600);
        $ledger = app(ShelterCostLedgerService::class);
        $this->capitalize($ledger, $contract, 2250);
        $ledger->releaseToSale($contract, now()->toDateString());
        $receipt = ShelterCostEntry::query()->where('maid_id', $contract->maid_id)->firstOrFail();
        $contract->update(['status' => 'shelter']);
        $service = app(\App\Services\ingaz\ShelterAgentCostRecoveryService::class);
        $this->assertSame(0.0, $service->summary($contract)['available']);
        $this->expectException(\RuntimeException::class);
        $service->recover($contract, $receipt->id, now()->toDateString(), 'مطالبة', (string) \Illuminate\Support\Str::uuid());
    }

    public function test_agent_recovery_requires_its_own_permission_and_renders_form(): void
    {
        [$contract] = $this->arrivedContractWithInvoice(600);
        $this->capitalize(app(ShelterCostLedgerService::class), $contract, 2250);
        $contract->update(['status' => 'shelter']);
        $this->grantShelterPermissions();
        $receipt = ShelterCostEntry::query()->where('maid_id', $contract->maid_id)->firstOrFail();
        $data = ['receipt_entry_id' => $receipt->id, 'date' => now()->toDateString(), 'reason' => 'مطالبة', 'request_key' => (string) \Illuminate\Support\Str::uuid()];
        $this->get(route('ShelterContract.show', $contract->id))->assertOk()
            ->assertSee('id="agent-cost-recovery"', false)
            ->assertSee('يلزم منح المستخدم صلاحية')
            ->assertDontSee('name="request_key"', false);
        $this->postJson(route('ShelterContract.agentRecovery.store', $contract->id), $data)->assertForbidden();
        $perm = \App\Models\Permission::withoutGlobalScopes()->updateOrCreate(['name' => \App\PermKeys::SHELTER_AGENT_COST_RECOVERY], ['display_name' => 'استرداد الوكيل', 'guard_name' => 'admin']);
        $this->admin->givePermissionTo($perm);
        $this->get(route('ShelterContract.show', $contract->id))->assertOk()->assertSee('استرداد تكلفة الوكيل');
        $this->post(route('ShelterContract.agentRecovery.store', $contract->id), $data)->assertRedirect();
        $this->assertSame(0.0, app(ShelterCostLedgerService::class)->balance($contract->maid_id));
    }

    public function test_deportation_splits_original_cost_and_living_expenses(): void
    {
        [$contract] = $this->arrivedContractWithInvoice(600);
        $ledger = app(ShelterCostLedgerService::class);
        $this->capitalize($ledger, $contract, 2250);
        $loss = $this->createAccount('خسائر إيواء للتقسيم', 'debtor');
        ConfirmSetting::query()->create(['title' => ShelterAccountingSettings::DEPORTATION_LOSS_ACCOUNT, 'value' => $loss->id]);
        $journal = $this->storeConstraint('إعاشة مثبتة', [[$this->asset->id, 'debtor', 750, 0], [$loss->id, 'creditor', 0, 750]]);
        ShelterCostEntry::query()->create(['maid_id' => $contract->maid_id, 'contract_id' => $contract->id,
            'kind' => ShelterCostEntry::KIND_LIVING_EXPENSE, 'amount' => 750, 'constraint_id' => $journal->id]);
        $procedure = DeportationProcedure::query()->create(['contract_id' => $contract->id, 'maid_id' => $contract->maid_id,
            'status' => 'in_progress', 'started_at' => now(), 'created_by' => $this->admin->id]);
        $result = $ledger->releaseToDeportation($procedure, now()->toDateString());
        $this->assertSame([[$this->office->id, 2250.0, 0.0], [$this->asset->id, 0.0, 2250.0],
            [$loss->id, 750.0, 0.0], [$this->asset->id, 0.0, 750.0]], $this->lines($result['constraint']));
    }

    public function test_recovery_then_trial_and_return_preserve_only_remaining_living_cost(): void
    {
        [$contract] = $this->arrivedContractWithInvoice(600);
        $ledger = app(ShelterCostLedgerService::class);
        $this->capitalize($ledger, $contract, 2250);
        $receipt = ShelterCostEntry::query()->where('maid_id', $contract->maid_id)->firstOrFail();
        $contract->update(['status' => 'shelter']);
        $trialAccount = $this->createAccount('إيواء التجربة اختبار', 'debtor');
        ConfirmSetting::query()->create(['title' => ShelterAccountingSettings::TRIAL_PREPAID_ACCOUNT, 'value' => $trialAccount->id]);
        $journal = $this->storeConstraint('إعاشة مثبتة', [[$this->asset->id, 'debtor', 750, 0], [$this->office->id, 'creditor', 0, 750]]);
        ShelterCostEntry::query()->create(['maid_id' => $contract->maid_id, 'contract_id' => $contract->id,
            'kind' => ShelterCostEntry::KIND_LIVING_EXPENSE, 'amount' => 750, 'constraint_id' => $journal->id]);
        $service = app(\App\Services\ingaz\ShelterAgentCostRecoveryService::class);
        $recovery = $service->recover($contract, $receipt->id, now()->toDateString(), 'مطالبة', (string) \Illuminate\Support\Str::uuid());
        $transfer = \App\Models\SponsorshipTransfer::query()->create(['contract_id' => $contract->id,
            'old_sponsor_id' => $contract->user_id, 'new_sponsor_id' => $contract->user_id,
            'trial_days' => 5, 'trial_start_date' => now()->toDateString(), 'contract_value' => 5000, 'paid_amount' => 0,
            'remaining_amount' => 5000, 'status' => 'trial']);
        $this->assertSame(750.0, $ledger->releaseToTrial($transfer, now()->toDateString())['amount']);
        $this->assertSame(750.0, $ledger->trialReleasedAmount($transfer));
        $this->assertSame(0.0, $service->summary($contract)['available']);
        $this->assertSame(750.0, $ledger->returnFromTrial($transfer, now()->toDateString())['amount']);
        $summary = $service->summary($contract);
        $this->assertSame([], $summary['issues']);
        $this->assertSame(0.0, $summary['available']);
        $this->assertSame(750.0, $summary['living']);
        $this->expectException(\RuntimeException::class);
        $service->reverse($contract, $recovery->id, 'عكس بعد تجربة فاشلة');
    }

    public function test_recovery_refuses_a_second_request_and_blocks_supplier_reversal(): void
    {
        [$contract, $invoice] = $this->arrivedContractWithInvoice(600);
        $ledger = app(ShelterCostLedgerService::class);
        $this->capitalize($ledger, $contract, 2250);
        $receipt = ShelterCostEntry::query()->where('maid_id', $contract->maid_id)->firstOrFail();
        $contract->update(['status' => 'shelter']);
        $service = app(\App\Services\ingaz\ShelterAgentCostRecoveryService::class);
        $service->recover($contract, $receipt->id, now()->toDateString(), 'مطالبة', (string) \Illuminate\Support\Str::uuid());
        try {
            $service->recover($contract, $receipt->id, now()->toDateString(), 'تكرار', (string) \Illuminate\Support\Str::uuid());
            $this->fail('Duplicate recovery must fail');
        } catch (\RuntimeException $e) { $this->assertStringContainsString('لا تكلفة', $e->getMessage()); }
        $this->assertSame(1, \App\Models\ShelterAgentCostRecovery::query()->where('contract_id', $contract->id)->count());
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('سبق استرداد');
        app(\App\Services\ingaz\RecruitmentTripAccountingService::class)->reverseExternalOfficeCostEntries($contract, $invoice, now()->toDateString(), 'عكس');
    }

    public function test_legacy_receipt_uses_supplier_journal_and_never_todays_office(): void
    {
        [$contract] = $this->arrivedContractWithInvoice(600);
        $ledger = app(ShelterCostLedgerService::class);
        $this->capitalize($ledger, $contract, 2250);
        $receipt = ShelterCostEntry::query()->where('maid_id', $contract->maid_id)->firstOrFail();
        $receipt->update(['office_account_id' => null, 'supplier_constraint_id' => null, 'asset_account_id' => null]);
        $contract->update(['status' => 'shelter', 'type' => 'maid']);
        $contract->maid->update(['office_id' => null]);
        $service = app(\App\Services\ingaz\ShelterAgentCostRecoveryService::class);
        $recovery = $service->recover($contract, $receipt->id, now()->toDateString(), 'مطالبة قديمة', (string) \Illuminate\Support\Str::uuid());
        $this->assertSame((int) $this->office->id, (int) $recovery->office_account_id);
        $this->assertNotNull($receipt->fresh()->supplier_constraint_id);
    }

    public function test_history_rebuild_does_not_capitalize_a_period_expense_using_todays_setting(): void
    {
        [$contract] = $this->arrivedContractWithInvoice();
        $contract->update(['status' => 'shelter']);
        ContractInfo::query()->updateOrCreate(['contract_id' => $contract->id], ['shelter_id' => 1, 'is_outside_shelter' => false]);
        $toggle = ConfirmSetting::query()->create(['title' => ShelterAccountingSettings::LIVING_EXPENSE_CAPITALIZE, 'value' => 'off']);
        $bank = $this->createAccount('بنك إعاشة قديمة', 'debtor', \Nami\Finance\Enums\AccountTypesEnum::Bank->value);
        $expenseAccount = $this->createAccount('مصروف إعاشة قديم', 'debtor');
        app(\App\Services\ingaz\ShelterLivingExpenseAccountingService::class)->create([
            'contract_id' => $contract->id, 'expense_date' => now()->toDateString(), 'title' => 'إعاشة', 'total_amount' => 150,
            'expense_account_id' => $expenseAccount->id, 'payment_source_type' => 'bank', 'payment_account_id' => $bank->id,
        ]);
        $toggle->update(['value' => 'on']);
        $this->artisan('finance:shelter-ledger-reconcile', ['--apply' => true, '--maid' => $contract->maid_id])->assertExitCode(0);
        $this->assertSame(0.0, app(ShelterCostLedgerService::class)->balance($contract->maid_id));
    }

    public function test_audit_blocks_an_existing_legacy_living_row_that_was_never_an_asset(): void
    {
        [$contract] = $this->arrivedContractWithInvoice(600);
        $this->capitalize(app(ShelterCostLedgerService::class), $contract, 2250);
        $expense = $this->createAccount('مصروف فترة', 'debtor');
        $journal = $this->storeConstraint('إعاشة قديمة كمصروف', [[$expense->id, 'debtor', 500, 0], [$this->office->id, 'creditor', 0, 500]]);
        ShelterCostEntry::query()->create(['maid_id' => $contract->maid_id, 'contract_id' => $contract->id,
            'kind' => ShelterCostEntry::KIND_LIVING_EXPENSE, 'amount' => 500, 'constraint_id' => $journal->id]);
        $summary = app(\App\Services\ingaz\ShelterAgentCostRecoveryService::class)->summary($contract);
        $this->assertNotEmpty($summary['issues']);
        $this->assertStringContainsString('لا تطابق', implode(' ', $summary['issues']));
    }

    // ── helpers ────────────────────────────────────────────────────────────────

    public function test_completed_contract_admission_automatically_makes_supplier_cost_recoverable(): void
    {
        [$contract, $invoice] = $this->arrivedContractWithInvoice(1400);
        $contract->update(['status' => ContractStatusEnum::Completed->value]);
        $contract->info->update(['is_outside_shelter' => true, 'outside_shelter_at' => now()]);
        $request = \App\Http\Requests\ingaz\ShelterContactStore::create('/', 'POST', [
            'contract_id' => $contract->id, 'maid_date_back' => now()->toDateString(),
            'arrival_date' => now()->subDays(30)->toDateString(), 'receipt_source' => 'client',
            'duration_from_enter' => 0,
        ]);
        app(\App\Http\Controllers\ingaz\ShelterContractController::class)->store($request);
        $this->assertSame('shelter', $contract->fresh()->status);
        $this->assertSame(
            ContractStatusEnum::Completed->value,
            $contract->contract_status_logs()->latest('id')->value('previous_status')
        );
        $this->assertFalse((bool) $contract->info->fresh()->is_outside_shelter);
        $this->assertTrue(app(\App\Services\commissions\RecruitmentCommissionService::class)->isEligible($contract->fresh()));
        $summary = app(\App\Services\ingaz\ShelterAgentCostRecoveryService::class)->summary($contract->fresh());
        $this->assertEquals(5250, $summary['available']);
        $this->assertEmpty($summary['issues']);
        $this->assertTrue(collect($summary['lots'])->first()['valid']);
        $this->assertEquals($invoice->external_office_cost_constraint_id,
            ShelterCostEntry::where('maid_id', $contract->maid_id)->sole()->supplier_constraint_id);
        // A retry cannot add another admission or cost.
        app(\App\Http\Controllers\ingaz\ShelterContractController::class)->store($request);
        $this->assertSame(1, ShelterCostEntry::where('maid_id', $contract->maid_id)->count());
    }

    public function test_cancelled_after_arrival_contract_can_be_admitted_to_shelter(): void
    {
        [$contract] = $this->arrivedContractWithInvoice();
        $contract->update(['status' => ContractStatusEnum::Back->value]);
        $request = \App\Http\Requests\ingaz\ShelterContactStore::create('/', 'POST', [
            'contract_id' => $contract->id,
            'maid_date_back' => now()->toDateString(),
            'arrival_date' => now()->subDays(3)->toDateString(),
            'receipt_source' => 'client',
            'duration_from_enter' => 0,
        ]);

        app(\App\Http\Controllers\ingaz\ShelterContractController::class)->store($request);

        $this->assertSame(ContractStatusEnum::Shelter->value, $contract->fresh()->status);
        $this->assertSame(ContractStatusEnum::Back->value,
            $contract->contract_status_logs()->latest('id')->value('previous_status'));
        $this->assertFalse(app(\App\Services\commissions\RecruitmentCommissionService::class)->isEligible($contract->fresh()));
    }

    public function test_admission_is_saved_when_supplier_cost_cannot_be_posted(): void
    {
        [$contract] = $this->arrivedContractWithInvoice(1400);
        $contract->update(['status' => ContractStatusEnum::Completed->value]);
        $request = \App\Http\Requests\ingaz\ShelterContactStore::create('/', 'POST', [
            'contract_id' => $contract->id, 'maid_date_back' => now()->subDays(1)->toDateString(),
            'arrival_date' => now()->subDays(30)->toDateString(), 'receipt_source' => 'client',
            'duration_from_enter' => 0,
        ]);
        $response = app(\App\Http\Controllers\ingaz\ShelterContractController::class)->store($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue((bool) $response->getData(true)['data']['accounting_reconciliation_pending']);
        $this->assertSame(ContractStatusEnum::Shelter->value, $contract->fresh()->status);
        $this->assertSame(now()->subDays(1)->toDateString(), $contract->info->fresh()->maid_date_back);
        $this->assertFalse(ShelterCostEntry::where('maid_id', $contract->maid_id)->exists());
    }

    public function test_legacy_supplier_cost_is_linked_on_admission_without_a_second_supplier_charge(): void
    {
        [$contract, $invoice] = $this->arrivedContractWithInvoice(1400);
        $supplierId = $invoice->external_office_cost_constraint_id;
        $number = 900000 + $contract->id;
        $contract->update(['status' => ContractStatusEnum::Completed->value,
            'arabia_order_number' => $number, 'arabia_supplier_invoice_number' => $number]);
        Constraint::findOrFail($supplierId)->update(['statement' => 'فاتورة رقم: ' . $number]);
        ConstraintDetails::where('constraint_id', $supplierId)->where('creditor_value', '>', 0)
            ->update(['statement' => "إغلاق طلب:  {$number}, فاتورة رقم: {$number} العميل: اختبار"]);
        $invoice->update(['external_office_cost_constraint_id' => null]);
        $before = Constraint::count();
        $request = \App\Http\Requests\ingaz\ShelterContactStore::create('/', 'POST', [
            'contract_id' => $contract->id, 'maid_date_back' => now()->toDateString(),
            'arrival_date' => now()->subDays(30)->toDateString(), 'receipt_source' => 'client', 'duration_from_enter' => 0,
        ]);
        app(\App\Http\Controllers\ingaz\ShelterContractController::class)->store($request);
        $this->assertEquals($supplierId, $invoice->fresh()->external_office_cost_constraint_id);
        $this->assertSame($before + 1, Constraint::count());
        $this->assertEquals(5250, app(\App\Services\ingaz\ShelterAgentCostRecoveryService::class)->summary($contract->fresh())['available']);
    }

    public function test_legacy_supplier_preview_does_not_link_and_ambiguous_journals_are_rejected(): void
    {
        [$contract, $invoice] = $this->arrivedContractWithInvoice(1400);
        $number = 900000 + $contract->id;
        $contract->update(['status' => 'shelter', 'arabia_order_number' => $number, 'arabia_supplier_invoice_number' => $number]);
        $contract->info->update(['maid_date_back' => now()->toDateString(), 'is_outside_shelter' => false]);
        $statement = "إغلاق طلب: {$number}, فاتورة رقم: {$number}";
        Constraint::findOrFail($invoice->external_office_cost_constraint_id)->update(['statement' => $statement]);
        $invoice->update(['external_office_cost_constraint_id' => null]);
        $this->artisan('finance:capitalize-missing-shelter-costs', ['contracts' => [$contract->id]])->assertExitCode(0);
        $this->assertNull($invoice->fresh()->external_office_cost_constraint_id);
        $this->storeConstraint('إلغاء ' . $statement, [
            [$this->office->id, 'debtor', 5250, 0], [$this->costOfSales->id, 'creditor', 0, 5250],
        ]);
        $this->artisan('finance:capitalize-missing-shelter-costs', ['contracts' => [$contract->id],
            '--apply' => true, '--expected-database' => DB::connection()->getDatabaseName()])->assertExitCode(1);
        $this->assertNull($invoice->fresh()->external_office_cost_constraint_id);
        $this->assertFalse(ShelterCostEntry::where('maid_id', $contract->maid_id)->exists());
    }

    public function test_missing_shelter_cost_command_previews_then_posts_once(): void
    {
        [$contract, $invoice] = $this->arrivedContractWithInvoice(1400);
        $contract->update(['status' => 'shelter']);
        $contract->info->update(['maid_date_back' => now()->toDateString(), 'is_outside_shelter' => false]);
        $arguments = ['contracts' => [$contract->id]];
        $before = Constraint::count();
        $this->artisan('finance:capitalize-missing-shelter-costs', $arguments)->assertExitCode(0);
        $this->assertSame($before, Constraint::count());
        $this->assertFalse(ShelterCostEntry::where('maid_id', $contract->maid_id)->exists());

        $arguments += ['--apply' => true, '--expected-database' => DB::connection()->getDatabaseName()];
        $this->artisan('finance:capitalize-missing-shelter-costs', $arguments)->assertExitCode(0);
        $receipt = ShelterCostEntry::where('maid_id', $contract->maid_id)->sole();
        $this->assertEquals(5250, $receipt->amount);
        $this->assertEquals($invoice->external_office_cost_constraint_id, $receipt->supplier_constraint_id);
        $this->assertSame($before + 1, Constraint::count());
        $this->artisan('finance:capitalize-missing-shelter-costs', $arguments)->assertExitCode(0);
        $this->assertSame($before + 1, Constraint::count());
        $this->assertSame(1, ShelterCostEntry::where('maid_id', $contract->maid_id)->count());
    }

    public function test_missing_shelter_cost_command_refuses_wrong_database_and_reversed_supplier(): void
    {
        [$contract, $invoice] = $this->arrivedContractWithInvoice(1400);
        $contract->update(['status' => 'shelter']);
        $contract->info->update(['maid_date_back' => now()->toDateString(), 'is_outside_shelter' => false]);
        $this->artisan('finance:capitalize-missing-shelter-costs', [
            'contracts' => [$contract->id], '--apply' => true, '--expected-database' => 'wrong_database',
        ])->assertExitCode(1);
        $invoice->update(['external_office_cost_reversal_constraint_id' => $invoice->external_office_cost_constraint_id]);
        $this->artisan('finance:capitalize-missing-shelter-costs', [
            'contracts' => [$contract->id], '--apply' => true, '--expected-database' => DB::connection()->getDatabaseName(),
        ])->assertExitCode(1);
        $this->assertFalse(ShelterCostEntry::where('maid_id', $contract->maid_id)->exists());
    }

    public function test_missing_shelter_cost_command_refuses_unlinked_asset_journal(): void
    {
        [$contract] = $this->arrivedContractWithInvoice(1400);
        $contract->update(['status' => 'shelter']);
        $contract->info->update(['maid_date_back' => now()->toDateString(), 'is_outside_shelter' => false]);
        $this->storeConstraint('تكلفة إيواء قديمة للعقد #' . $contract->id, [
            [$this->asset->id, 'debtor', 5250, 0], [$this->costReturns->id, 'creditor', 0, 5250],
        ]);
        $before = Constraint::count();
        $this->artisan('finance:capitalize-missing-shelter-costs', [
            'contracts' => [$contract->id], '--apply' => true, '--expected-database' => DB::connection()->getDatabaseName(),
        ])->assertExitCode(1);
        $this->assertSame($before, Constraint::count());
        $this->assertFalse(ShelterCostEntry::where('maid_id', $contract->maid_id)->exists());
    }

    /** @dataProvider postArrivalRecoveryAmounts */
    public function test_post_arrival_office_charge_reduces_cost_before_deportation(float $recovered, float $remaining): void
    {
        [$contract] = $this->arrivedContractWithInvoice(600);
        $ledger = app(ShelterCostLedgerService::class);
        $this->capitalize($ledger, $contract, 2250);
        $contract->update(['status' => 'shelter']);
        $settlement = $this->postArrivalOfficeRefund($contract, $recovered);
        $refundService = app(\App\Services\ingaz\PostArrivalRefundService::class);
        $refundService->post($settlement);
        $refundService->post($settlement->fresh());
        $this->assertSame($remaining, $ledger->balance($contract->maid_id));
        $this->assertSame(1, ShelterCostEntry::where('maid_id', $contract->maid_id)->where('kind', ShelterCostEntry::KIND_POST_REFUND_RECOVERY)->count());
        $this->assertSame([], app(\App\Services\ingaz\ShelterAgentCostRecoveryService::class)->summary($contract)['issues']);
        $procedure = DeportationProcedure::create(['contract_id' => $contract->id, 'maid_id' => $contract->maid_id,
            'status' => 'in_progress', 'procedures_confirmed' => true, 'trip_scheduled' => true, 'created_by' => $this->admin->id]);
        $result = app(DeportationCompletionService::class)->complete($procedure);
        $this->assertSame($remaining, $result['amount']);
        $this->assertSame(0.0, $ledger->balance($contract->maid_id));
        $this->assertSame($remaining > 0, $result['constraint'] !== null);
        $refundJournal = $settlement->fresh()->lines->first()->constraint_id;
        $journalIds = array_filter([$refundJournal, $result['constraint']?->id]);
        $this->assertSame(2250.0, (float) ConstraintDetails::whereIn('constraint_id', $journalIds)->where('account_id', $this->office->id)->sum('debtor_value'));
    }

    public function postArrivalRecoveryAmounts(): array
    {
        return ['full' => [2250.0, 0.0], 'partial' => [1000.0, 1250.0]];
    }

    public function test_old_post_arrival_charge_without_ledger_entry_blocks_duplicate_deportation(): void
    {
        [$contract] = $this->arrivedContractWithInvoice(600);
        $ledger = app(ShelterCostLedgerService::class);
        $this->capitalize($ledger, $contract, 2250);
        $contract->update(['status' => 'shelter']);
        $settlement = $this->postArrivalOfficeRefund($contract, 2250);
        app(\App\Services\ingaz\PostArrivalRefundService::class)->post($settlement);
        ShelterCostEntry::where('maid_id', $contract->maid_id)->where('kind', ShelterCostEntry::KIND_POST_REFUND_RECOVERY)->delete();
        $procedure = DeportationProcedure::create(['contract_id' => $contract->id, 'maid_id' => $contract->maid_id,
            'status' => 'in_progress', 'procedures_confirmed' => true, 'trip_scheduled' => true, 'created_by' => $this->admin->id]);
        $journalCount = Constraint::count();
        try {
            app(DeportationCompletionService::class)->complete($procedure);
            $this->fail('Legacy charge must block duplicate recovery');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('منع التكرار', $e->getMessage());
        }
        $this->assertSame($journalCount, Constraint::count());
        $this->assertSame('in_progress', $procedure->fresh()->status);
        $this->assertSame('shelter', $contract->fresh()->status);
    }

    public function test_post_arrival_charge_cannot_be_posted_after_deportation_settles_cost(): void
    {
        [$contract] = $this->arrivedContractWithInvoice(600);
        $ledger = app(ShelterCostLedgerService::class);
        $this->capitalize($ledger, $contract, 2250);
        $contract->update(['status' => 'shelter']);
        $settlement = $this->postArrivalOfficeRefund($contract, 2250);
        $procedure = DeportationProcedure::create(['contract_id' => $contract->id, 'maid_id' => $contract->maid_id]);
        $ledger->releaseToDeportation($procedure, now()->toDateString());
        $journalCount = Constraint::count();
        try {
            app(\App\Services\ingaz\PostArrivalRefundService::class)->post($settlement);
            $this->fail('Settled cost must not be recovered again');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('office_charge', $e->errors());
        }
        $this->assertSame($journalCount, Constraint::count());
        $this->assertSame('draft', $settlement->fresh()->status);
    }

    public function test_reversing_post_arrival_charge_restores_cost_once_and_is_blocked_after_exit(): void
    {
        [$contract] = $this->arrivedContractWithInvoice(600);
        $ledger = app(ShelterCostLedgerService::class);
        $this->capitalize($ledger, $contract, 2250);
        $contract->update(['status' => 'shelter']);
        $settlement = $this->postArrivalOfficeRefund($contract, 2250);
        $service = app(\App\Services\ingaz\PostArrivalRefundService::class);
        $service->post($settlement);
        $line = $settlement->fresh()->lines->first();
        $service->reverseLine($line, 'Cancel claim');
        $service->reverseLine($line->fresh(), 'Retry');
        $this->assertSame(2250.0, $ledger->balance($contract->maid_id));
        $this->assertSame([], app(\App\Services\ingaz\ShelterAgentCostRecoveryService::class)->summary($contract)['issues']);
        $second = $this->postArrivalOfficeRefund($contract, 2250);
        $service->post($second);
        $procedure = DeportationProcedure::create(['contract_id' => $contract->id, 'maid_id' => $contract->maid_id]);
        $ledger->releaseToDeportation($procedure, now()->toDateString());
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->reverseLine($second->fresh()->lines->first(), 'After exit');
    }

    private function postArrivalOfficeRefund(Contract $contract, float $amount): \App\Models\ContractReturnSettlement
    {
        $receipt = ShelterCostEntry::where('contract_id', $contract->id)->where('kind', ShelterCostEntry::KIND_CAPITALIZE_OFFICE_COST)->firstOrFail();
        $settlement = \App\Models\ContractReturnSettlement::create(['contract_id' => $contract->id,
            'invoice_id' => $contract->invoice->id, 'settlement_date' => now()->toDateString(), 'status' => 'draft',
            'reason' => 'Test office charge', 'created_by_id' => $this->admin->id]);
        $settlement->lines()->create(['type' => 'office_charge', 'status' => 'draft', 'proposed_amount' => $amount,
            'approved_amount' => $amount, 'debit_account_id' => $this->office->id, 'credit_account_id' => $this->asset->id,
            'settlement_method' => 'claim', 'metadata' => ['shelter_receipt_entry_id' => $receipt->id]]);
        return $settlement;
    }

    private function capitalize(ShelterCostLedgerService $ledger, Contract $contract, float $amount): void
    {
        $source = ContractReturnRequest::query()->create([
            'contract_id' => $contract->id, 'user_id' => $contract->user_id, 'status' => 'approved', 'notes' => 'x', 'processed_at' => now(),
        ]);
        $ledger->capitalizeOfficeCost($contract, $amount, now()->toDateString(), $source, 'اختبار استلام');
        $this->assertSame($amount, $ledger->balance($contract->maid_id));
    }

    /**
     * عقد وصلت عاملته قبل 30 يوماً، بفاتورة وقيد فاتورة وقيد إيراد وصول (الوضع الافتراضي) وفاتورة مورد.
     * @return array{0: Contract, 1: Invoice}
     */
    private function arrivedContractWithInvoice(float $officeCostUsd = 0): array
    {
        $user = ClientUser::query()->create([
            'name' => 'العميل اكس', 'email' => 'client' . uniqid() . '@example.test',
            'phone' => '05' . random_int(10000000, 99999999), 'password' => bcrypt('secret'),
            'account_id' => $this->createAccount('العميل اكس', 'debtor')->id,
        ]);
        $maid = Maid::query()->create([
            'name' => 'عاملة اختبار', 'passport_number' => 'P' . uniqid(), 'nationality_id' => $this->nationality->id,
            'status' => 'contracted', 'available' => false, 'maid_accommodation_type' => 2,
        ]);
        $maid->forceFill(['office_id' => $this->createOfficeWithAccount()])->save();

        $contract = Contract::query()->create([
            'status' => ContractStatusEnum::Arrival->value, 'type' => 'recruitment', 'user_id' => $user->id,
            'maid_id' => $maid->id, 'marketer_id' => $this->admin->id, 'required_maid_nationality_id' => $this->nationality->id,
        ]);
        ContractInfo::query()->create(['contract_id' => $contract->id, 'arrival_date' => now()->subDays(30)->toDateString()]);

        if ($officeCostUsd > 0) {
            DB::table('contract_external_office_dispatches')->insert([
                'contract_id' => $contract->id,
                'recruitment_office_id' => $maid->office_id,
                'office_contract_cost_usd' => $officeCostUsd,
                'dispatch_date' => now()->subDays(60)->toDateString(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $client = $user->account_id;
        $invoiceConstraint = $this->storeConstraint('قيد فاتورة اختبار', [
            [$this->liability->id, 'creditor', 0, 285], [$client, 'debtor', 285, 0],
            [$this->liability->id, 'creditor', 0, 3653], [$client, 'debtor', 3653, 0],
            [$this->vat->id, 'creditor', 0, 42.75], [$client, 'debtor', 42.75, 0],
        ]);
        $arrivalConstraint = $this->storeConstraint('ايراد طلب استقدام اختبار', [
            [$this->liability->id, 'debtor', 3938, 0], [$this->sales->id, 'creditor', 0, 3938],
        ]);
        $supplier = $officeCostUsd > 0 ? $this->storeConstraint('فاتورة مورد اختبار', [
            [$this->costOfSales->id, 'debtor', $officeCostUsd * 3.75, 0], [$this->office->id, 'creditor', 0, $officeCostUsd * 3.75],
        ]) : null;

        $invoice = Invoice::query()->create([
            'contract_id' => $contract->id,
            'direct_recruitment_cost' => 285, 'direct_recruitment_count' => 1,
            'external_recruitment_cost' => 3653, 'external_recruitment_count' => 1,
            'government_cost' => 0, 'government_cost_count' => 0, 'internal_ticket_cost' => 0, 'internal_ticket_count' => 0,
            'subtotal' => 3938, 'discount' => 0, 'vat_amount' => 42.75, 'total_due' => 3980.75,
            'is_paid' => true, 'send_to_zatca' => false,
            'revenue_recognition_mode' => 'on_arrival',
            'invoice_constraint_id' => $invoiceConstraint->id,
            'arrival_revenue_constraint_id' => $arrivalConstraint->id,
            'external_office_cost_constraint_id' => $supplier?->id,
        ]);

        return [$contract->fresh(['user', 'maid', 'info']), $invoice];
    }

    private function createOfficeWithAccount(): int
    {
        $columns = Schema::getColumnListing('recruitment_offices');
        $row = ['nationality_id' => $this->nationality->id, 'account_id' => $this->office->id, 'created_at' => now(), 'updated_at' => now()];
        foreach (['name' => 'مكتب اختبار', 'title' => 'مكتب اختبار', 'status' => 1] as $col => $val) {
            if (in_array($col, $columns, true)) {
                $row[$col] = $val;
            }
        }

        return (int) DB::table('recruitment_offices')->insertGetId($row);
    }

    private function grantShelterPermissions(): void
    {
        $perm = \App\Models\Permission::withoutGlobalScopes()->updateOrCreate(
            ['name' => \App\PermKeys::SHELTER_CONTRACT_CONTROL],
            ['display_name' => 'shelter control', 'guard_name' => 'admin']
        );
        $this->admin->givePermissionTo($perm);
    }

    private function ensureActiveFiscalYear(): void
    {
        $exists = FnYear::query()->whereDate('from_date', '<=', now())->whereDate('to_date', '>=', now())->where('status', 1)->exists();
        if (!$exists) {
            FnYear::query()->create([
                'title' => 'اختبار ' . now()->year, 'from_date' => now()->startOfYear()->toDateString(),
                'to_date' => now()->endOfYear()->toDateString(), 'status' => 1, 'created_by_id' => $this->admin->id,
            ]);
        }
    }

    private function ensureConstraintNumberSequence(): void
    {
        if (DB::table('fn_constraint_code_sequences')->where('id', 1)->exists()) {
            return;
        }
        DB::table('fn_constraint_code_sequences')->insert([
            'id' => 1, 'last_code' => (int) DB::table('fn_constraints')->max('code'), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function createAccount(string $name, string $status, int $type = 1): Account
    {
        $code = ((int) DB::table('fn_accounts')->max('code')) + random_int(100, 999);

        return Account::query()->create([
            'name' => $name, 'code' => $code, 'parent_id' => null, 'level' => 1, 'operation_type' => 1,
            'status' => $status, 'statement_type' => 'income_statement', 'type' => $type, 'created_by_id' => $this->admin->id,
        ]);
    }

    private function storeConstraint(string $statement, array $rows): Constraint
    {
        $service = app(ConstraintService::class);
        $year = app(\Nami\Finance\Services\YearService::class)->checkDateRange(now()->toDateString());
        $data = [
            'code' => $service->lastCode(), 'date' => now()->toDateString(), 'statement' => $statement,
            'account_id' => array_column($rows, 0), 'account_type' => array_column($rows, 1),
            'debtor_value' => array_column($rows, 2), 'creditor_value' => array_column($rows, 3),
            'statement_account' => array_fill(0, count($rows), $statement), 'cost_center_required' => 'off',
            'cost_center_id' => array_fill(0, count($rows), null), 'value' => array_sum(array_column($rows, 2)),
        ];
        $constraint = $service->storeConstraint($data, $year);
        $service->storeConstraintDetails($data['account_id'], $constraint->id, $data);

        return $constraint;
    }

    private function lines(Constraint $constraint): array
    {
        return ConstraintDetails::query()->where('constraint_id', $constraint->id)->orderBy('id')->get()
            ->map(fn (ConstraintDetails $d) => [(int) $d->account_id, (float) $d->debtor_value, (float) $d->creditor_value])->all();
    }
}
