<?php

namespace Tests\Feature\Finance;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\Permission;
use App\Models\auth\Admin;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\User as ClientUser;
use App\Support\RecruitmentCostCenters;
use App\Support\RecruitmentSupplierCostRecognition;
use App\Support\RecruitmentSupplierSettings;
use App\Services\ingaz\RecruitmentTripAccountingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Enums\CostCenterTypeEnum;
use Nami\Finance\Models\ConfirmSetting;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\ConstraintDetails;
use Nami\Finance\Models\CostCenter;
use Nami\Finance\Models\FnYear;
use Nami\Finance\Services\ConfirmSettingService;
use Tests\TestCase;

/**
 * إعدادات «قيود المشتريات والوكلاء الخارجيون».
 *
 * الشرط الأول في كل اختبار هنا: الافتراضي يساوي السلوك الحالي للنظام حرفياً، فالإعداد
 * الذي لم يُحفظ قط لا يغيّر أي قيد ولا ينشئ مركز تكلفة بشكل مختلف.
 */
class RecruitmentSupplierCycleTest extends TestCase
{
    use DatabaseTransactions;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();

        Permission::withoutGlobalScopes()->updateOrCreate(
            ['name' => 'finance_module', 'guard_name' => 'admin'],
            [
                'display_name' => 'finance_module',
                'group_name' => 'finance',
                'section_name' => 'finance',
                'is_active' => true,
            ]
        );
        $this->admin->givePermissionTo('finance_module');
        $this->actingAs($this->admin, 'admin');

        ConfirmSetting::query()->whereIn('title', array_merge(
            RecruitmentSupplierSettings::confirmSettingTitles(),
            [RecruitmentSupplierCostRecognition::SETTING_KEY, RecruitmentSupplierSettings::CONTRACT_FINANCE_SCREEN]
        ))->delete();

