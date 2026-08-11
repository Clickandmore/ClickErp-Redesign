<?php

namespace Nami\Finance\Services;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\BondTypes;
use Nami\Finance\MainService;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Bond;
use Nami\Finance\Models\Client;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\CostCenter;
use Nami\Finance\Models\Supplier;

class HomeService extends MainService
{
    public function index(): array
    {
        $today = now()->startOfDay();
        $monthStart = $today->copy()->startOfMonth();
        $yearStart = $today->copy()->startOfYear();
        $lastMonthStart = $monthStart->copy()->subMonth();
        $lastMonthEnd = $lastMonthStart->copy()->addDays($today->day - 1);
        if ($lastMonthEnd->month !== $lastMonthStart->month) {
            $lastMonthEnd = $lastMonthStart->copy()->endOfMonth();
        }

        $accountMap = Account::query()
            ->select(['id', 'parent_id', 'code', 'name', 'type', 'status'])
            ->get()
            ->keyBy('id');

        $incomeAccountIds = $this->accountBranchIds($accountMap, [4], [
            AccountTypesEnum::GeneralIncome->value,
        ]);
        $expenseAccountIds = $this->accountBranchIds($accountMap, [5], [
            AccountTypesEnum::GeneralOutcome->value,
        ]);
        $liquidityAccountIds = $this->liquidityAccountIds($accountMap);
        $clientAccountIds = $this->clientAccountIds($accountMap);
        $supplierAccountIds = $this->supplierAccountIds($accountMap);

        $monthIncome = $this->accountMovement($incomeAccountIds, $monthStart, $today, 'creditor');
        $monthExpenses = $this->accountMovement($expenseAccountIds, $monthStart, $today, 'debtor');
        $lastMonthIncome = $this->accountMovement($incomeAccountIds, $lastMonthStart, $lastMonthEnd, 'creditor');
        $lastMonthExpenses = $this->accountMovement($expenseAccountIds, $lastMonthStart, $lastMonthEnd, 'debtor');
        $todayIncome = $this->accountMovement($incomeAccountIds, $today, $today, 'creditor');
        $todayExpenses = $this->accountMovement($expenseAccountIds, $today, $today, 'debtor');

        $liquidityBalances = $this->accountBalances($liquidityAccountIds, $today)
            ->sortByDesc('signed_balance')
            ->values();
        $clientBalances = $this->accountBalances($clientAccountIds, $today);
        $supplierBalances = $this->accountBalances($supplierAccountIds, $today);

        $monthlyMovement = $this->monthlyIncomeExpenseMovement(
            $incomeAccountIds,
            $expenseAccountIds,
            $yearStart,
            $today
        );
        $dailyMovement = $this->dailyIncomeExpenseMovement(
            $incomeAccountIds,
            $expenseAccountIds,
            $today->copy()->subDays(13),
            $today
        );

        $monthCollections = $this->bondMovement(BondTypes::Qabd->value, $monthStart, $today);
        $monthPayments = $this->bondMovement(BondTypes::Sarf->value, $monthStart, $today);
        $todayCollections = $this->bondMovement(BondTypes::Qabd->value, $today, $today);
        $todayPayments = $this->bondMovement(BondTypes::Sarf->value, $today, $today);

        $recentConstraints = Constraint::query()
            ->approved()
            ->with(['accounts:id,name'])
            ->latest('date')
            ->latest('id')
            ->limit(7)
            ->get();

        $pendingConstraints = Constraint::query()->pendingApproval()->count();

        return [
            'as_of_date' => $today->toDateString(),
            'month_label' => $today->translatedFormat('F Y'),
            'liquidity_total' => (float) $liquidityBalances->sum('signed_balance'),
            'liquidity_balances' => $liquidityBalances,
            'bank_accounts_count' => $liquidityBalances->count(),
            'clients_receivable' => (float) $clientBalances->sum(fn ($row) => max($row->signed_balance, 0)),
            'clients_credit' => (float) $clientBalances->sum(fn ($row) => abs(min($row->signed_balance, 0))),
            'clients_with_balance' => $clientBalances->filter(fn ($row) => abs($row->signed_balance) > .005)->count(),
            'top_clients' => $clientBalances
                ->filter(fn ($row) => $row->signed_balance > .005)
                ->sortByDesc('signed_balance')
                ->take(6)
                ->values(),
            'suppliers_payable' => (float) $supplierBalances->sum(fn ($row) => max(-$row->signed_balance, 0)),
            'supplier_advances' => (float) $supplierBalances->sum(fn ($row) => max($row->signed_balance, 0)),
            'suppliers_with_balance' => $supplierBalances->filter(fn ($row) => abs($row->signed_balance) > .005)->count(),
            'top_suppliers' => $supplierBalances
                ->filter(fn ($row) => $row->signed_balance < -.005)
                ->sortBy('signed_balance')
                ->take(6)
                ->values(),
            'month_income' => $monthIncome,
            'month_expenses' => $monthExpenses,
            'month_net' => $monthIncome - $monthExpenses,
            'today_income' => $todayIncome,
            'today_expenses' => $todayExpenses,
            'today_net' => $todayIncome - $todayExpenses,
            'last_month_income' => $lastMonthIncome,
            'last_month_expenses' => $lastMonthExpenses,
            'income_change' => $this->percentageChange($monthIncome, $lastMonthIncome),
            'expense_change' => $this->percentageChange($monthExpenses, $lastMonthExpenses),
            'month_collections' => $monthCollections,
            'month_payments' => $monthPayments,
            'today_collections' => $todayCollections,
            'today_payments' => $todayPayments,
            'monthly_income' => $monthlyMovement['income'],
            'monthly_expenses' => $monthlyMovement['expenses'],
            'daily_labels' => $dailyMovement['labels'],
            'daily_income' => $dailyMovement['income'],
            'daily_expenses' => $dailyMovement['expenses'],
            'finance_counts' => [
                'accounts' => $accountMap->count(),
                'clients' => Client::query()->count(),
                'suppliers' => Supplier::query()->count(),
                'cost_centers' => CostCenter::query()->count(),
                'constraints' => Constraint::query()->count(),
                'month_constraints' => Constraint::query()->approved()->whereBetween('date', [$monthStart, $today])->count(),
                'today_constraints' => Constraint::query()->approved()->whereDate('date', $today)->count(),
                'pending_constraints' => $pendingConstraints,
            ],
            'recent_constraints' => $recentConstraints,
        ];
    }

