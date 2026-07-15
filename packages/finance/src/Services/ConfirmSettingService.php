<?php

namespace Nami\Finance\Services;

use App\Services\ingaz\RentalMaidFinanceService;
use App\Services\ingaz\ShelterLivingExpenseAccountingService;
use Nami\Finance\MainService;
use Nami\Finance\Enums\AccountStatusEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\ConfirmSetting;

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

    public function getAccounts() {
        return $this->accountService->getAccountWithNoChild();
    }

    public function getMusanedIntermediateAccounts()
    {
        return Account::query()
            ->whereDoesntHave('children')
            ->where(function ($query) {
                $query->whereIn('parent_id', [122, 156])
                    ->orWhere('name', 'like', '%مساند%');
            })
            ->orderBy('code')
            ->get();
    }

    public function getMusanedSourceAccounts()
    {
        return Account::query()
            ->where(function ($query) {
                $query->whereIn('parent_id', [122, 156])
                    ->orWhere('name', 'like', '%مساند%');
            })
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

    public function storeData($data)
    {
        foreach (($data['data'] ?? []) as $value) {
            if (!isset($value['title'])) {
                continue;
            }

            $setting = $this->model->updateOrCreate(
                ['title' => $value['title']],
                [
                    'value' => $value['value'] ?? null,
                    'created_by_id' => auth('admin')->user()->id,
                ]
            );

            // Clean up older duplicated rows for the same setting title.
            $this->model->where('title', $value['title'])
                ->whereKeyNot($setting->id)
                ->delete();
        }
    }

    public function getAllSettings()
    {
        return $this->model->whereIn('title', array_merge([
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
            'bill_sales',
            'bill_purchases',
            'bond_sarf_account',
            'bond_qabd_account',
            'musaned_account',
            'revenue_tax_account',
            'musaned_intermediate_account',
            'musaned_commission_account',
            'expense_tax_account',
            'ingaz_delegation_revenue_account',
            'ingaz_delegation_ingaz_account',
            'ingaz_delegation_cost_account',
            'ingaz_delegation_fixed_cost',
            'cost_center_sarf_bond',
            'cost_center_qabd_bond',
            'cost_center_general_sarf_bond',
            'cost_center_general_qabd_bond',
            'cost_center_account_transfer',
        ], RentalMaidFinanceService::confirmSettingTitles(), ShelterLivingExpenseAccountingService::confirmSettingTitles()))
            ->latest('updated_at')
            ->get()
            ->unique('title')
            ->keyBy('title');
    }

    public function getConfirmSetting($type)
    {
        return $this->model->where('title', $type)->latest('updated_at')->first();
    }
}
