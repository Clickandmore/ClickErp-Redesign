<?php

namespace Nami\Finance\Services;

use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\ConstraintDetails;

class ConstraintDetailsService extends MainService
{
    public $model;
    protected AccountService $accountService;
    protected CostCenterService $costCenterService;

    public function __construct(ConstraintDetails $model, AccountService $accountService, CostCenterService $costCenterService)
    {
        $this->model = $model;
        $this->accountService = $accountService;
        $this->costCenterService = $costCenterService;
    }

    public function storeConstraintDetails(array $data)
    {
        return $this->store($data);
    }

    public function updateConstraintDetails($id, array $data)
    {
        return $this->find($id)->update($data);
    }

    public function filterAccount(array $dataSearch): array
    {
        return $this->accountService->filterAccount($dataSearch);
    }

    public function filterPurchases(array $dataSearch): array
    {
        return $this->accountService->filterPurchases($dataSearch);
    }

    public function filterAccountStatement(array $dataSearch)
    {
        $account = Constraint::query()
            ->with(['constraintDetails' => function ($query) use ($dataSearch) {
                $query->when($dataSearch['account_id'], function ($query) use ($dataSearch) {
                    return $query->where('account_id', $dataSearch['account_id']);
                })
                    ->when($dataSearch['from_date'], function ($query) use ($dataSearch) {
                        return $query->where('date', '>=', $dataSearch['from_date']);
                    })
                    ->when($dataSearch['to_date'], function ($query) use ($dataSearch) {
                        return $query->where('date', '<=', $dataSearch['to_date']);
                    })
                    ->when($dataSearch['transaction_type'] && $dataSearch['transaction_type'] != 'all', function ($query) use ($dataSearch) {
                        return $query->where('type', $dataSearch['transaction_type']);
                    })
                    ->when($dataSearch['show_counter_account'] && $dataSearch['show_counter_account'] != 'no', function ($query) use ($dataSearch) {
                        // Additional filtering if needed
                    });
            }])
            ->leftJoin('fn_constraint_details as primary_account', function ($join) {
                $join->on('fn_constraints.id', '=', 'primary_account.constraint_id')
                    ->where('primary_account.type', '=', 'debtor')
                    ->whereNull('primary_account.deleted_at');
            })
            ->leftJoin('fn_constraint_details as counter_account', function ($join) {
                $join->on('fn_constraints.id', '=', 'counter_account.constraint_id')
                    ->where('counter_account.type', '=', 'creditor')
                    ->whereNull('counter_account.deleted_at');
            })
            ->leftJoin('fn_accounts as primary_account_details', function ($join) {
                $join->on('primary_account_details.id', '=', 'primary_account.account_id');
            })
            ->leftJoin('fn_accounts as counter_account_details', function ($join) {
                $join->on('counter_account_details.id', '=', 'counter_account.account_id');
            })
            ->select(
                'fn_constraints.*',
                'primary_account_details.name as primary_account_name',
                'counter_account_details.name as counter_account_name'
            )
            ->groupBy('fn_constraints.id')
            ->get();

//            $this->model
//            ->when($dataSearch['account_id'], function ($query) use ($dataSearch) {
//                return $query->where('fn_constraint_details.account_id', $dataSearch['account_id']);
//            })
//            ->when($dataSearch['from_date'], function ($query) use ($dataSearch) {
//                return $query->where('fn_constraint_details.date', '>=', $dataSearch['from_date']);
//            })
//            ->when($dataSearch['to_date'], function ($query) use ($dataSearch) {
//                return $query->where('fn_constraint_details.date', '<=', $dataSearch['to_date']);
//            })
//            ->when($dataSearch['transaction_type'] && $dataSearch['transaction_type'] != 'all', function ($query) use ($dataSearch) {
//                return $query->where('fn_constraint_details.type', $dataSearch['transaction_type']);
//            })
//            ->when($dataSearch['show_counter_account'] && $dataSearch['show_counter_account'] != 'no', function ($query) use ($dataSearch) {
////                return $query->where('account_id', '!=', $dataSearch['account_id']);
//            })
//            // Join with the same table for primary account
//            ->leftJoin('fn_constraint_details as primary_account', function ($join) {
//                $join->on('fn_constraint_details.constraint_id', '=', 'primary_account.constraint_id')
//                    ->where('primary_account.type', '=', 'debtor');
//            })
//            // Join with the same table for counter account
//            ->leftJoin('fn_constraint_details as counter_account', function ($join) {
//                $join->on('fn_constraint_details.constraint_id', '=', 'counter_account.constraint_id')
//                    ->where('counter_account.type', '=', 'creditor');
//            })
//            ->leftJoin('fn_accounts as primary_account_details', function ($join) {
//                $join->on('primary_account_details.id', '=', 'primary_account.account_id');
//            })
//            // Join with the accounts table for the counter account
//            ->join('fn_accounts as counter_account_details', function ($join) {
//                $join->on('counter_account_details.id', '=', 'counter_account.account_id');
//            })
//            ->select('fn_constraint_details.*', 'primary_account_details.name as primary_account_name', 'counter_account_details.name as counter_account_name')
//            ->groupBy('fn_constraint_details.constraint_id')
//            ->get();

        return $account;
    }

