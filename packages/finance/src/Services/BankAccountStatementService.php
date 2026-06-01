<?php

namespace Nami\Finance\Services;

use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\Constraint;

class BankAccountStatementService extends MainService
{
    protected AccountService $accountService;
    protected CostCenterService $costCenterService;
    protected ConstraintDetailsService $constraintDetailsService;
    protected YearService $yearService;
    protected ConstraintFileService $constraintFileService;
    protected ConfirmSettingService $confirmSettingService;

    public function __construct(
        Constraint               $model,
        AccountService           $accountService,
        CostCenterService        $costCenterService,
        ConstraintDetailsService $constraintDetailsService,
        YearService              $yearService,
        ConstraintFileService    $constraintFileService,
        ConfirmSettingService    $confirmSettingService
    )
    {
        $this->model = $model;
        $this->accountService = $accountService;
        $this->costCenterService = $costCenterService;
        $this->constraintDetailsService = $constraintDetailsService;
        $this->yearService = $yearService;
        $this->constraintFileService = $constraintFileService;
        $this->confirmSettingService = $confirmSettingService;
    }

    public function getAccounts($type = null)
    {
        return $this->accountService->getAccountWithNoChild($type);
    }

    public function getCostCenters()
    {
        return $this->costCenterService->get();
    }

    public function lastCode()
    {
        $row = $this->model->orderBy('code', 'DESC')->first();
        return (isset($row->code)) ? ($row->code + 1) : 1;
    }

    public function storeConstraint($data,$getActiveYear,$type = ConstraintTypiesEnum::DailyEntry)
    {
        $year = $getActiveYear;
        $data['year_id'] = $year->id;
        $data['type'] = $type;
        return $this->store($data);
    }

    public function storeFiles($constraint, $data)
    {
        if (isset($data['file'])) {
            $this->constraintFileService->storeFiles($constraint, $data['file']);
        }
        return true;
    }

    public function storeConstraintDetails(array $accountIds, int $constraintId, $request,$year_id = null,$statement = null,$date = null)
    {
        $yearId = $year_id ?? $this->yearService->getActiveYear()->id;

        foreach ($accountIds as $key => $accountId) {
            $data = [
                "type" => $request['account_type'][$key],
                "debtor_value" => $request['debtor_value'][$key],
                "creditor_value" => $request['creditor_value'][$key],
                "statement" => $statement ?? $request['statement_account'][$key],
                "account_id" => $accountId,
                "cost_center_id" => $request['cost_center_id'][$key] ?? '',
                "year_id" => $yearId,
                "constraint_id" => $constraintId,
                "date" => $date,
            ];

            $this->constraintDetailsService->storeConstraintDetails($data);
        }
    }


    public function updateConstraint($id, array $data, $request,$getActiveYear)
    {
        $year = $getActiveYear;
        $data['year_id'] = $year->id;
        $data['type'] = ConstraintTypiesEnum::DailyEntry;
        $constraint = $this->find($id);
        $constraint->update($data);
        return $constraint;
    }

    public function updateConstraintDetails(int $constraintId, array $accountIds, $request)
    {
        $yearId = $this->yearService->getActiveYear()->id;

        foreach ($accountIds as $key => $accountId) {
            $data = [
                "type" => $request['account_type'][$key],
                "debtor_value" => $request['debtor_value'][$key],
                "creditor_value" => $request['creditor_value'][$key],
                "statement" => $request['statement_account'][$key],
                "account_id" => $accountId,
                "cost_center_id" => $request['cost_center_id'][$key],
                "year_id" => $yearId,
                "constraint_id" => $constraintId,
            ];

            if (isset($request['old_id'][$key])) {
                $this->constraintDetailsService->updateConstraintDetails($request['old_id'][$key], $data);
            } else {
                $this->constraintDetailsService->storeConstraintDetails($data);
            }
        }
    }

    public function calculateTotal($constraintDetails, $type): string
    {
        $accounts = '';
        $total = 0;
        foreach ($constraintDetails as $con) {
            if ($con->type === $type) {
                $accounts .= $con->account->name . " <br> ";
                $total += $con->type === 'debtor' ? $con->debtor_value : $con->creditor_value;
            }
        }
        return $accounts . " بأجمالي " . ($total ?? 0);
    }

    public function getConfirmSetting($type)
    {
        return $this->confirmSettingService->getConfirmSetting($type);
    }

    public function storeFirstBalanceConstraint($data)
    {
        $data['code'] = $this->lastCode();
        $data['type'] = ConstraintTypiesEnum::FirstBalance;
        return $this->store($data);
    }

