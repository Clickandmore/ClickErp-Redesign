<?php

namespace Nami\Finance\Services;

use App\Services\ingaz\RentalMaidFinanceService;
use App\Services\ingaz\RentalContractFinanceService;
use App\Services\ingaz\ShelterLivingExpenseAccountingService;
use App\Services\ingaz\NationalityFinanceAccountService;
use Illuminate\Support\Collection;
use Nami\Finance\MainService;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\AccountStatusEnum;
use Nami\Finance\Models\Account;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\ConfirmSetting;
use Nami\Finance\Models\TaxSetting;

class ConfirmSettingService extends MainService
{
    public function __construct(ConfirmSetting $model, protected TaxSettingService $taxSettingService
    , protected AccountService $accountService)
    {
        $this->model = $model;
    }

    public function getTaxSetting() {
        return $this->taxSettingService->get()->load('account');
    }

    public function getSelectedAccounts(Collection $settings, array $additionalAccountIds = []): Collection
    {
        $accountIds = $settings
            ->pluck('value')
            ->filter(fn ($value) => is_numeric($value) && (int) $value > 0)
            ->map(fn ($value) => (int) $value)
            ->concat($additionalAccountIds)
            ->filter(fn ($value) => is_numeric($value) && (int) $value > 0)
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        return Account::query()
            ->select(['id', 'code', 'name'])
            ->whereIn('id', $accountIds)
            ->get()
            ->keyBy('id');
    }

    public function searchAccounts(?string $search, ?string $scope = null): Collection
    {
        $search = trim((string) $search);
        $query = Account::query()->select(['id', 'parent_id', 'code', 'name', 'status', 'type']);

        match ($scope) {
            'leaf' => $query->whereDoesntHave('children'),
            'debtor_leaf' => $query
                ->whereDoesntHave('children')
                ->where('status', AccountStatusEnum::Debtor->value),
            'creditor_leaf' => $query
                ->whereDoesntHave('children')
                ->where('status', AccountStatusEnum::Creditor->value),
            'rental_client_parent' => $query
                ->where('status', AccountStatusEnum::Debtor->value)
                ->where(function ($query) {
                    $query->where('type', AccountTypesEnum::Clients->value)
                        ->orWhereHas('children');
                }),
            'rental_receipt' => $query
                ->whereDoesntHave('children')
                ->where('status', AccountStatusEnum::Debtor->value)
                ->whereIn('type', [
                    AccountTypesEnum::Bank->value,
                    AccountTypesEnum::Box->value,
                    AccountTypesEnum::ElectronicWallet->value,
                ]),
            'musaned_source' => $query->musanedAccountCandidate(),
            'musaned_intermediate' => $query
                ->whereDoesntHave('children')
                ->musanedAccountCandidate(),
            default => null,
        };

        return $query
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('code')
            ->limit(50)
            ->get()
            ->map(fn (Account $account) => [
                'id' => $account->id,
                'text' => trim((string) $account->code) . ' / ' . $account->name,
            ]);
    }

    public function getAccounts() {
        return $this->accountService->getAccountWithNoChild();
    }

    public function getMusanedIntermediateAccounts()
    {
        return Account::query()
            ->whereDoesntHave('children')
            ->musanedAccountCandidate()
            ->orderBy('code')
            ->get();
    }

    public function getMusanedSourceAccounts()
    {
        return Account::query()
            ->musanedAccountCandidate()
            ->orderBy('code')
            ->get()
            ->unique('id')
            ->values();
    }

    public function getDebtorAccounts()
    {
        return Account::query()
            ->whereDoesntHave('children')
            ->where('status', AccountStatusEnum::Debtor->value)
            ->orderBy('code')
            ->get();
    }

    public function getRevenueTaxAccounts()
    {
        return Account::query()
            ->whereDoesntHave('children')
            ->where('status', AccountStatusEnum::Creditor->value)
            ->orderBy('code')
            ->get();
    }

    public function getRentalClientParentAccounts()
    {
        return Account::query()
            ->where('status', AccountStatusEnum::Debtor->value)
            ->where(function ($query) {
                $query->where('type', AccountTypesEnum::Clients->value)
                    ->orWhereHas('children');
            })
            ->orderBy('code')
            ->get();
    }