    private function ledgerQuery(): Builder
    {
        $query = DB::table('fn_constraint_details as detail')
            ->join('fn_constraints as constraint', function ($join) {
                $join->on('constraint.id', '=', 'detail.constraint_id')
                    ->whereNull('constraint.deleted_at');
            })
            ->whereNull('detail.deleted_at');

        if (Constraint::hasApprovalColumns()) {
            $query->where('constraint.approval_status', Constraint::APPROVAL_APPROVED);
        }

        return $query;
    }

    private function accountMovement(Collection $accountIds, Carbon $from, Carbon $to, string $normalSide): float
    {
        if ($accountIds->isEmpty()) {
            return 0.0;
        }

        $row = $this->ledgerQuery()
            ->whereIn('detail.account_id', $accountIds)
            ->whereDate('detail.date', '>=', $from)
            ->whereDate('detail.date', '<=', $to)
            ->selectRaw('COALESCE(SUM(detail.debtor_value), 0) as debtor')
            ->selectRaw('COALESCE(SUM(detail.creditor_value), 0) as creditor')
            ->first();

        return $normalSide === 'creditor'
            ? (float) $row->creditor - (float) $row->debtor
            : (float) $row->debtor - (float) $row->creditor;
    }

    private function accountBalances(Collection $accountIds, Carbon $to): Collection
    {
        if ($accountIds->isEmpty()) {
            return collect();
        }

        return $this->ledgerQuery()
            ->join('fn_accounts as account', 'account.id', '=', 'detail.account_id')
            ->whereIn('detail.account_id', $accountIds)
            ->whereDate('detail.date', '<=', $to)
            ->select(['account.id', 'account.code', 'account.name'])
            ->selectRaw('COALESCE(SUM(detail.debtor_value), 0) as debtor_total')
            ->selectRaw('COALESCE(SUM(detail.creditor_value), 0) as creditor_total')
            ->groupBy('account.id', 'account.code', 'account.name')
            ->get()
            ->map(function ($row) {
                $row->signed_balance = (float) $row->debtor_total - (float) $row->creditor_total;
                $row->balance = abs($row->signed_balance);
                return $row;
            });
    }

    private function monthlyIncomeExpenseMovement(
        Collection $incomeIds,
        Collection $expenseIds,
        Carbon $from,
        Carbon $to
    ): array {
        $income = $this->groupedMovement($incomeIds, $from, $to, '%m', 'creditor');
        $expenses = $this->groupedMovement($expenseIds, $from, $to, '%m', 'debtor');

        return [
            'income' => $this->fillNumericPeriod($income, 1, 12),
            'expenses' => $this->fillNumericPeriod($expenses, 1, 12),
        ];
    }