    public function findByYearAndType($year_id,$type)
    {
        return $this->model->query()->where('year_id',$year_id)->where('type',$type)->first();
    }

    public function checkHasAnyConstraint($constraint) // لو في اي قيد تم اضافته من قبل لتلك السنة
    {
        if ($constraint) {
            return $this->model->query()
                ->where('year_id', $constraint->year_id)
                ->where('type', '!=', ConstraintTypiesEnum::FirstBalance)
                ->exists();
        } else {
            return false;
        }
    }

    /* ----------NEW------------ */
    public function getFirstBalance($dataSearch)
    {
        if($dataSearch["from_date"] != ""){
            $accountId = $dataSearch["account_id"];  // Replace with your desired account_id
            $customDate = $dataSearch["from_date"];  // Replace with your desired date
            return  DB::table('fn_constraint_details')->select(\DB::raw('SUM(fn_constraint_details.debtor_value) as sum_debtor_value, fn_accounts.status ,
                                           SUM(fn_constraint_details.creditor_value) as sum_creditor_value'))
                                        ->join('fn_accounts', 'fn_accounts.id', '=', 'fn_constraint_details.account_id')
                                        ->join('fn_constraints', 'fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
                                        ->whereNull('fn_constraint_details.deleted_at')
                                        ->whereNull('fn_constraints.deleted_at')
                                        ->where('fn_constraint_details.account_id', $accountId)
                                        ->where('fn_constraint_details.date', '<', $customDate)
                                        ->first();
        }
        return null;
    }
    public function filterAccount($dataSearch)
    {
        $accountStatment = DB::table('fn_constraint_details')
            ->select([
                'fn_constraint_details.date',
                'fn_bond_checks.check_num',
                'fn_bond_checks.check_value',
                'fn_bond_checks.check_date',
                'fn_bonds.payment_type',
                'fn_bonds.transfer_number',
                'fn_constraint_details.statement',
                'fn_constraint_details.debtor_value',
                'fn_constraint_details.creditor_value',
                'fn_accounts.status',
                'fn_constraints.type as constraints_type',
                'fn_constraint_details.constraint_id',
                'admins.name',
            ])
            ->join('fn_bond_checks', 'fn_bond_checks.constraint_id', '=', 'fn_constraint_details.constraint_id')
            ->join('fn_bonds', 'fn_bonds.constraint_id', '=', 'fn_constraint_details.constraint_id')
            ->join('fn_accounts', 'fn_accounts.id', '=', 'fn_constraint_details.account_id')
            ->join('fn_constraints', 'fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
            ->join('admins', 'admins.id', '=', 'fn_constraints.created_by_id')
            ->whereNull('fn_constraint_details.deleted_at')
            ->whereNull('fn_constraints.deleted_at')
            ->where('fn_constraint_details.account_id', '=', $dataSearch['account_id'])
            ->when($dataSearch['type'] != 'both', function ($q) use ($dataSearch) {
                return $q->where('fn_constraint_details.type', $dataSearch['type']);
            })
            ->when($dataSearch['from_date'] != '', function ($q) use ($dataSearch) {
                return $q->whereDate('fn_constraint_details.date', '>=', $dataSearch['from_date']);
            })
            ->when($dataSearch['to_date'] != '', function ($q) use ($dataSearch) {
                return $q->whereDate('fn_constraint_details.date', '<=', $dataSearch['to_date']);
            })
            ->orderBy('fn_constraint_details.date')
            ->orderBy('fn_constraints.id')
            ->get();
//dd($accountStatment);
        return $accountStatment;
    }

    public function filterConstraints($dataSearch)
    {
        $account_id = $dataSearch['account_id'];
        $otherAccounts = $this->model->select([
            'fn_constraints.id',
        ])
        ->join('fn_constraint_details', 'fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
        ->where('fn_constraint_details.account_id', '=', $dataSearch['account_id'])
        ->when($dataSearch['type'] != 'both', function ($q) use ($dataSearch) {
            return $q->where('fn_constraint_details.type', $dataSearch['type']);
        })
        ->when($dataSearch['from_date'] != '', function ($q) use ($dataSearch) {
            return $q->whereDate('fn_constraint_details.date', '>=', $dataSearch['from_date']);
        })
        ->when($dataSearch['to_date'] != '', function ($q) use ($dataSearch) {
            return $q->whereDate('fn_constraint_details.date', '<=', $dataSearch['to_date']);
        })
        ->with(['accounts' => function ($query) use ($account_id) {
            return $query->where('account_id', '!=', $account_id);
        }])
        ->get();
        $result = [];
        foreach ($otherAccounts as $one) {
            $result[$one->id]['accounts'] = $one->accounts;
        }
        return $result;
    }
}
