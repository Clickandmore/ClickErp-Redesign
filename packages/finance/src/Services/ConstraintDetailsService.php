<?php

namespace Nami\Finance\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\AccountTypesEnum;
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

    public function balancesReport(string $scope = 'all', array $filters = [])
    {
        $query = DB::table('fn_constraint_details as detail')
            ->join('fn_constraints as constraint', function ($join) {
                $join->on('constraint.id', '=', 'detail.constraint_id')
                    ->whereNull('constraint.deleted_at');
            })
            ->join('fn_accounts as account', 'account.id', '=', 'detail.account_id')
            ->leftJoin('fn_accounts as parent', 'parent.id', '=', 'account.parent_id')
            ->whereNull('detail.deleted_at')
            ->when(!empty($filters['from_date']), function ($query) use ($filters) {
                $query->where('detail.date', '>=', $filters['from_date']);
            })
            ->when(!empty($filters['to_date']), function ($query) use ($filters) {
                $query->where('detail.date', '<=', $filters['to_date']);
            })
            ->when(!empty($filters['keyword']), function ($query) use ($filters) {
                $keyword = trim((string) $filters['keyword']);
                $query->where(function ($query) use ($keyword) {
                    $query->where('account.name', 'like', "%{$keyword}%")
                        ->orWhere('account.code', 'like', "%{$keyword}%")
                        ->orWhere('parent.name', 'like', "%{$keyword}%")
                        ->orWhere('parent.code', 'like', "%{$keyword}%");
                });
            });

        if ($scope === 'clients') {
            $query->where('account.type', AccountTypesEnum::Client->value);
        } elseif ($scope === 'external_agents') {
            $query->where(function ($query) {
                $query->whereExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('recruitment_offices as office')
                        ->whereColumn('office.account_id', 'account.id');
                })->orWhere('parent.code', 221);
            });
        } elseif ($scope === 'treasury_banks') {
            $query->whereIn('account.type', [
                AccountTypesEnum::Bank->value,
                AccountTypesEnum::Box->value,
            ]);
        }

        return $query
            ->select([
                'account.id',
                'account.code',
                'account.name',
                'account.status',
                'parent.code as parent_code',
                'parent.name as parent_name',
            ])
            ->selectRaw('SUM(detail.debtor_value) AS debtor_total')
            ->selectRaw('SUM(detail.creditor_value) AS creditor_total')
            ->selectRaw("CASE
                WHEN SUM(detail.debtor_value) >= SUM(detail.creditor_value) THEN 'debtor'
                ELSE 'creditor'
            END AS actual_status")
            ->selectRaw('ABS(SUM(detail.debtor_value) - SUM(detail.creditor_value)) AS balance')
            ->groupBy(
                'account.id',
                'account.code',
                'account.name',
                'account.status',
                'parent.code',
                'parent.name'
            )
            ->havingRaw('ABS(SUM(detail.debtor_value) - SUM(detail.creditor_value)) > ?', [0.005])
            ->orderBy('account.code')
            ->orderBy('account.id')
            ->get();
    }

    public function normalizeBalanceMovementFilters(array $filters): array
    {
        $firstMovementDate = DB::table('fn_constraint_details as detail')
            ->join('fn_constraints as constraint', function ($join) {
                $join->on('constraint.id', '=', 'detail.constraint_id')
                    ->whereNull('constraint.deleted_at');
            })
            ->whereNull('detail.deleted_at')
            ->min('detail.date');

        try {
            $fromDate = !empty($filters['from_date'])
                ? Carbon::parse($filters['from_date'])->startOfDay()
                : ($firstMovementDate ? Carbon::parse($firstMovementDate)->startOfDay() : now()->startOfYear());
        } catch (\Throwable) {
            $fromDate = $firstMovementDate ? Carbon::parse($firstMovementDate)->startOfDay() : now()->startOfYear();
        }

        try {
            $toDate = !empty($filters['to_date'])
                ? Carbon::parse($filters['to_date'])->startOfDay()
                : now()->startOfDay();
        } catch (\Throwable) {
            $toDate = now()->startOfDay();
        }

        if ($fromDate->greaterThan($toDate)) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        return [
            'from_date' => $fromDate->format('Y-m-d'),
            'to_date' => $toDate->format('Y-m-d'),
            'keyword' => trim((string) ($filters['keyword'] ?? '')),
        ];
    }

    public function balancesMovementReport(string $scope, array $filters)
    {
        $fromDate = $filters['from_date'];
        $toDate = $filters['to_date'];

        $accountsQuery = DB::table('fn_accounts as account')
            ->leftJoin('fn_accounts as parent', 'parent.id', '=', 'account.parent_id')
            ->select([
                'account.id',
                'account.code',
                'account.name',
                'account.level',
                'account.parent_id',
                'parent.code as parent_code',
                'parent.name as parent_name',
            ]);

        if ($scope === 'clients') {
            $accountsQuery->where('account.type', AccountTypesEnum::Client->value);
        } elseif ($scope === 'external_agents') {
            $accountsQuery->where(function ($query) {
                $query->whereExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('recruitment_offices as office')
                        ->whereColumn('office.account_id', 'account.id');
                })->orWhere('parent.code', 221);
            });
        } elseif ($scope === 'treasury_banks') {
            $accountsQuery->whereIn('account.type', [
                AccountTypesEnum::Bank->value,
                AccountTypesEnum::Box->value,
            ]);
        }

        $accounts = $accountsQuery
            ->orderBy('account.code')
            ->orderBy('account.id')
            ->get();

        if ($accounts->isEmpty()) {
            return collect();
        }

        $movementQuery = DB::table('fn_constraint_details as detail')
            ->join('fn_constraints as constraint', function ($join) {
                $join->on('constraint.id', '=', 'detail.constraint_id')
                    ->whereNull('constraint.deleted_at');
            })
            ->whereNull('detail.deleted_at')
            ->when($scope !== 'all', function ($query) use ($accounts) {
                $query->whereIn('detail.account_id', $accounts->pluck('id'));
            })
            ->groupBy('detail.account_id')
            ->select('detail.account_id')
            ->selectRaw('SUM(CASE WHEN detail.date < ? THEN detail.debtor_value ELSE 0 END) AS opening_debtor', [$fromDate])
            ->selectRaw('SUM(CASE WHEN detail.date < ? THEN detail.creditor_value ELSE 0 END) AS opening_creditor', [$fromDate])
            ->selectRaw('SUM(CASE WHEN detail.date >= ? AND detail.date <= ? THEN detail.debtor_value ELSE 0 END) AS period_debtor', [$fromDate, $toDate])
            ->selectRaw('SUM(CASE WHEN detail.date >= ? AND detail.date <= ? THEN detail.creditor_value ELSE 0 END) AS period_creditor', [$fromDate, $toDate])
            ->selectRaw('SUM(CASE WHEN detail.date > ? THEN detail.debtor_value ELSE 0 END) AS after_debtor', [$toDate])
            ->selectRaw('SUM(CASE WHEN detail.date > ? THEN detail.creditor_value ELSE 0 END) AS after_creditor', [$toDate]);

        $movementByAccount = $movementQuery->get()->keyBy('account_id');
        $sumFields = [
            'opening_debtor',
            'opening_creditor',
            'period_debtor',
            'period_creditor',
            'after_debtor',
            'after_creditor',
        ];
        $totals = [];

        foreach ($accounts as $account) {
            $movement = $movementByAccount->get($account->id);
            $totals[$account->id] = [];

            foreach ($sumFields as $field) {
                $totals[$account->id][$field] = (float) ($movement->{$field} ?? 0);
            }
        }

        // The general report includes parent accounts. Roll each posting
        // account upward once so every parent represents its complete branch.
        if ($scope === 'all') {
            foreach ($accounts->sortByDesc('level') as $account) {
                $parentId = (int) ($account->parent_id ?? 0);
                if (!$parentId || !isset($totals[$parentId])) {
                    continue;
                }

                foreach ($sumFields as $field) {
                    $totals[$parentId][$field] += $totals[$account->id][$field];
                }
            }
        }

        $parentIds = $accounts->pluck('parent_id')->filter()->map(fn ($id) => (int) $id)->flip();
        $keyword = mb_strtolower($filters['keyword'] ?? '');

        return $accounts
            ->map(function ($account) use ($totals, $parentIds) {
                $accountTotals = $totals[$account->id];
                $activityTotal = array_sum($accountTotals);

                if ($activityTotal <= 0.005) {
                    return null;
                }

                foreach (['opening', 'period', 'after'] as $period) {
                    $this->setMovementPeriodValues(
                        $account,
                        $period,
                        $accountTotals["{$period}_debtor"],
                        $accountTotals["{$period}_creditor"]
                    );
                }

                $closingDebtor = $accountTotals['opening_debtor']
                    + $accountTotals['period_debtor']
                    + $accountTotals['after_debtor'];
                $closingCreditor = $accountTotals['opening_creditor']
                    + $accountTotals['period_creditor']
                    + $accountTotals['after_creditor'];
                $this->setMovementPeriodValues($account, 'closing', $closingDebtor, $closingCreditor);
                $account->has_children = $parentIds->has((int) $account->id);

                return $account;
            })
            ->filter()
            ->when($keyword !== '', function ($accounts) use ($keyword) {
                return $accounts->filter(function ($account) use ($keyword) {
                    return str_contains(mb_strtolower((string) $account->name), $keyword)
                        || str_contains(mb_strtolower((string) $account->code), $keyword)
                        || str_contains(mb_strtolower((string) $account->parent_name), $keyword)
                        || str_contains(mb_strtolower((string) $account->parent_code), $keyword);
                });
            })
            ->sortBy(fn ($account) => [(int) $account->code, (int) $account->id])
            ->values();
    }

    public function summarizeBalanceMovementReport(Collection $accounts, string $scope): array
    {
        $summaryRows = $scope === 'all'
            ? $accounts->filter(fn ($account) => empty($account->parent_id))
            : $accounts;

        if ($summaryRows->isEmpty()) {
            $summaryRows = $accounts;
        }

        $periodDebtor = (float) $summaryRows->sum('period_debtor');
        $periodCreditor = (float) $summaryRows->sum('period_creditor');

        return [
            'accounts_count' => $accounts->count(),
            'period_debtor' => $periodDebtor,
            'period_creditor' => $periodCreditor,
            'period_movement' => $periodDebtor + $periodCreditor,
        ];
    }

    private function setMovementPeriodValues(object $account, string $period, float $debtor, float $creditor): void
    {
        $difference = round($debtor - $creditor, 2);
        $account->{$period . '_debtor'} = $debtor;
        $account->{$period . '_creditor'} = $creditor;
        $account->{$period . '_balance'} = abs($difference);
        $account->{$period . '_status'} = abs($difference) <= 0.005
            ? null
            : ($difference > 0 ? 'debtor' : 'creditor');
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
            ->with('account')
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
            ->with('account')
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
            ->with('account')
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
        $constraintsQuery = $this->model
            ->newQuery()
            ->select('id')
            ->when($request->filled('from_date'), function ($q) use ($request) {
                return $q->whereDate("date", ">=", $request->from_date);
            })
            ->when($request->filled('to_date'), function ($q) use ($request) {
                return $q->whereDate("date", "<=", $request->to_date);
            });

        return $this->accountService->getIncomeAccountsChildrens($constraintsQuery);
    }

    public function filterProfitLoss($request)
    {
        $constraintsQuery = $this->model
            ->newQuery()
            ->select('id')
            ->when($request->filled('from_date'), function ($q) use ($request) {
                return $q->whereDate("date", ">=", $request->from_date);
            })
            ->when($request->filled('to_date'), function ($q) use ($request) {
                return $q->whereDate("date", "<=", $request->to_date);
            });

        $requestedLevel = $request->input('level', 'all');
        $level = $requestedLevel === 'all' ? null : (int) $requestedLevel;

        return $this->accountService->getIncomeAccountsChildrens($constraintsQuery, $level);
    }

    public function getIncomeReportLevels(): array
    {
        return $this->accountService->getIncomeReportLevels();
    }
}
