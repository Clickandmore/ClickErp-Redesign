<?php

namespace Nami\Finance\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\Constraint;

class AuditBudgetService extends MainService
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


    public function storeFirstBalanceConstraint($data)
    {
        $data['code'] = $this->lastCode();
        $data['type'] = ConstraintTypiesEnum::FirstBalance;
        return $this->store($data);
    }

    public function getYear()
    {
        return $this->yearService->getWhere(['status'=>1])->first();
    }


    /* ----------NEW------------ */
    public function filterAccount($dataSearch)
    {
        $accountStatment = DB::table('fn_constraint_details')
            ->select([
                'fn_constraint_details.date',
                DB::raw('SUM(fn_constraint_details.debtor_value) as sum_debtor_value'),
                DB::raw('SUM(fn_constraint_details.creditor_value) as sum_creditor_value'),
                'fn_constraint_details.statement',
                'fn_accounts.code as account_code',
                'fn_accounts.name as account_name',
                'fn_accounts.status as account_status',
                'fn_constraints.type as constraints_type',
                'fn_constraint_details.constraint_id',
                'admins.name',
                'fn_constraint_details.account_id'
            ])
            ->join('fn_accounts', 'fn_accounts.id', '=', 'fn_constraint_details.account_id')
            ->join('fn_constraints', 'fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
            ->join('admins', 'admins.id', '=', 'fn_constraints.created_by_id')
            ->where('fn_accounts.type', '=', $dataSearch['type'])
            ->when($dataSearch['from_date'] != '', function ($q) use ($dataSearch) {
                return $q->whereDate('fn_constraint_details.date', '>=', $dataSearch['from_date']);
            })
            ->when($dataSearch['to_date'] != '', function ($q) use ($dataSearch) {
                return $q->whereDate('fn_constraint_details.date', '<=', $dataSearch['to_date']);
            })
            ->groupBy('fn_constraint_details.account_id')
            ->get()
            ->keyBy('account_id');

        return $accountStatment;
    }
    public function filterFirstAccount($dataSearch)
    {
        $accountStatment = DB::table('fn_constraint_details')
            ->select([
                'fn_constraint_details.date',
                DB::raw('SUM(fn_constraint_details.debtor_value) as sum_debtor_value'),
                DB::raw('SUM(fn_constraint_details.creditor_value) as sum_creditor_value'),
                'fn_constraint_details.statement',
                'fn_accounts.code as account_code',
                'fn_accounts.name as account_name',
                'fn_accounts.status as account_status',
                'fn_constraints.type as constraints_type',
                'fn_constraint_details.constraint_id',
                'admins.name',
                'fn_constraint_details.account_id'
            ])
            ->join('fn_accounts', 'fn_accounts.id', '=', 'fn_constraint_details.account_id')
            ->join('fn_constraints', 'fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
            ->join('admins', 'admins.id', '=', 'fn_constraints.created_by_id')
            ->where('fn_accounts.type', '=', $dataSearch['type'])
            ->when($dataSearch['start_date'] != '', function ($q) use ($dataSearch) {
                return $q->whereDate('fn_constraint_details.date', '>=', $dataSearch['start_date']);
            })
            ->when($dataSearch['from_date'] != '', function ($q) use ($dataSearch) {
                return $q->whereDate('fn_constraint_details.date', '<=', $dataSearch['from_date']);
            })
            ->groupBy('fn_constraint_details.account_id')
            ->get()
            ->keyBy('account_id');

        return $accountStatment;
    }

    /* ----------NEW------------ */
    public function filterAccountMonthly($dataSearch)
    {

        $accountStatements = DB::table('fn_constraint_details')
            ->select([
                'fn_constraint_details.account_id',
                DB::raw('DATE_FORMAT(fn_constraint_details.date, "%m") as month'),
                DB::raw('SUM(fn_constraint_details.debtor_value) as sum_debtor_value'),
                DB::raw('SUM(fn_constraint_details.creditor_value) as sum_creditor_value'),
                'fn_accounts.code as account_code',
                'fn_accounts.name as account_name',
                'fn_accounts.status as account_status',
                'fn_constraints.type as constraints_type',
                'fn_constraint_details.constraint_id',
            ])
            ->join('fn_accounts', 'fn_accounts.id', '=', 'fn_constraint_details.account_id')
            ->join('fn_constraints', 'fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
            ->where('fn_accounts.type', '=', $dataSearch['type'])
            ->when($dataSearch['from_date'] != '', function ($q) use ($dataSearch) {
                $dataSearch['from_date'] = $dataSearch['from_date'].'-01';
                return $q->whereDate('fn_constraint_details.date', '>=', $dataSearch['from_date']);
            })
            ->when($dataSearch['to_date'] != '', function ($q) use ($dataSearch) {
                $dataSearch['to_date'] = $dataSearch['to_date'].'-31';
                return $q->whereDate('fn_constraint_details.date', '<=', $dataSearch['to_date']);
            })
            ->groupBy('fn_constraint_details.account_id', DB::raw('DATE_FORMAT(fn_constraint_details.date, "%Y-%m")'))

            ->groupBy('fn_constraint_details.constraint_id')
            ->get();
        $months_keys = [];
        $organizedData = $accountStatements->mapToGroups(function ($item) use (&$months_keys) {
            $months_keys[] = $item->month;
            return [
                $item->account_id => [
                    'account_name' => $item->account_name, // إضافة الاسم خارج الأشهر
                    $item->month => [
                        'sum_debtor_value' => $item->sum_debtor_value,
                        'sum_creditor_value' => $item->sum_creditor_value,
                        'account_code' => $item->account_code,
                        'account_status' => $item->account_status,
                        'constraints_type' => $item->constraints_type,
                        'constraint_id' => $item->constraint_id,
                    ],
                ],
            ];
        });

// تحويل البيانات بحيث يكون الاسم في المستوى الأعلى فقط
        // تنظيم البيانات بحيث يكون اسم الحساب في المستوى الأعلى فقط
        $months_keys =  array_unique($months_keys);
       sort($months_keys);

        $finalData = $organizedData->map(function ($months)  {
            // استخدم اسم الحساب من المستوى الأعلى فقط
            $keys = array_keys($months->first());

            $accountName = $months->first()['account_name'];
            $accountType = $months->first()[$keys[1]]['account_status'];
            // تحديث مفاتيح الأشهر
            $months_keys[] = $keys[1]; // دمج وتصفية المفاتيح

            // تنظيم الأشهر مع إزالة تكرار اسم الحساب
            $monthDetails = $months->mapWithKeys(function ($details, $month) {

                return  Arr::except($details, ['account_name']); // إزالة اسم الحساب من التفاصيل
            });

            return [
                'account_name' => $accountName,
                'type' => $accountType,
                'months' => $monthDetails,
            ];
        });

        return ['months_keys' => $months_keys,'data' => $finalData] ;
    }


    public function filterFirstAccountMonthly($dataSearch)
    {

        $accountStatment = DB::table('fn_constraint_details')
            ->select([
                'fn_constraint_details.date',
                DB::raw('SUM(fn_constraint_details.debtor_value) as sum_debtor_value'),
                DB::raw('SUM(fn_constraint_details.creditor_value) as sum_creditor_value'),
                'fn_constraint_details.statement',
                'fn_accounts.code as account_code',
                'fn_accounts.name as account_name',
                'fn_accounts.status as account_status',
                'fn_constraints.type as constraints_type',
                'fn_constraint_details.constraint_id',
                'fn_constraint_details.account_id'
            ])
            ->join('fn_accounts', 'fn_accounts.id', '=', 'fn_constraint_details.account_id')
            ->join('fn_constraints', 'fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
           ->where('fn_accounts.type', '=', $dataSearch['type'])
            ->when($dataSearch['start_date'] != '', function ($q) use ($dataSearch) {
                return $q->whereDate('fn_constraint_details.date', '>=', $dataSearch['start_date']);
            })
            ->when($dataSearch['from_date'] != '', function ($q) use ($dataSearch) {
                $dataSearch['from_date'] = $dataSearch['from_date'].'-01';
                return $q->whereDate('fn_constraint_details.date', '<=', $dataSearch['from_date']);
            })
            ->groupBy('fn_constraint_details.account_id')
            ->get()
            ->keyBy('account_id');

        return $accountStatment;
    }
}