    public function getRentalReceiptAccounts()
    {
        return Account::query()
            ->whereDoesntHave('children')
            ->where('status', AccountStatusEnum::Debtor->value)
            ->whereIn('type', [
                AccountTypesEnum::Bank->value,
                AccountTypesEnum::Box->value,
                AccountTypesEnum::ElectronicWallet->value,
            ])
            ->orderBy('code')
            ->get();
    }

    public function storeData($data)
    {
        $rows = $data['data'] ?? [];

        foreach ($rows as $key => $setting) {
            if (($setting['title'] ?? null) === \App\Support\InvoiceSupplierWorkflow::SETTING_KEY
                && !in_array($setting['value'] ?? null, [\App\Support\InvoiceSupplierWorkflow::LEGACY, \App\Support\InvoiceSupplierWorkflow::UPDATED], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages(["data.$key.value" => 'اختر التسلسل القديم أو التسلسل الجديد.']);
            }
        }

        foreach ($rows as $key => $setting) {
            $title = $setting['title'] ?? null;

            if (!in_array($title, TaxAccountResolver::RATE_SETTINGS, true) || empty($setting['value'])) {
                continue;
            }

            $tax = TaxSetting::find($setting['value']);
            if ($tax && ((int) $tax->value <= 0 || (int) $tax->value > 100)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "data.$key.value" => 'اختر ضريبة افتراضية بنسبة أكبر من صفر وحتى 100.',
                ]);
            }
            $status = $title === 'tax_rate_revenue' ? 'creditor' : 'debtor';

            if (!$tax || !Account::whereKey($tax->account_id)->where('status', $status)->whereDoesntHave('children')->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "data.$key.value" => $status === 'creditor'
                        ? 'اختر ضريبة مرتبطة بحساب دائن طرفي لضريبة الإيرادات.'
                        : 'اختر ضريبة مرتبطة بحساب مدين طرفي لضريبة المصروفات.',
                ]);
            }
        }

        $this->assertRecruitmentSupplierCycleIsConsistent($rows);

        DB::transaction(function () use ($rows) {
            foreach ($rows as $value) {
                $title = $value['title'] ?? null;

                // حقول الحساب القديمة لم تعد تُعرض ولا تُكتب من هذه الصفحة.
                if ($title === null || isset(TaxAccountResolver::RATE_SETTINGS[$title])) {
                    continue;
                }

                $legacyTitle = array_search($title, TaxAccountResolver::RATE_SETTINGS, true);

                // الصفحة تُرسل كل الإعدادات في كل حفظ. اختيار ضريبة فارغ يعني
                // «لم يُلمس هذا الحقل»، فلا يجوز أن يمحو الاختيار أو الحساب القديم.
                if ($legacyTitle !== false && blank($value['value'] ?? null)) {
                    continue;
                }

                if ($legacyTitle !== false) {
                    app(TaxAccountResolver::class)->remember($legacyTitle, (int) auth('admin')->id());
                }

                $setting = $this->model->updateOrCreate(
                    ['title' => $title],
                    [
                        'value' => $value['value'] ?? null,
                        'created_by_id' => auth('admin')->user()->id,
                    ]
                );

                if ($legacyTitle !== false) {
                    app(TaxAccountResolver::class)->remember($legacyTitle, (int) auth('admin')->id());
                }

                // الحساب القديم يبقى احتياطياً للقراءة فقط بعد اعتماد تعريف الضريبة،
                // ولا يُصفَّر حتى لا تفقد الإعدادات حسابها الوحيد.

                // Clean up older duplicated rows for the same setting title.
                $this->model->where('title', $title)
                    ->whereKeyNot($setting->id)
                    ->delete();
            }
        });
    }


    /**
     * تركيبات إعدادات «قيود المشتريات والوكلاء الخارجيون» التي لا يمكن تنفيذها تُرفض عند
     * الحفظ بدل أن تفشل بصمت وقت إنشاء العقد.
     */
    private function assertRecruitmentSupplierCycleIsConsistent(array $rows): void
    {
        $settings = \App\Support\RecruitmentSupplierSettings::class;
        $recognition = \App\Support\RecruitmentSupplierCostRecognition::class;

        // القيمة المرسلة أولاً، وإلا المحفوظة: الصفحة قد تُحفظ بقسم واحد.
        $submitted = function (string $title) use ($rows) {
            foreach ($rows as $row) {
                if (($row['title'] ?? null) === $title) {
                    return $row['value'] ?? null;
                }
            }

            return null;
        };

        $rate = $submitted($settings::USD_RATE);
        if ($rate !== null && trim((string) $rate) !== '' && (!is_numeric($rate) || (float) $rate <= 0)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'data.' . $settings::USD_RATE . '.value' => 'سعر صرف الدولار يجب أن يكون رقماً أكبر من صفر.',
            ]);
        }

        $mode = $submitted($recognition::SETTING_KEY);
        $mode = $mode === null ? $recognition::mode() : $recognition::normalize($mode);

        if ($mode !== $recognition::MODE_ON_CONTRACT) {
            return;
        }

        $source = $submitted($settings::COST_SOURCE);
        $source = $source === null
            ? $settings::costSource()
            : $settings::normalize($settings::COST_SOURCE, $source);

        if ($source !== $settings::SOURCE_OFFICE_PRICE_LIST) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'data.' . $settings::COST_SOURCE . '.value' => 'إثبات التكلفة «عند إنشاء العقد» يلزمه مصدر مبلغ لا يعتمد على الإرسالية. اختر «تكلفة الدولار المعرّفة على ملف المكتب الخارجي».',
            ]);
        }
    }

    public function getAllSettings()
    {
        $titles = array_merge([
            'tax_rate_expenditure',
            'tax_rate_revenue',
            'cost_center',
            'sarf_bond',
            'qabd_bond',
            'account_transfer',
            'general_sarf_bond',
            'general_qabd_bond',
            'daily_constraints',
            'automatic_journal_entries',
            \App\Support\InvoiceSupplierWorkflow::SETTING_KEY,
            \App\Support\MusanedCommissionPosting::SETTING_KEY,
            \App\Support\RecruitmentRevenueRecognition::SETTING_KEY,
            \App\Support\RecruitmentSupplierCostRecognition::SETTING_KEY,
            'bill_sales',
            'bill_purchases',
            'bond_sarf_account',
            'bond_qabd_account',
            'musaned_account',
            'revenue_tax_account',
            'musaned_intermediate_account',
            'musaned_commission_account',
            'expense_tax_account',
            'general_invoice_revenue_account',
            'ingaz_delegation_revenue_account',
            'ingaz_delegation_ingaz_account',
            'ingaz_delegation_cost_account',
            'ingaz_delegation_fixed_cost',
            'cost_center_sarf_bond',
            'cost_center_qabd_bond',
            'cost_center_general_sarf_bond',
            'cost_center_general_qabd_bond',
            'cost_center_account_transfer',
        ], \App\Support\RecruitmentSupplierSettings::confirmSettingTitles(), RentalContractFinanceService::confirmSettingTitles(), RentalMaidFinanceService::confirmSettingTitles(), ShelterLivingExpenseAccountingService::confirmSettingTitles(), NationalityFinanceAccountService::confirmSettingTitles(), \App\Support\ShelterAccountingSettings::confirmSettingTitles());

        return $this->model->where(function ($query) use ($titles) {
            $query->whereIn('title', $titles)
                ->orWhere('title', 'like', 'nationality_account_%');
        })
            ->latest('updated_at')
            ->get()
            ->unique('title')
            ->keyBy('title');
    }

    public function getConfirmSetting($type)
    {
        if (isset(TaxAccountResolver::RATE_SETTINGS[$type])) {
            $account = app(TaxAccountResolver::class)->resolve($type);

            return $account ? new ConfirmSetting(['title' => $type, 'value' => $account->id]) : null;
        }

        return $this->model->where('title', $type)->latest('updated_at')->first();
    }
}
