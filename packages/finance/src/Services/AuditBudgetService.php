<?php

namespace Nami\Finance\Services;

use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\Constraint;

class AuditBudgetService extends MainService
{
    private const EXTERNAL_AGENTS_PARENT_CODE = 221;

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
        return $this->yearService->getReportPeriod();
    }


    /* ----------NEW------------ */
    public function filterAccount($dataSearch)
    {
        $accountStatment = $this->supplierReportAccountsQuery()
            ->select([
                DB::raw('MAX(fn_constraint_details.date) as date'),
                DB::raw('COALESCE(SUM(CASE WHEN fn_constraints.id IS NOT NULL THEN fn_constraint_details.debtor_value ELSE 0 END), 0) as sum_debtor_value'),
                DB::raw('COALESCE(SUM(CASE WHEN fn_constraints.id IS NOT NULL THEN fn_constraint_details.creditor_value ELSE 0 END), 0) as sum_creditor_value'),
                DB::raw('MAX(fn_constraint_details.statement) as statement'),
                'fn_accounts.code as account_code',
                'fn_accounts.name as account_name',
                'fn_accounts.status as account_status',
                DB::raw('MAX(fn_constraints.type) as constraints_type'),
                DB::raw('MAX(fn_constraint_details.constraint_id) as constraint_id'),
                DB::raw('NULL as name'),
                'fn_accounts.id as account_id'
            ])
            ->leftJoin('fn_constraint_details', function ($join) use ($dataSearch) {
                $join->on('fn_constraint_details.account_id', '=', 'fn_accounts.id')
                    ->whereNull('fn_constraint_details.deleted_at');

                if (($dataSearch['from_date'] ?? '') != '') {
                    $join->whereDate('fn_constraint_details.date', '>=', $dataSearch['from_date']);
                }

                if (($dataSearch['to_date'] ?? '') != '') {
                    $join->whereDate('fn_constraint_details.date', '<=', $dataSearch['to_date']);
                }
            })
            ->leftJoin('fn_constraints', function ($join) {
                $join->on('fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
                    ->whereNull('fn_constraints.deleted_at');
            })
            ->groupBy('fn_accounts.id', 'fn_accounts.code', 'fn_accounts.name', 'fn_accounts.status')
            ->get()
            ->keyBy('account_id');

        return $accountStatment;
    }
    public function filterFirstAccount($dataSearch)
    {
        $accountStatment = $this->supplierReportAccountsQuery()
            ->select([
                DB::raw('MAX(fn_constraint_details.date) as date'),
                DB::raw('COALESCE(SUM(CASE WHEN fn_constraints.id IS NOT NULL THEN fn_constraint_details.debtor_value ELSE 0 END), 0) as sum_debtor_value'),
                DB::raw('COALESCE(SUM(CASE WHEN fn_constraints.id IS NOT NULL THEN fn_constraint_details.creditor_value ELSE 0 END), 0) as sum_creditor_value'),
                DB::raw('MAX(fn_constraint_details.statement) as statement'),
                'fn_accounts.code as account_code',
                'fn_accounts.name as account_name',
                'fn_accounts.status as account_status',
                DB::raw('MAX(fn_constraints.type) as constraints_type'),
                DB::raw('MAX(fn_constraint_details.constraint_id) as constraint_id'),
                DB::raw('NULL as name'),
                'fn_accounts.id as account_id'
            ])
            ->leftJoin('fn_constraint_details', function ($join) use ($dataSearch) {
                $join->on('fn_constraint_details.account_id', '=', 'fn_accounts.id')
                    ->whereNull('fn_constraint_details.deleted_at');

                if (($dataSearch['start_date'] ?? '') != '') {
                    $join->whereDate('fn_constraint_details.date', '>=', $dataSearch['start_date']);
                }

                if (($dataSearch['from_date'] ?? '') != '') {
                    $join->whereDate('fn_constraint_details.date', '<=', $dataSearch['from_date']);
                }
            })
            ->leftJoin('fn_constraints', function ($join) {
                $join->on('fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
                    ->whereNull('fn_constraints.deleted_at');
            })
            ->groupBy('fn_accounts.id', 'fn_accounts.code', 'fn_accounts.name', 'fn_accounts.status')
            ->get()
            ->keyBy('account_id');

        return $accountStatment;
    }

    /* ----------NEW------------ */
    public function filterAccountMonthly($dataSearch)
    {
        $accounts = $this->supplierReportAccountsQuery()
            ->select([
                'fn_accounts.id as account_id',
                'fn_accounts.code as account_code',
                'fn_accounts.name as account_name',
                'fn_accounts.status as account_status',
            ])
            ->orderBy('fn_accounts.code')
            ->get()
            ->keyBy('account_id');

        $accountStatements = DB::table('fn_constraint_details')
            ->select([
                'fn_constraint_details.account_id',
                DB::raw('DATE_FORMAT(fn_constraint_details.date, "%Y-%m") as month'),
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
            ->whereNull('fn_constraint_details.deleted_at')
            ->whereNull('fn_constraints.deleted_at')
            ->whereIn('fn_constraint_details.account_id', $accounts->keys())
            ->when(($dataSearch['from_date'] ?? '') != '', function ($q) use ($dataSearch) {
                return $q->whereDate('fn_constraint_details.date', '>=', $this->normalizeMonthStart($dataSearch['from_date']));
            })
            ->when(($dataSearch['to_date'] ?? '') != '', function ($q) use ($dataSearch) {
                return $q->whereDate('fn_constraint_details.date', '<=', $this->normalizeMonthEnd($dataSearch['to_date']));
            })
            ->groupBy('fn_constraint_details.account_id', DB::raw('DATE_FORMAT(fn_constraint_details.date, "%Y-%m")'), 'fn_accounts.code', 'fn_accounts.name', 'fn_accounts.status')
            ->get()
            ->keyBy(fn ($item) => $item->account_id . '-' . $item->month);

        $months_keys = $this->monthsRange($dataSearch['from_date'] ?? '', $dataSearch['to_date'] ?? '');

        $finalData = $accounts->mapWithKeys(function ($account) use ($accountStatements, $months_keys) {
            $monthDetails = collect($months_keys)->mapWithKeys(function ($month) use ($account, $accountStatements) {
                $statement = $accountStatements->get($account->account_id . '-' . $month);

                return [
                    $month => [
                        'sum_debtor_value' => $statement->sum_debtor_value ?? 0,
                        'sum_creditor_value' => $statement->sum_creditor_value ?? 0,
                        'account_code' => $account->account_code,
                        'account_status' => $account->account_status,
                        'constraints_type' => $statement->constraints_type ?? null,
                        'constraint_id' => $statement->constraint_id ?? null,
                    ],
                ];
            });

            return [
                $account->account_id => [
                    'account_code' => $account->account_code,
                    'account_name' => $account->account_name,
                    'type' => $account->account_status,
                    'months' => $monthDetails,
                ],
            ];
        });

        return ['months_keys' => $months_keys,'data' => $finalData] ;
    }


    public function filterFirstAccountMonthly($dataSearch)
    {

        $accountStatment = $this->supplierReportAccountsQuery()
            ->select([
                DB::raw('MAX(fn_constraint_details.date) as date'),
                DB::raw('COALESCE(SUM(CASE WHEN fn_constraints.id IS NOT NULL THEN fn_constraint_details.debtor_value ELSE 0 END), 0) as sum_debtor_value'),
                DB::raw('COALESCE(SUM(CASE WHEN fn_constraints.id IS NOT NULL THEN fn_constraint_details.creditor_value ELSE 0 END), 0) as sum_creditor_value'),
                DB::raw('MAX(fn_constraint_details.statement) as statement'),
                'fn_accounts.code as account_code',
                'fn_accounts.name as account_name',
                'fn_accounts.status as account_status',
                DB::raw('MAX(fn_constraints.type) as constraints_type'),
                DB::raw('MAX(fn_constraint_details.constraint_id) as constraint_id'),
                'fn_accounts.id as account_id'
            ])
            ->leftJoin('fn_constraint_details', function ($join) use ($dataSearch) {
                $join->on('fn_constraint_details.account_id', '=', 'fn_accounts.id')
                    ->whereNull('fn_constraint_details.deleted_at');

                if (($dataSearch['start_date'] ?? '') != '') {
                    $join->whereDate('fn_constraint_details.date', '>=', $dataSearch['start_date']);
                }

                if (($dataSearch['from_date'] ?? '') != '') {
                    $join->whereDate('fn_constraint_details.date', '<=', $this->normalizeMonthStart($dataSearch['from_date']));
                }
            })
            ->leftJoin('fn_constraints', function ($join) {
                $join->on('fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
                    ->whereNull('fn_constraints.deleted_at');
            })
            ->groupBy('fn_accounts.id', 'fn_accounts.code', 'fn_accounts.name', 'fn_accounts.status')
            ->get()
            ->keyBy('account_id');

        return $accountStatment;
    }

    private function supplierReportAccountsQuery()
    {
        return DB::table('fn_accounts')
            ->leftJoin('fn_accounts as parent_accounts', 'parent_accounts.id', '=', 'fn_accounts.parent_id')
            ->where(function ($query) {
                $query->whereExists(function ($supplierQuery) {
                    $supplierQuery->select(DB::raw(1))
                        ->from('fn_suppliers')
                        ->whereColumn('fn_suppliers.account_id', 'fn_accounts.id');
                })
                    ->orWhere('parent_accounts.code', self::EXTERNAL_AGENTS_PARENT_CODE);
            });
    }

    private function normalizeMonthStart(string $month): string
    {
        return substr($month, 0, 7) . '-01';
    }

    private function normalizeMonthEnd(string $month): string
    {
        return date('Y-m-t', strtotime($this->normalizeMonthStart($month)));
    }

    private function monthsRange(string $fromMonth, string $toMonth): array
    {
        if ($fromMonth === '' || $toMonth === '') {
            return [];
        }

        $months = [];
        $current = strtotime($this->normalizeMonthStart($fromMonth));
        $end = strtotime($this->normalizeMonthStart($toMonth));

        while ($current <= $end) {
            $months[] = date('Y-m', $current);
            $current = strtotime('+1 month', $current);
        }

        return $months;
    }
}