    private function dailyIncomeExpenseMovement(
        Collection $incomeIds,
        Collection $expenseIds,
        Carbon $from,
        Carbon $to
    ): array {
        $income = $this->groupedMovement($incomeIds, $from, $to, '%Y-%m-%d', 'creditor');
        $expenses = $this->groupedMovement($expenseIds, $from, $to, '%Y-%m-%d', 'debtor');
        $labels = [];
        $incomeValues = [];
        $expenseValues = [];

        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            $key = $date->toDateString();
            $labels[] = $date->format('d/m');
            $incomeValues[] = (float) ($income[$key] ?? 0);
            $expenseValues[] = (float) ($expenses[$key] ?? 0);
        }

        return ['labels' => $labels, 'income' => $incomeValues, 'expenses' => $expenseValues];
    }

    private function groupedMovement(
        Collection $accountIds,
        Carbon $from,
        Carbon $to,
        string $dateFormat,
        string $normalSide
    ): Collection {
        if ($accountIds->isEmpty()) {
            return collect();
        }

        $expression = $normalSide === 'creditor'
            ? 'SUM(detail.creditor_value) - SUM(detail.debtor_value)'
            : 'SUM(detail.debtor_value) - SUM(detail.creditor_value)';

        return $this->ledgerQuery()
            ->whereIn('detail.account_id', $accountIds)
            ->whereDate('detail.date', '>=', $from)
            ->whereDate('detail.date', '<=', $to)
            ->selectRaw("DATE_FORMAT(detail.date, ?) as period, {$expression} as total", [$dateFormat])
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('total', 'period');
    }

    private function fillNumericPeriod(Collection $values, int $from, int $to): array
    {
        $filled = [];
        for ($period = $from; $period <= $to; $period++) {
            $filled[] = (float) ($values[str_pad((string) $period, 2, '0', STR_PAD_LEFT)] ?? 0);
        }
        return $filled;
    }

    private function accountBranchIds(Collection $accounts, array $rootCodes, array $rootTypes = []): Collection
    {
        $rootIds = $accounts
            ->filter(fn ($account) => in_array((string) $account->code, array_map('strval', $rootCodes), true)
                || in_array($this->accountTypeValue($account->type), $rootTypes, true))
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        if ($rootIds->isEmpty()) {
            return collect();
        }

        $included = $rootIds->flip();
        $changed = true;
        while ($changed) {
            $changed = false;
            foreach ($accounts as $account) {
                if ($account->parent_id && $included->has((int) $account->parent_id) && !$included->has((int) $account->id)) {
                    $included->put((int) $account->id, true);
                    $changed = true;
                }
            }
        }

        return $included->keys()->values();
    }

    private function liquidityAccountIds(Collection $accounts): Collection
    {
        $directIds = $accounts->filter(fn ($account) => in_array($this->accountTypeValue($account->type), [
            AccountTypesEnum::Bank->value,
            AccountTypesEnum::Box->value,
            AccountTypesEnum::ElectronicWallet->value,
            AccountTypesEnum::BankName->value,
        ], true))->pluck('id');

        $containerIds = $this->accountBranchIds($accounts, [], [
            AccountTypesEnum::Banks->value,
            AccountTypesEnum::Boxs->value,
            AccountTypesEnum::ElectronicWallets->value,
        ]);

        $mappedIds = DB::table('bank_accounts')->whereNotNull('account_id')->pluck('account_id')
            ->merge(DB::table('fn_boxes')->whereNotNull('account_id')->pluck('account_id'));

        return $directIds->merge($containerIds)->merge($mappedIds)->map(fn ($id) => (int) $id)->unique()->values();
    }

    private function clientAccountIds(Collection $accounts): Collection
    {
        return $accounts->filter(fn ($account) => $this->accountTypeValue($account->type) === AccountTypesEnum::Client->value)->pluck('id')
            ->merge(Client::query()->whereNotNull('account_id')->pluck('account_id'))
            ->map(fn ($id) => (int) $id)->unique()->values();
    }

    private function supplierAccountIds(Collection $accounts): Collection
    {
        $ids = $accounts->filter(fn ($account) => $this->accountTypeValue($account->type) === AccountTypesEnum::Supplier->value)->pluck('id')
            ->merge(Supplier::query()->whereNotNull('account_id')->pluck('account_id'));

        if (DB::getSchemaBuilder()->hasTable('recruitment_offices')) {
            $ids = $ids->merge(DB::table('recruitment_offices')->whereNotNull('account_id')->pluck('account_id'));
        }

        return $ids
            ->merge($this->accountBranchIds($accounts, [221]))
            ->map(fn ($id) => (int) $id)->unique()->values();
    }

    private function bondMovement(string|int $type, Carbon $from, Carbon $to): float
    {
        return (float) Bond::query()
            ->where('type', $type)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->sum('value');
    }

    private function percentageChange(float $current, float $previous): ?float
    {
        if (abs($previous) < .005) {
            return null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }

    private function accountTypeValue(mixed $type): int
    {
        return $type instanceof \BackedEnum ? (int) $type->value : (int) $type;
    }
}
