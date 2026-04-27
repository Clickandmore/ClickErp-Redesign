<?php

namespace Nami\Finance\Services;

use Nami\Finance\Enums\AccountPerishedTypeEnum;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\Enums\PerishedTypeEnum;
use Nami\Finance\Enums\RegistrationTypeEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\Principle;

//use App\Services\Hr\EmployeeService;

class PrincipleService extends MainService
{
    public function __construct(Principle $model,protected AccountService $accountService,
    protected PrincipleWebsiteService $principleWebsiteService,
    protected PrincipleHistoryService $principleHistoryService,
    protected PrinciplePerishedService $principlePerishedService,
    protected PrincipleImageService $principleImageService,
    protected TaxSettingService $taxSettingService,
    protected ConstraintService $constraintService,
    protected ConstraintDetailsService $constraintDetailsService,
    protected YearService $yearService,
    protected DepreciationPrincipleService $depreciationPrincipleService,
//    protected EmployeeService $employeeService
    )
    {
        $this->model = $model;
        $this->fileFolder = 'principles/';
        $this->files = ['image'];
    }

    public function getPrincipleTransferDataTable()
    {
        return $this->model->query()->where('principle_website_id', '!=', null)->latest();
    }

    public function getPrinciplesByPerishedTypes()
    {
        return $this->model->where('perished_type', '!=', PerishedTypeEnum::Without->value)->get();
    }

    public function storePrinciple($data)
    {
        $code = $this->getAccountCode($data['parent_account_id']);
        $data['code'] = $data['code'] ?? $code;
        return $this->store($data);
    }
    public function update($id, $data)
    {
       return parent::update($id, $data);
    }

    public function getPrincipleAndTotalConstraintValue()
    {
        return $this->model->with(['depreciationPrinciple.constraint'])
            ->get()
            ->map(function ($principle) {
                $principle->total_constraint_value = $principle->depreciationPrinciple->sum(function ($depreciationPrinciple) {
                    return $depreciationPrinciple->constraint ? $depreciationPrinciple->constraint->value : 0;
                });
                return $principle;
            });
    }

    public function getTypes()
    {
        $type = $this->accountService->findByType(AccountTypesEnum::FixedPrinciple);
        return $this->accountService->parentAccounts(@$type->id);
    }
    public function getAccounts()
    {
        return $this->accountService->getAccountWithNoChild();
    }

    public function getPerishedTypes()
    {
        return PerishedTypeEnum::cases();
    }

    public function getRegistrationTypes()
    {
        return RegistrationTypeEnum::cases();
    }

    public function getAccountPerishedTypes()
    {
        return AccountPerishedTypeEnum::cases();
    }


    public function getPrincipleWebsites()
    {
        return $this->principleWebsiteService->get();
    }

//    public function getEmployees()
//    {
//        return $this->employeeService->get();
//    }

    public function getTaxs()
    {
        return $this->taxSettingService->get();
    }

    public function storePrinciplePerished($data, $principle_id)
    {
        if ($data['perished_type'] != PerishedTypeEnum::Without->value) {
            if ($data['account_type'] == AccountPerishedTypeEnum::Private->value) {
                $accounts = $this->handleAccountPerishedType($data);
                $data['perished_account_id'] = $accounts['perished_account']->id;
                $data['expense_account_id'] = $accounts['expense_account']->id;
            }
            $data['principle_id'] = $principle_id;
            $this->principlePerishedService->store($data);
        }
    }

    public function updatePrinciplePerished($data, $principle_id)
    {
        $obj = $this->find($principle_id);
        $perishedAccountId = @$obj->principlePerished->perished_account_id;
        $expenseAccountId = @$obj->principlePerished->expense_account_id;
        $accountType = @$obj->principlePerished->account_type;
        if (isset($obj->principlePerished)) {
            $obj->principlePerished->delete();
        }
        if ($data['perished_type'] != PerishedTypeEnum::Without->value) {
            $data['perished_account_id'] = $perishedAccountId;
            $data['expense_account_id'] = $expenseAccountId;
            $data['account_type'] = $accountType;
            $data['principle_id'] = $principle_id;
            $this->principlePerishedService->store($data);
        }
    }

    public function handleAccountPerishedType($data) {

        $perishedAccount = $this->storePerishedAccount($data);
        $expenseAccount = $this->storeExpenseAccount($data);
        return [
            'perished_account' => $perishedAccount,
            'expense_account' => $expenseAccount,
        ];
    }

    public function storePrincipleHistory($dataInsert, $id)
    {
        $data = [
            'from_website_id' => $dataInsert['from_website_id'],
            'to_website_id' => $dataInsert['to_website_id'],
            'principle_id' => $id
        ];
        return $this->principleHistoryService->store($data);
    }

    public function storeFiles($data, $principle_id) {
        $this->principleImageService->storeFiles($data, $principle_id);
    }