    public function filterSales(array $dataSearch): array
    {
        return $this->accountService->filterSales($dataSearch);
    }

    public function getAccounts()
    {
        return $this->accountService->get();
    }

    public function constraintOfYear($year_id)
    {
        return $this->model->whereHas('constraint', function ($q) use ($year_id) {
            $q->where('year_id', $year_id)
                ->where('type', ConstraintTypiesEnum::FirstBalance);
             })
            ->select('account_id','constraint_id','debtor_value','creditor_value')
            ->get();
    }


    public function accounts_balances()
    {
        return ConstraintDetails::select(
            'account_id',
            DB::raw('SUM(debtor_value) as total_debtor'),
            DB::raw('SUM(creditor_value) as total_creditor')
        )
            ->groupBy('account_id')
            ->get();
    }



    public function treasury_and_bank_balances()
    {
        return ConstraintDetails::select(
            'account_id',
            \DB::raw('SUM(debtor_value) as total_debtor'),
            \DB::raw('SUM(creditor_value) as total_creditor')
        )
            ->join('fn_accounts', 'fn_accounts.id', '=', 'fn_constraint_details.account_id')
            ->whereIn('fn_accounts.type', [15, 17])
            ->groupBy('account_id')
            ->with('account.status') // لو عندك علاقة account -> status
            ->get();
    }


    public function  supliers_balances()
    {
        return ConstraintDetails::select(
            'account_id',
            \DB::raw('SUM(debtor_value) as total_debtor'),
            \DB::raw('SUM(creditor_value) as total_creditor')
        )
            ->join('fn_accounts', 'fn_accounts.id', '=', 'fn_constraint_details.account_id')
            ->where('fn_accounts.type', 12)
            ->groupBy('account_id')
            ->with('account.status') // لو عندك علاقة account -> status
            ->get();
    }

    public function  clients_balances()
    {
        return ConstraintDetails::select(
            'account_id',
            \DB::raw('SUM(debtor_value) as total_debtor'),
            \DB::raw('SUM(creditor_value) as total_creditor')
        )
            ->join('fn_accounts', 'fn_accounts.id', '=', 'fn_constraint_details.account_id')
            ->with('account.status') // only if the relationship exists
            ->where('fn_accounts.type', 7)
            ->groupBy('account_id')
            ->get(); // <- must return!

    }

    public function getConstraintsInDate($dateFrom, $dateTo)
    {
        return $this->model->whereBetween('date', [$dateFrom, $dateTo])->query();
    }

    public function getConstraintsInLevel($level)
    {
        return $this->model->where('accounts.level', '>=', $level);
    }

    public function getAccountReport() {
        return $this->model->groupBy('account_id')
        ->select('account_id', 'type',
            DB::raw('SUM(creditor_value) as creditor_value_total'),
            DB::raw('SUM(debtor_value) as debtor_value_total'))
        ->get();
    }

    public function filterIncomeList($request)
    {
        $constraints_ids = $this->model
            ->when($request->filled('from_date'), function ($q) use ($request) {
                return $q->whereDate("date", ">=", $request->from_date);
            })
            ->when($request->filled('to_date'), function ($q) use ($request) {
                return $q->whereDate("date", "<=", $request->to_date);
            })
            ->pluck('id')->toArray();

        return $this->accountService->getIncomeAccountsChildrens($constraints_ids);
    }
}