        $this->ensureActiveFiscalYear();
        $this->ensureConstraintNumberSequence();
    }

    public function test_every_default_matches_the_current_system_behaviour(): void
    {
        $this->assertSame(RecruitmentSupplierSettings::SOURCE_DISPATCH_MANUAL, RecruitmentSupplierSettings::costSource());
        $this->assertFalse(RecruitmentSupplierSettings::usesOfficePriceList());

        // السعر كان مثبتاً في RecruitmentTripAccountingService على 3.75.
        $this->assertSame(3.75, RecruitmentSupplierSettings::usdRate());

        $this->assertSame(RecruitmentSupplierSettings::SCOPE_CONTRACT, RecruitmentSupplierSettings::costCenterScope());
        $this->assertFalse(RecruitmentSupplierSettings::groupsCostCentersByNationality());

        $this->assertFalse(RecruitmentSupplierSettings::stampsSupplierLineCostCenter());

        $this->assertSame(RecruitmentSupplierSettings::PAYMENT_GENERAL_BOND, RecruitmentSupplierSettings::paymentMode());
        $this->assertFalse(RecruitmentSupplierSettings::usesContractLinkedPayments());

        $this->assertSame(RecruitmentSupplierSettings::MUSANED_INTERMEDIATE_TWO_STEP, RecruitmentSupplierSettings::musanedCollectionMode());
        $this->assertFalse(RecruitmentSupplierSettings::musanedUsesDirectReceipt());

        $this->assertSame(RecruitmentSupplierCostRecognition::MODE_ON_ARRIVAL, RecruitmentSupplierCostRecognition::mode());
        $this->assertFalse(RecruitmentSupplierCostRecognition::isOnContract());
    }

    public function test_unknown_values_fall_back_to_the_current_behaviour(): void
    {
        foreach ([
            RecruitmentSupplierSettings::COST_SOURCE => RecruitmentSupplierSettings::SOURCE_DISPATCH_MANUAL,
            RecruitmentSupplierSettings::COST_CENTER_SCOPE => RecruitmentSupplierSettings::SCOPE_CONTRACT,
            RecruitmentSupplierSettings::PAYMENT_MODE => RecruitmentSupplierSettings::PAYMENT_GENERAL_BOND,
            RecruitmentSupplierSettings::MUSANED_COLLECTION_MODE => RecruitmentSupplierSettings::MUSANED_INTERMEDIATE_TWO_STEP,
        ] as $key => $expected) {
            $this->saveSetting($key, 'قيمة-غير-معروفة');
            $this->assertSame($expected, RecruitmentSupplierSettings::mode($key), $key);
        }

        // سعر صرف غير صالح لا يُستخدم في أي قيد.
        foreach (['0', '-1', 'abc', ''] as $invalid) {
            $this->saveSetting(RecruitmentSupplierSettings::USD_RATE, $invalid);
            $this->assertSame(3.75, RecruitmentSupplierSettings::usdRate(), 'rate: ' . $invalid);
        }
    }

    public function test_default_mode_creates_no_entry_when_a_contract_is_created(): void
    {
        $constraintsBefore = Constraint::query()->count();

        $contract = $this->createContract($this->createNationality('اختبار الفلبين'));

        $this->assertSame($constraintsBefore, Constraint::query()->count());
        $this->assertNull($contract->fresh()->external_office_cost_constraint_id);
        $this->assertNull($contract->fresh()->supplier_cost_recognition_mode);
    }

    public function test_on_contract_mode_never_blocks_contract_creation_when_data_is_incomplete(): void
    {
        $this->saveSetting(RecruitmentSupplierCostRecognition::SETTING_KEY, RecruitmentSupplierCostRecognition::MODE_ON_CONTRACT);
        $this->saveSetting(RecruitmentSupplierSettings::COST_SOURCE, RecruitmentSupplierSettings::SOURCE_OFFICE_PRICE_LIST);

        $constraintsBefore = Constraint::query()->count();

        // عقد بلا عاملة: لا مكتب خارجي ولا سعر، فالقيد يُؤجَّل ولا يفشل الإنشاء.
        $contract = $this->createContract($this->createNationality('اختبار كينيا'));

        $this->assertSame($constraintsBefore, Constraint::query()->count());
        $this->assertNull($contract->fresh()->external_office_cost_constraint_id);
        $this->assertSame('missing_external_office', $contract->fresh()->supplier_cost_skip_reason);
    }

    public function test_saved_usd_rate_replaces_the_hardcoded_rate(): void
    {
        $this->saveSetting(RecruitmentSupplierSettings::USD_RATE, '4.1');

        $this->assertSame(4.1, RecruitmentSupplierSettings::usdRate());
    }

    public function test_on_contract_is_refused_without_a_price_source(): void
    {
        $service = app(ConfirmSettingService::class);

        try {
            $service->storeData(['data' => [
                RecruitmentSupplierCostRecognition::SETTING_KEY => [
                    'title' => RecruitmentSupplierCostRecognition::SETTING_KEY,
                    'value' => RecruitmentSupplierCostRecognition::MODE_ON_CONTRACT,
                ],
                RecruitmentSupplierSettings::COST_SOURCE => [
                    'title' => RecruitmentSupplierSettings::COST_SOURCE,
                    'value' => RecruitmentSupplierSettings::SOURCE_DISPATCH_MANUAL,
                ],
            ]]);

            $this->fail('كان يجب رفض التركيبة المستحيلة.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'data.' . RecruitmentSupplierSettings::COST_SOURCE . '.value',
                $exception->errors()
            );
        }

        // ولا شيء يُحفظ: الوضع يبقى على السلوك الحالي.
        $this->assertSame(RecruitmentSupplierCostRecognition::MODE_ON_ARRIVAL, RecruitmentSupplierCostRecognition::mode());
    }

    public function test_settings_endpoint_accepts_on_contract_with_office_price_source(): void
    {
        $response = $this->postJson(route('finance.confirm-settings.store'), [
            'data' => [
                RecruitmentSupplierCostRecognition::SETTING_KEY => [
                    'title' => RecruitmentSupplierCostRecognition::SETTING_KEY,
                    'value' => RecruitmentSupplierCostRecognition::MODE_ON_CONTRACT,
                ],
                RecruitmentSupplierSettings::COST_SOURCE => [
                    'title' => RecruitmentSupplierSettings::COST_SOURCE,
                    'value' => RecruitmentSupplierSettings::SOURCE_OFFICE_PRICE_LIST,
                ],
            ],
        ]);

        $response->assertSuccessful()->assertJsonPath('status', true);
        $this->assertSame(RecruitmentSupplierCostRecognition::MODE_ON_CONTRACT, RecruitmentSupplierCostRecognition::mode());
        $this->assertSame(RecruitmentSupplierSettings::SOURCE_OFFICE_PRICE_LIST, RecruitmentSupplierSettings::costSource());
    }

    public function test_invalid_usd_rate_is_refused_on_save(): void
    {
        $this->expectException(ValidationException::class);

        app(ConfirmSettingService::class)->storeData(['data' => [
            RecruitmentSupplierSettings::USD_RATE => [
                'title' => RecruitmentSupplierSettings::USD_RATE,
                'value' => '0',
            ],
        ]]);
    }

    public function test_cost_centers_stay_flat_by_default_and_nest_under_the_country_when_configured(): void
    {
        $root = RecruitmentCostCenters::root();

        $flat = RecruitmentCostCenters::forContract(990000001, 'اختبار الهند');
        $this->assertSame($root->id, $flat->parent_id);

        $this->saveSetting(
            RecruitmentSupplierSettings::COST_CENTER_SCOPE,
            RecruitmentSupplierSettings::SCOPE_NATIONALITY_THEN_CONTRACT
        );

        $nested = RecruitmentCostCenters::forContract(990000002, 'اختبار الهند');
        $this->assertNotSame($root->id, $nested->parent_id);

        $nationalityNode = CostCenter::query()->find($nested->parent_id);
        $this->assertSame('اختبار الهند', $nationalityNode->title);
        $this->assertSame($root->id, $nationalityNode->parent_id);
        $this->assertSame(0, (int) $nationalityNode->table_related_id);
    }

    public function test_switching_cost_center_scope_never_duplicates_an_existing_centre(): void
    {
        $existing = RecruitmentCostCenters::forContract(990000003, 'اختبار نيبال');

        $this->saveSetting(
            RecruitmentSupplierSettings::COST_CENTER_SCOPE,
            RecruitmentSupplierSettings::SCOPE_NATIONALITY_THEN_CONTRACT
        );

        $again = RecruitmentCostCenters::forContract(990000003, 'اختبار نيبال');

        $this->assertSame($existing->id, $again->id);
        $this->assertSame(1, CostCenter::query()
            ->where('table_name', CostCenterTypeEnum::General->value)
            ->where('table_related_id', 990000003)
            ->count());
    }

    public function test_musaned_collection_mode_is_read_from_the_transfer_not_the_live_setting(): void
    {
        $this->saveSetting(
            RecruitmentSupplierSettings::MUSANED_COLLECTION_MODE,
            RecruitmentSupplierSettings::MUSANED_DIRECT_RECEIPT
        );

        // تحويل قديم بلا قيمة محفوظة يبقى مقروءاً بالطريقة التي أُنشئ بها.
        $legacy = new \App\Models\MusanedTransfer();
        $this->assertSame(
            RecruitmentSupplierSettings::MUSANED_INTERMEDIATE_TWO_STEP,
            RecruitmentSupplierSettings::musanedCollectionModeForTransfer($legacy)
        );

        $fresh = new \App\Models\MusanedTransfer([
            RecruitmentSupplierSettings::MUSANED_TRANSFER_COLUMN => RecruitmentSupplierSettings::MUSANED_DIRECT_RECEIPT,
        ]);
        $this->assertSame(
            RecruitmentSupplierSettings::MUSANED_DIRECT_RECEIPT,
            RecruitmentSupplierSettings::musanedCollectionModeForTransfer($fresh)
        );
    }


    public function test_on_contract_mode_posts_the_agent_invoice_and_the_later_stages_never_duplicate_it(): void
    {
        $this->saveSetting(RecruitmentSupplierCostRecognition::SETTING_KEY, RecruitmentSupplierCostRecognition::MODE_ON_CONTRACT);
        $this->saveSetting(RecruitmentSupplierSettings::COST_SOURCE, RecruitmentSupplierSettings::SOURCE_OFFICE_PRICE_LIST);

        if (!Account::query()->where('code', 221)->exists()) {
            $this->createAccount('الوكلاء الخارجيين', 'creditor')->update(['code' => 221]);
        }

        $nationality = $this->createNationality('اختبار بنجلاديش');
        $contract = $this->createContract($nationality);
        $costAccount = $this->createAccount('تكلفة مبيعات ' . $nationality->display_name, 'debtor');
        $this->ensureServiceItemTemplate($costAccount->id);

        $officeAccount = $this->createAccount('مكتب اختبار ' . $contract->id, 'creditor');
        $office = \App\Models\office\Office::query()->create([
            'ar' => ['title' => $officeAccount->name],
            'account_id' => $officeAccount->id,
            // لا توجد إرسالية، فالمبلغ يأتي من قائمة أسعار المكاتب.
            'cost_of_dollar' => 500,
        ]);
        $contract->info()->create(['manual_maid_office_id' => $office->id]);

        $service = app(RecruitmentTripAccountingService::class);
        $result = $service->recordContractCostEntries($contract->fresh());

        $this->assertNull($result['skipped_reason']);
        $this->assertTrue($result['created_external_office_cost_constraint']);
        $this->assertTrue($result['created_supplier_purchase']);

        $constraint = Constraint::query()->findOrFail($result['external_office_cost_constraint_id']);
        $this->assertSame([
            [$costAccount->id, 1875.0, 0.0],
            [$officeAccount->id, 0.0, 1875.0],
        ], $this->lines($constraint));

        $fresh = $contract->fresh();
        $this->assertSame((int) $constraint->id, (int) $fresh->external_office_cost_constraint_id);
        $this->assertSame((int) $result['supplier_purchase_id'], (int) $fresh->external_office_purchase_id);
        $this->assertSame(RecruitmentSupplierCostRecognition::MODE_ON_CONTRACT, $fresh->supplier_cost_recognition_mode);
        $this->assertNull($fresh->supplier_cost_skip_reason);

        // استدعاء ثانٍ لا ينشئ قيداً إضافياً.
        $again = $service->recordContractCostEntries($contract->fresh());
        $this->assertSame('already_recorded', $again['skipped_reason']);

        // ولا مرحلة الوصول: الرابط يُنسخ على الفاتورة فيُقرأ كقيد قائم.
        $invoice = $this->createInvoice($contract);
        $arrival = $service->recordCompletionEntries($contract->fresh());

        $this->assertFalse($arrival['created_external_office_cost_constraint']);
        $this->assertFalse($arrival['created_supplier_purchase']);
        $this->assertSame((int) $constraint->id, (int) $arrival['external_office_cost_constraint_id']);
        $this->assertSame((int) $constraint->id, (int) $invoice->fresh()->external_office_cost_constraint_id);

        $this->assertSame(1, Constraint::query()
            ->where('statement', 'like', '%لعقد استقدام: ' . $contract->id . '%')
            ->count());
    }

    public function test_supplier_line_cost_center_setting_adds_the_worker_detail_to_the_agent_line(): void
    {
        $this->saveSetting(RecruitmentSupplierCostRecognition::SETTING_KEY, RecruitmentSupplierCostRecognition::MODE_ON_CONTRACT);
        $this->saveSetting(RecruitmentSupplierSettings::COST_SOURCE, RecruitmentSupplierSettings::SOURCE_OFFICE_PRICE_LIST);
        $this->saveSetting(RecruitmentSupplierSettings::SUPPLIER_LINE_COST_CENTER, 'on');

        if (!Account::query()->where('code', 221)->exists()) {
            $this->createAccount('الوكلاء الخارجيين', 'creditor')->update(['code' => 221]);
        }

        $nationality = $this->createNationality('اختبار سريلانكا');
        $contract = $this->createContract($nationality);
        $costAccount = $this->createAccount('تكلفة مبيعات ' . $nationality->display_name, 'debtor');
        $this->ensureServiceItemTemplate($costAccount->id);

        $officeAccount = $this->createAccount('مكتب سريلانكا ' . $contract->id, 'creditor');
        $office = \App\Models\office\Office::query()->create([
            'ar' => ['title' => $officeAccount->name],
            'account_id' => $officeAccount->id,
            'cost_of_dollar' => 200,
        ]);
        $contract->info()->create(['manual_maid_office_id' => $office->id]);

        $result = app(RecruitmentTripAccountingService::class)->recordContractCostEntries($contract->fresh());
        $this->assertNull($result['skipped_reason']);

        $costCentre = RecruitmentCostCenters::forContract((int) $contract->id, $nationality->display_name);

        $agentLine = ConstraintDetails::query()
            ->where('constraint_id', $result['external_office_cost_constraint_id'])
            ->where('account_id', $officeAccount->id)
            ->firstOrFail();

        $this->assertSame((int) $costCentre->id, (int) $agentLine->cost_center_id);
    }

    private function ensureServiceItemTemplate(int $accountId): void
    {
        if (\Nami\Finance\Models\Item::query()->where('type', 'service')->exists()) {
            return;
        }

        $categoryId = DB::table('fn_categories')->insertGetId(['title' => 'خدمات اختبار الدورة']);
        $unitId = DB::table('fn_units')->insertGetId(['title' => 'خدمة اختبار الدورة']);

        \Nami\Finance\Models\Item::query()->create([
            'name' => 'خدمة اختبار الدورة',
            'type' => 'service',
            'code' => 999998,
            'main_category_id' => $categoryId,
            'sale_unit_id' => $unitId,
            'purchase_unit_id' => $unitId,
            'details' => 'اختبار',
            'sale_account_id' => $accountId,
            'stock_account_id' => $accountId,
        ]);
    }

    private function createInvoice(Contract $contract): \App\Models\ingaz\Invoice
    {
        return \App\Models\ingaz\Invoice::query()->create([
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
        ]);
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

    private function lines(Constraint $constraint): array
    {
        return ConstraintDetails::query()
            ->where('constraint_id', $constraint->id)
            ->orderBy('id')
            ->get()
            ->map(fn (ConstraintDetails $d) => [(int) $d->account_id, (float) $d->debtor_value, (float) $d->creditor_value])
            ->all();
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
                'title' => 'اختبار الدورة ' . now()->year,
                'from_date' => now()->startOfYear()->toDateString(),
                'to_date' => now()->endOfYear()->toDateString(),
                'status' => 1,
                'created_by_id' => $this->admin->id,
            ]);
        }
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


    public function test_contract_finance_screen_is_off_by_default_and_refuses_access(): void
    {
        $this->assertFalse(RecruitmentSupplierSettings::contractFinanceScreenEnabled());

        $contract = $this->createContract($this->createNationality('اختبار نيجيريا'));

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(\App\Http\Controllers\ingaz\RecruitmentContractFinanceController::class)->show((int) $contract->id);
    }

    public function test_contract_finance_screen_reports_the_three_states_without_posting_anything(): void
    {
        $this->saveSetting(RecruitmentSupplierSettings::CONTRACT_FINANCE_SCREEN, 'on');

        $contract = $this->createContract($this->createNationality('اختبار غانا'));
        $constraintsBefore = Constraint::query()->count();

        $view = app(\App\Http\Controllers\ingaz\RecruitmentContractFinanceController::class)->show((int) $contract->id);
        $state = $view->getData()['state'];

        // عقد بلا فاتورة ولا تحصيل ولا تكلفة وكيل.
        $this->assertFalse($state['invoice']['exists']);
        $this->assertFalse($state['musaned']['settled']);
        $this->assertSame(0.0, $state['agent']['invoiced']);

        // الشاشة لوحة عرض: لا تنشئ أي قيد بمجرد فتحها.
        $this->assertSame($constraintsBefore, Constraint::query()->count());

        $invoice = $this->createInvoice($contract);
        $state = app(\App\Http\Controllers\ingaz\RecruitmentContractFinanceController::class)
            ->show((int) $contract->id)->getData()['state'];

        $this->assertTrue($state['invoice']['exists']);
        $this->assertSame((int) $invoice->id, (int) $state['invoice']['id']);
        $this->assertSame(3980.75, $state['invoice']['total']);
        $this->assertFalse($state['musaned']['settled']);
        $this->assertSame($constraintsBefore, Constraint::query()->count());
    }

    public function test_agent_balance_helper_is_the_single_source_for_both_screens(): void
    {
        $contract = $this->createContract($this->createNationality('اختبار أوغندا'));

        $balance = \App\Support\RecruitmentAgentBalance::for($contract);

        $this->assertSame(0.0, $balance['invoiced']);
        $this->assertSame(0.0, $balance['paid']);
        $this->assertSame(0.0, $balance['remaining']);
        $this->assertNull($balance['cost_constraint_id']);
    }

    private function saveSetting(string $title, ?string $value): void
    {
        ConfirmSetting::query()->where('title', $title)->delete();
        ConfirmSetting::query()->create([
            'title' => $title,
            'value' => $value,
            'created_by_id' => $this->admin->id,
        ]);
    }

    private function createNationality(string $name): Nationality
    {
        return Nationality::query()->create([
            'ar' => ['title' => $name, 'sub_title' => $name],
        ]);
    }

    private function createContract(Nationality $nationality): Contract
    {
        $user = ClientUser::query()->create([
            'name' => 'عميل اختبار الدورة',
            'email' => 'cycle' . uniqid() . '@example.test',
            'phone' => '05' . random_int(10000000, 99999999),
            'password' => bcrypt('secret'),
        ]);

        return Contract::query()->create([
            'status' => ContractStatusEnum::New->value,
            'type' => 'recruitment',
            'user_id' => $user->id,
            'marketer_id' => $this->admin->id,
            'required_maid_nationality_id' => $nationality->id,
        ]);
    }
}