    public function getAccountCode($account_id) {
        $parentPluckCode = $this->accountService->parentPluckCode();
        $childPluckCode = $this->accountService->childPluckCode($account_id);
        $code = $this->accountService->getChildCode($account_id,$childPluckCode,$parentPluckCode);
        return $code;
    }

    public function storePerishedAccount($data)
    {
        $account = $this->accountService->getWhereFirst(['type' =>  AccountTypesEnum::DepreciationProvision->value]);
        $code = $this->getAccountCode($account->id);
        $data = [
            'name' => 'مخصص اهلاك' . ' ' . $data['name'],
            'status' => $account->status,
            'type' => AccountTypesEnum::NormalAccount->value,
            'operation_type' => $account->operation_type,
            'parent_id' => $account->id,
            'level' => $account->level + 1,
            'code' => $code,
        ];
        return $this->accountService->store($data);
    }

    public function storeExpenseAccount($data)
    {
        $account = $this->accountService->getWhereFirst(['type' =>  AccountTypesEnum::DepreciationExpenses->value]);
        $code = $this->getAccountCode($account->id);
        $data = [
            'name' => 'مصروفات اهلاك' . ' ' . $data['name'],
            'status' => $account->status,
            'type' => AccountTypesEnum::NormalAccount->value,
            'operation_type' => $account->operation_type,
            'parent_id' => $account->id,
            'level' => $account->level + 1,
            'code' => $code,
        ];
        return $this->accountService->store($data);
    }

    public function storeAccount($data)
    {
        $account = $this->accountService->find($data['parent_account_id']);
        $code = $this->getAccountCode($data['parent_account_id']);
        $data = [
            'name' => $data['name'],
            'status' => $account->status,
            'type' => AccountTypesEnum::NormalAccount->value,
            'operation_type' => $account->operation_type,
            'parent_id' => $data['parent_account_id'],
            'level' => $account->level + 1,
            'code' => $code,
        ];
        return $this->accountService->store($data);
    }

    public function checkDateRange($date) {
        return $this->yearService->checkDateRange($date);
    }

    public function storeConstraint($obj)
    {
        $code = $this->constraintService->lastCode();
        $yearId = $this->yearService->getActiveYear()->id;
        $type = ($obj->registration_type == RegistrationTypeEnum::Purchase->value) ? ConstraintTypiesEnum::Purchases->value : ConstraintTypiesEnum::FirstBalance->value;
        $statement = ($obj->registration_type == RegistrationTypeEnum::Purchase) ? "شراء اصل" : "تسجيل اصل";

        $data = [
            'type' => $type,
            'value' => $obj->purchase_value,
            'date' => date('Y-m-d'),
            'code' => $code,
            'year_id' => $yearId,
            'statement' => $statement,
            'created_by_id' => auth('admin')->user()->id,
        ];
        $value = $obj->purchase_value;
        $constraint = $this->constraintService->store($data);
        if ($obj->first_tax != null) {
            $first = $this->taxSettingService->find($obj->first_tax);
            $first_value = ($obj->purchase_value * $first->value) / 100;
            $this->storeConstraintDetails($constraint->id, $first_value, 0, $first->account_id, "debtor",$statement);
            $value -= $first_value;
        }

        if ($obj->second_tax != null) {
            $second = $this->taxSettingService->find($obj->second_tax);
            $second_value = ($obj->purchase_value * $second->value) / 100;
            $this->storeConstraintDetails($constraint->id, $second_value, 0, $second->account_id, "debtor",$statement);
            $value -= $second_value;
        }
        $this->storeConstraintDetails($constraint->id, 0, $obj->purchase_value, $obj->creditor_account_id, "creditor",$statement);
        $this->storeConstraintDetails($constraint->id, $value, 0, $obj->account_id, "debtor",$statement);

    }

    public function storeConstraintDetails($constraintId, $debtor_value, $creditor_value, $account_id, $type, $statement)
    {
        $yearId = $this->yearService->getActiveYear()->id;
        $data = [
            "type" => $type,
            "debtor_value" => $debtor_value,
            "creditor_value" => $creditor_value,
            "account_id" => $account_id,
            "year_id" => $yearId,
            'statement' => $statement ?? "-",
            "constraint_id" => $constraintId,
            "date" => date('Y-m-d'),
        ];

        $this->constraintDetailsService->storeConstraintDetails($data);
    }


    public function storeManualDepreciationConstraint($data)
    {
        $yearId = $this->yearService->getActiveYear()->id;
        $dataInsert = [
            'type' => ConstraintTypiesEnum::ManualDepreciation->value ,
            'value' => $data['value'],
            'date' => $data['date'],
            'year_id' => $yearId,
            'created_by_id' => auth('admin')->user()->id,
            'statement' => $data['statement'],
        ];
        $constraint = $this->constraintService->store($dataInsert);

        $this->storeManualDepreciationConstraintDetails($constraint, 0, $data['value'], $data['perished_account_id'], "creditor");
        $this->storeManualDepreciationConstraintDetails($constraint, $data['value'], 0, $data['expense_account_id'], "debtor");
        return $constraint;
    }

