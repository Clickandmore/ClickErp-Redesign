<?php

namespace Nami\Finance\Services;

use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\PrincipleSale;

class PrincipleSaleService extends MainService
{
    public function __construct(PrincipleSale $model,
        protected AccountService $accountService,
        protected PrincipleService $principleService,
        protected ConstraintService $constraintService,
        protected ConstraintDetailsService $constraintDetailsService,
        protected YearService $yearService,
        protected TaxSettingService $taxSettingService
        )
    {
        $this->model = $model;
    }
    public function getDepreciationPrincipleIds()
    {
        return $this->model->query()->get()->pluck('principle_id')->toArray();
    }

    public function getAccounts() {
        return $this->accountService->getAccountWithNoChild();
    }

    public function getPrinciples() {
        return $this->principleService->getPrincipleAndTotalConstraintValue();
    }

    public function getTaxs() {
        return $this->taxSettingService->get();
    }

    public function checkDateRange($date) {
        return $this->yearService->checkDateRange($date);
    }

    public function storeConstraint($obj)
    {
        $principle_account_id = $obj->principle->account_id;
        $total_sales_price = $obj->total_price;
        $difference = $obj->principle_value - $obj->financial_value;
        $code = $this->constraintService->lastCode();
        $yearId = $this->yearService->getActiveYear()->id;
        $data = [
            'type' => ConstraintTypiesEnum::PrincipleSales->value,
            'value' => $total_sales_price + $difference,
            'date' => date('Y-m-d'),
            'code' => $code,
            'year_id' => $yearId,
            'statement' => "بيع اصل",
            'created_by_id' => auth('admin')->user()->id,
        ];
        $value = $obj->purchase_value;
        $constraint = $this->constraintService->store($data);
        if ($obj->added_value_tax_id != null) {
            $first = $this->taxSettingService->find($obj->added_value_tax_id);
            $first_value = ($obj->purchase_value * $first->value) / 100;
            $this->storeConstraintDetails($constraint->id, $first_value, 0, $first->account_id, "debtor");
            $value -= $first_value;
        }

        $this->storeConstraintDetails($constraint->id, 0, $total_sales_price, $obj->debit_account_id, "debtor");
        if ($obj->principle->principlePerished != null) {
            $this->storeConstraintDetails($constraint->id, 0, $difference, $obj->principle->principlePerished->perished_account_id, "debtor");
        }

        $this->storeConstraintDetails($constraint->id, $obj->total_tax_value, 0, $obj->tax_account_id, "creditor");
        $this->storeConstraintDetails($constraint->id, $obj->principle_value, 0, $principle_account_id, "creditor");
        if ($obj->sales_value > $obj->financial_value) {
            $account = $this->accountService->getWhereFirst(['type' => AccountTypesEnum::CapitalProfits->value]);
            if (is_null($account)) {
                return jsonValid(['error' => [__("finance.this_account_type_not_found")]], '');
            }
            $this->storeConstraintDetails($constraint->id, abs($obj->sales_value - $obj->financial_value), 0, $account->id, "creditor");
        } else {
            $account = $this->accountService->getWhereFirst(['type' => AccountTypesEnum::CapitalLosses->value]);
            if (is_null($account)) {
                return jsonValid(['error' => [__("finance.this_account_type_not_found")]], '');
            }
            $this->storeConstraintDetails($constraint->id, 0, abs($obj->sales_value - $obj->financial_value), $account->id, "debtor");
        }

        $obj->update(['constraint_id' => $constraint->id]);
    }

    public function storeConstraintDetails($constraintId, $debtor_value, $creditor_value, $account_id, $type)
    {
        $yearId = $this->yearService->getActiveYear()->id;
        $data = [
            "type" => $type,
            "debtor_value" => $debtor_value,
            "creditor_value" => $creditor_value,
            "account_id" => $account_id,
            "year_id" => $yearId,
            'statement' => "بيع اصل",
            "constraint_id" => $constraintId,
            "date" => date('Y-m-d'),
        ];

        $this->constraintDetailsService->storeConstraintDetails($data);
    }
}
