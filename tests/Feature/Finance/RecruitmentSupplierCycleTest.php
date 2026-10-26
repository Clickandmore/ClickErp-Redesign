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
            [
                RecruitmentSupplierCostRecognition::SETTING_KEY,
                RecruitmentSupplierSettings::CONTRACT_FINANCE_SCREEN,
                \App\Support\InvoiceSupplierWorkflow::SETTING_KEY,
                \App\Support\RecruitmentRevenueRecognition::SETTING_KEY,
                'automatic_journal_entries',
            ]
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

        // «تسلسل الفواتير ومساند والمورد» قديم افتراضياً: لا عمولة عند الإصدار ولا فاتورة مورد
        // مع فاتورة العميل، وبيان قيد التكلفة بصيغة «الإرسالية» كما كان.
        $this->assertFalse(\App\Support\InvoiceSupplierWorkflow::enabled());
        $statement = $this->callPrivate(
            app(RecruitmentTripAccountingService::class),
            'buildExternalOfficeCostStatement',
            $this->createContract($this->createNationality('اختبار الافتراضي')),
            null
        );
        $this->assertStringStartsWith('فاتورة مورد: إثبات تكلفة إرسالية المكتب الخارجي لعقد استقدام: ', $statement);
    }

    public function test_supplier_statement_follows_the_workflow_and_matches_every_earlier_wording(): void
    {
        $service = app(RecruitmentTripAccountingService::class);
        $contract = $this->createContract($this->createNationality('اختبار البيان'));

        $legacy = $this->callPrivate($service, 'buildExternalOfficeCostStatement', $contract, null);

        $this->saveSetting(\App\Support\InvoiceSupplierWorkflow::SETTING_KEY, \App\Support\InvoiceSupplierWorkflow::UPDATED);
        $updated = $this->callPrivate($service, 'buildExternalOfficeCostStatement', $contract, null);
        $this->assertStringStartsWith('فاتورة مورد: إثبات تكلفة فاتورة المورد لعقد استقدام: ', $updated);

        // قيد أُنشئ بالصيغة القديمة يُعثر عليه بالصيغة الجديدة، فلا يُنشأ قيد مكرر بعد التبديل.
        $existing = Constraint::withoutEvents(fn () => Constraint::query()->create([
            'type' => 1,
            'code' => 9100000 + random_int(1, 99999),
            'date' => now()->toDateString(),
            'value' => 1875,
            'statement' => $legacy,
        ]));

        $found = $this->callPrivate($service, 'findExistingConstraint', $updated, now()->toDateString(), 1875.0);
        $this->assertSame((int) $existing->id, (int) $found?->id);
    }

    public function test_cv_cost_precedes_the_office_price_only_under_the_updated_workflow(): void
    {
        $this->saveSetting(RecruitmentSupplierSettings::COST_SOURCE, RecruitmentSupplierSettings::SOURCE_OFFICE_PRICE_LIST);

        $contract = $this->createContract($this->createNationality('اختبار السيرة'));
        $office = (new \App\Models\RecruitmentOffices())->forceFill(['cost_of_dollar' => 500]);
        $maid = (new \App\Models\ingaz\Maid())->forceFill(['supplier_cost_usd' => 800]);
        $maid->setRelation('office', $office);
        $contract->setRelation('maid', $maid);
        $contract->setRelation('externalOfficeDispatch', null);

        $service = app(RecruitmentTripAccountingService::class);

        // التسلسل القديم: تكلفة السيرة لا تُقرأ، كما قبل التحديث.
        $this->assertSame(500.0, $this->callPrivate($service, 'resolveExternalOfficeCostUsd', $contract));

        $this->saveSetting(\App\Support\InvoiceSupplierWorkflow::SETTING_KEY, \App\Support\InvoiceSupplierWorkflow::UPDATED);
        $this->assertSame(800.0, $this->callPrivate($service, 'resolveExternalOfficeCostUsd', $contract));
    }

    public function test_supplier_invoice_cannot_silently_differ_from_the_cost_posted_at_contract_creation(): void
    {
        $this->saveSetting(RecruitmentSupplierCostRecognition::SETTING_KEY, RecruitmentSupplierCostRecognition::MODE_ON_CONTRACT);
        $this->saveSetting(RecruitmentSupplierSettings::COST_SOURCE, RecruitmentSupplierSettings::SOURCE_OFFICE_PRICE_LIST);

        $contract = $this->createContract($this->createNationality('اختبار الفرق'));
        $constraint = Constraint::withoutEvents(fn () => Constraint::query()->create([
            'type' => 1,
            'code' => 9200000 + random_int(1, 99999),
            'date' => now()->toDateString(),
            'value' => 1875,
            'statement' => 'قيد تكلفة عند إنشاء العقد',
        ]));
        Contract::query()->whereKey($contract->id)->update(['external_office_cost_constraint_id' => $constraint->id]);

        try {
            app(\App\Http\Controllers\ingaz\RecruitmentContractExternalOfficeDispatchController::class)
                ->persistExternalOfficeDispatch($contract->fresh(), now()->toDateString(), 'RUH', '600', $this->admin->id);
            $this->fail('A supplier invoice with a different amount must be refused.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('office_contract_cost_usd', $exception->errors());
            $this->assertStringContainsString('1,875.00', $exception->errors()['office_contract_cost_usd'][0]);
        }

        $this->assertNull($contract->fresh()->externalOfficeDispatch);
    }

    public function test_refund_before_any_musaned_transfer_reverses_the_commission_posted_at_issuance(): void
    {
        $this->saveSetting('automatic_journal_entries', 'on');

        $contract = $this->createContract($this->createNationality('اختبار عكس العمولة'));
        $invoice = $this->createInvoice($contract);
        $refunds = app(\App\Services\ingaz\RecruitmentContractRefundService::class);

        // التسلسل القديم: لا عمولة عند الإصدار، فلا شيء يُعكس.
        $this->assertFalse($this->callPrivate($refunds, 'shouldReverseMusanedCommission', $invoice->fresh(), false));

        $musaned = $this->createAccount('مساند العكس', 'debtor');
        $commission = $this->createAccount('عمولة العكس', 'debtor');
        $tax = $this->createAccount('ضريبة العكس', 'debtor');
        $issued = Constraint::withoutEvents(fn () => Constraint::query()->create([
            'type' => 1,
            'code' => 9300000 + random_int(1, 99999),
            'date' => now()->toDateString(),
            'value' => 83.91,
            'statement' => 'التكاليف البنكية لمساند عند إصدار الفاتورة',
        ]));
        foreach ([[$commission->id, 72.97, 0], [$tax->id, 10.94, 0], [$musaned->id, 0, 83.91]] as [$account, $debtor, $creditor]) {
            ConstraintDetails::query()->create([
                'constraint_id' => $issued->id,
                'type' => 1,
                'account_id' => $account,
                'debtor_value' => $debtor,
                'creditor_value' => $creditor,
                'statement' => $issued->statement,
            ]);
        }
        $invoice->forceFill(['musaned_commission_constraint_id' => $issued->id])->saveQuietly();
        $invoice = $invoice->fresh();

        // بعد التحويل حسمت مساند العمولة فعلاً، فلا تُعكس.
        $this->assertFalse($this->callPrivate($refunds, 'shouldReverseMusanedCommission', $invoice, true));
        $this->assertTrue($this->callPrivate($refunds, 'shouldReverseMusanedCommission', $invoice, false));

        $reversal = $this->callPrivate($refunds, 'reverseMusanedCommission', $contract->fresh(), $invoice, now()->toDateString());

        $this->assertNotNull($reversal);
        $this->assertSame([
            [$commission->id, 0.0, 72.97],
            [$tax->id, 0.0, 10.94],
            [$musaned->id, 83.91, 0.0],
        ], $this->lines($reversal));
        $this->assertSame((int) $reversal->id, (int) $invoice->fresh()->musaned_commission_reversal_constraint_id);

        // لا يُعكس مرتين.
        $this->assertFalse($this->callPrivate($refunds, 'shouldReverseMusanedCommission', $invoice->fresh(), false));
    }

    public function test_refund_reverses_supplier_cost_posted_before_arrival_and_leaves_the_default_cycle_alone(): void
    {
        $this->saveSetting('automatic_journal_entries', 'on');

        $contract = $this->createContract($this->createNationality('اختبار عكس التكلفة'));
        $invoice = $this->createInvoice($contract);
        $refunds = app(\App\Services\ingaz\RecruitmentContractRefundService::class);

        // بلا قيد تكلفة لا شيء يُعكس.
        $this->assertFalse($this->callPrivate($refunds, 'shouldReverseExternalOfficeCost', $contract->fresh(), $invoice->fresh()));

        $invoice->forceFill(['external_office_cost_constraint_id' => 987654321])->saveQuietly();

        // الدورة الافتراضية: فاتورة المورد لا تُعكس من نافذة الاسترداد، كما كانت.
        $this->assertFalse($this->callPrivate($refunds, 'shouldReverseExternalOfficeCost', $contract->fresh(), $invoice->fresh()));

        // تكلفة أُثبتت قبل الوصول بإعداد صريح تُعكس مع إثبات الإيراد عند الوصول.
        $this->saveSetting(RecruitmentSupplierCostRecognition::SETTING_KEY, RecruitmentSupplierCostRecognition::MODE_ON_DISPATCH);
        $this->assertTrue($this->callPrivate($refunds, 'shouldReverseExternalOfficeCost', $contract->fresh(), $invoice->fresh()));

        // بعد الوصول: الدورة الافتراضية كما هي، التكلفة لا تُعكس من نافذة الاسترداد.
        Contract::query()->whereKey($contract->id)->update(['status' => ContractStatusEnum::Arrival->value]);
        $this->assertFalse($this->callPrivate($refunds, 'shouldReverseExternalOfficeCost', $contract->fresh(), $invoice->fresh()));
    }

    public function test_musaned_bank_charges_stay_a_separate_entry_by_default(): void
    {
        $this->assertSame(
            \App\Support\MusanedCommissionPosting::SEPARATE,
            \App\Support\MusanedCommissionPosting::mode()
        );
    }

    public function test_two_entry_mode_posts_bank_charges_inside_the_receipt_entry_and_reverses_only_them(): void
    {
        $musaned = $this->createAccount('بنك مساند قيدان', 'debtor');
        $musaned->update(['type' => \Nami\Finance\Enums\AccountTypesEnum::Bank->value]);
        $commission = $this->createAccount('عمولات مساند قيدان', 'debtor');
        $tax = $this->createAccount('ضريبة مصروفات قيدان', 'debtor');
        foreach ([
            'automatic_journal_entries' => 'on',
            \App\Support\InvoiceSupplierWorkflow::SETTING_KEY => \App\Support\InvoiceSupplierWorkflow::UPDATED,
            \App\Support\MusanedCommissionPosting::SETTING_KEY => \App\Support\MusanedCommissionPosting::WITH_RECEIPT,
            'musaned_account' => (string) $musaned->id,
            'musaned_intermediate_account' => (string) $musaned->id,
            'musaned_commission_account' => (string) $commission->id,
            'expense_tax_account' => (string) $tax->id,
        ] as $title => $value) {
            $this->saveSetting($title, $value);
        }
        ConfirmSetting::query()->where('title', 'tax_rate_expenditure')->delete();

        $contract = $this->createContract($this->createNationality('اختبار قيدان'));
        $client = $this->createAccount('عميل قيدان', 'debtor');
        $invoice = $this->createInvoice($contract);
        $invoice->forceFill(['total_due' => 14296.68, 'musaned_commission_rate' => 2.4])->saveQuietly();

        // سند القبض كما تنشئه الفاتورة: مدين مساند / دائن العميل بكامل الإجمالي.
        $receipt = Constraint::withoutEvents(fn () => Constraint::query()->create([
            'type' => 1,
            'code' => 9400000 + random_int(1, 99999),
            'date' => now()->toDateString(),
            'value' => 14296.68,
            'statement' => 'سند قبض سداد الفاتورة #' . $invoice->id,
        ]));
        foreach ([[$musaned->id, 14296.68, 0], [$client->id, 0, 14296.68]] as [$account, $debtor, $creditor]) {
            ConstraintDetails::query()->create([
                'constraint_id' => $receipt->id,
                'type' => 1,
                'account_id' => $account,
                'debtor_value' => $debtor,
                'creditor_value' => $creditor,
                'statement' => $receipt->statement,
            ]);
        }
        $bond = \Nami\Finance\Models\Bond::query()->create([
            'code' => 9400000 + random_int(1, 99999),
            'type' => \Nami\Finance\Enums\BondTypes::Qabd->value,
            'payment_type' => 1,
            'debit_account_id' => $musaned->id,
            'credit_account_id' => $client->id,
            'value' => 14296.68,
            'date' => now()->toDateString(),
            'statement' => 'سند قبض سداد فاتورة طلب استقدام للعقد #' . $contract->id . ' / الفاتورة #' . $invoice->id,
            'created_by_id' => $this->admin->id,
            'constraint_id' => $receipt->id,
            'is_confirm' => 1,
        ]);
        \Nami\Finance\Models\Bond::query()->whereKey($bond->id)->update(['recruitment_contract_id' => $contract->id]);

        $constraintsBefore = Constraint::query()->count();
        $posted = app(\App\Http\Controllers\ingaz\MusanedTransferController::class)
            ->recordInvoiceCommission($invoice->fresh());

        // قيدان لا ثلاثة: لا قيد جديد، والعمولة داخل قيد السند بقيمته نفسها.
        $this->assertSame($constraintsBefore, Constraint::query()->count());
        $this->assertSame((int) $receipt->id, (int) $posted->id);
        $this->assertSame(14296.68, (float) $posted->fresh()->value);
        $this->assertSame([
            [$musaned->id, 13953.56, 0.0],
            [$client->id, 0.0, 14296.68],
            [$commission->id, 298.37, 0.0],
            [$tax->id, 44.75, 0.0],
        ], $this->lines($receipt));

        $fresh = $invoice->fresh();
        $this->assertSame((int) $receipt->id, (int) $fresh->musaned_commission_constraint_id);
        $this->assertSame(\App\Support\MusanedCommissionPosting::WITH_RECEIPT, $fresh->musaned_commission_posting);

        // الاسترداد قبل التحويل يعكس العمولة وحدها، لا سداد العميل.
        $reversal = $this->callPrivate(
            app(\App\Services\ingaz\RecruitmentContractRefundService::class),
            'buildMusanedCommissionReversalLines',
            $fresh,
            'عكس اختبار'
        );
        $this->assertSame(
            [[$musaned->id, 343.12, 0.0], [$commission->id, 0.0, 298.37], [$tax->id, 0.0, 44.75]],
            array_map(fn (array $line) => [$line['account_id'], (float) $line['debtor_value'], (float) $line['creditor_value']], $reversal)
        );

        // تعديل السند من شاشة السندات كان سيعيد توليد قيده فتضيع سطور العمولة.
        $this->expectException(ValidationException::class);
        app(\Nami\Finance\Services\BondService::class)->updateBond($bond->id, []);
    }

    private function callPrivate(object $target, string $method, ...$arguments)
    {
        $reflection = new \ReflectionMethod($target, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($target, ...$arguments);
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

        // overview() هي شاشة الملف المالي. الاسم show() صار لميزة «العمليات المالية
        // المتأخرة» المستقلة، وحارسها يرفض بـ422 قبل أن يُسأل عن هذا الإعداد أصلاً.
        try {
            app(\App\Http\Controllers\ingaz\RecruitmentContractFinanceController::class)
                ->overview((int) $contract->id);

            $this->fail('كان يجب رفض الوصول لأن الإعداد مطفأ.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            // 403 تحديداً: لو مرّ الرفض بأي حارس آخر لفقد الاختبار معناه.
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_contract_finance_screen_reports_the_three_states_without_posting_anything(): void
    {
        $this->saveSetting(RecruitmentSupplierSettings::CONTRACT_FINANCE_SCREEN, 'on');

        $contract = $this->createContract($this->createNationality('اختبار غانا'));
        $constraintsBefore = Constraint::query()->count();

        $view = app(\App\Http\Controllers\ingaz\RecruitmentContractFinanceController::class)->overview((int) $contract->id);
        $state = $view->getData()['state'];

        // عقد بلا فاتورة ولا تحصيل ولا تكلفة وكيل.
        $this->assertFalse($state['invoice']['exists']);
        $this->assertFalse($state['musaned']['settled']);
        $this->assertSame(0.0, $state['agent']['invoiced']);

        // الشاشة لوحة عرض: لا تنشئ أي قيد بمجرد فتحها.
        $this->assertSame($constraintsBefore, Constraint::query()->count());

        $invoice = $this->createInvoice($contract);
        $state = app(\App\Http\Controllers\ingaz\RecruitmentContractFinanceController::class)
            ->overview((int) $contract->id)->getData()['state'];

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