    public function storeManualDepreciationConstraintDetails($constraint, $debtor_value, $creditor_value, $account_id, $type)
    {
        $yearId = $this->yearService->getActiveYear()->id;
        $data = [
            "type" => $type,
            "debtor_value" => $debtor_value,
            "creditor_value" => $creditor_value,
            "account_id" => $account_id,
            "constraint_id" => $constraint->id,
            "statement" => $constraint->statement ?? "-",
            "year_id" => $yearId,
            "date" => $constraint->date,
        ];
        $this->constraintDetailsService->storeConstraintDetails($data);
    }

    public function storeDepreciationPrinciple($data, $constraintId) {
        $data['constraint_id'] = $constraintId;
        $this->depreciationPrincipleService->store($data);
    }

    public function updateManualDepreciationConstraint($id, $data)
    {
        $dataInsert = [
            'value' => $data['value'],
            'date' => $data['date'],
            'statement' => $data['statement'],
        ];
        $constraint = $this->constraintService->update($id,$dataInsert);
        return $constraint;
    }

    /* PRINCIPLES REPORTS */

    public function getPrincipleReport($request)
    {
        // Fetch principles with required relationships
        $principles = $this->model->with([
            'firstTax',
            'secondTax',
            'depreciationPrinciple.constraint',
            'principle_website',
            'parent_account',
            'employee'
        ])->where('parent_account_id', $request->account_id)->get();

        // Map and prepare the data for display
        $data_report = $principles->map(function ($principle) {
            $first_percent = $principle->first_tax ? $principle->firstTax->value : 0;
            $second_percent = $principle->second_tax ? $principle->secondTax->value : 0;
            $first_value = ($principle->purchase_value * $first_percent) / 100;
            $second_value = ($principle->purchase_value * $second_percent) / 100;
            $depreciated_value = $principle->depreciationPrinciple->sum(function ($depreciation) {
                return $depreciation->constraint ? $depreciation->constraint->value : 0;
            });
            $financial_value = $principle->purchase_value - $depreciated_value;

            return [
                'principle_code' => $principle->code,
                'principle_name' => $principle->name,
                'principle_site' => $principle->principle_website ? $principle->principle_website->title : "",
                'responsible_employee' => $principle->employee_id ? $principle->employee->name : "",
                'purchase_date' => $principle->purchase_date,
                'purchase_value' => $principle->purchase_value,
                'first_value' => $first_value,
                'second_value' => $second_value,
                'depreciated_value' => $depreciated_value,
                'financial_value' => $financial_value,
            ];
        });

        // Fetch account information to display in the report header
        $account = $this->accountService->find($request->account_id);
        $group_code = $account ? $account->code : "";
        $group_name = $account ? $account->name : "";

        return [
            'data_report' => $data_report,
            'group_code' => $group_code,
            'group_name' => $group_name,
        ];
    }

    public function getPrincipleReportWithConstraints($request)
    {
        if (isset($request['type_search'])){
            // Fetch principles with related data and apply date filtering if provided
            $principles = $this->model->with([
                'firstTax',
                'secondTax',
                'depreciationPrinciple.constraint' => function ($query) use ($request) {
                    if ($request->filled('from_date') && $request->filled('to_date')) {
                        $query->whereBetween('date', [$request->from_date, $request->to_date]);
                    }
                    $query->orderBy('date', 'asc');
                },
                'parent_account'
            ])->where('account_id', $request->account_id)->get();
        }
        else{
            // Fetch principles with related data and apply date filtering if provided
            $principles = $this->model->with([
                'firstTax',
                'secondTax',
                'depreciationPrinciple.constraint' => function ($query) use ($request) {
                    if ($request->filled('from_date') && $request->filled('to_date')) {
                        $query->whereBetween('date', [$request->from_date, $request->to_date]);
                    }
                    $query->orderBy('date', 'asc');
                },
                'parent_account'
            ])->where('parent_account_id', $request->account_id)->get();
        }
        // Map and prepare each principle’s data, including constraints
        $data_report = $principles->map(function ($principle) {
            $constraints = $principle->depreciationPrinciple->flatMap(function ($depreciation) {
                return $depreciation->constraint ? [
                    [
                        'date' => $depreciation->constraint->date,
                        'value' => $depreciation->constraint->value,
                        'statement' => $depreciation->constraint->statement,
                    ]
                ] : [];
            });

            return [
                'principle_code' => $principle->code,
                'principle_name' => $principle->name,
                'constraints' => $constraints->all(),
            ];
        });

        // Get account details for the header
        $account = $this->accountService->find($request->account_id);
        $group_code = $account ? $account->code : "";
        $group_name = $account ? $account->name : "";

        return [
            'data_report' => $data_report,
            'group_code'  => $group_code,
            'group_name'  => $group_name,
            'principles'  => $principles,
            'type_search' => $request['type_search'],
        ];
    }

}
