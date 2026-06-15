<?php

namespace Nami\Finance\Services;

use App\Models\RentalContractInfo;
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
    public function __construct(private BondService $bondService) {}

    public function index()
    {
        $now = now();
        $currentMonth = $now->month;
        $currentYear = $now->year;
        $lastMonth = $now->copy()->subMonth();

        $revenues_value_month = Bond::query()
            ->where('type', BondTypes::Qabd->value)
            ->whereYear('date', $currentYear)
            ->selectRaw('MONTH(date) as month, SUM(value) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $expenses_value_month =  Bond::query()
            ->where('type', BondTypes::Sarf->value)
            ->whereYear('date', $currentYear)
            ->selectRaw('MONTH(date) as month, SUM(value) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $revenues = (float) Bond::query()->where('type', BondTypes::Qabd->value)->sum('value');
        $expenses = (float) Bond::query()->where('type', BondTypes::Sarf->value)->sum('value');
        $monthRevenues = (float) Bond::query()
            ->where('type', BondTypes::Qabd->value)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->sum('value');
        $monthExpenses = (float) Bond::query()
            ->where('type', BondTypes::Sarf->value)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->sum('value');
        $lastMonthRevenues = (float) Bond::query()
            ->where('type', BondTypes::Qabd->value)
            ->whereMonth('date', $lastMonth->month)
            ->whereYear('date', $lastMonth->year)
            ->sum('value');
        $lastMonthExpenses = (float) Bond::query()
            ->where('type', BondTypes::Sarf->value)
            ->whereMonth('date', $lastMonth->month)
            ->whereYear('date', $lastMonth->year)
            ->sum('value');

        $recentBonds = Bond::query()
            ->with(['debitAccount', 'creditAccount'])
            ->latest('id')
            ->limit(6)
            ->get();

        $recentConstraints = Constraint::query()
            ->latest('id')
            ->limit(6)
            ->get();

        return [
            'revenues' => $revenues,
            'expenses' => $expenses,
            'net_profit' => $revenues - $expenses,
            'month_revenues' => $monthRevenues,
            'month_expenses' => $monthExpenses,
            'month_net' => $monthRevenues - $monthExpenses,
            'last_month_revenues' => $lastMonthRevenues,
            'last_month_expenses' => $lastMonthExpenses,
            'last_month_net' => $lastMonthRevenues - $lastMonthExpenses,
            'revenues_value_month' => $this->fillMissingMonths($revenues_value_month),
            'expenses_value_month' => $this->fillMissingMonths($expenses_value_month),
            'finance_counts' => [
                'accounts' => Account::count(),
                'leaf_accounts' => Account::doesntHave('children')->count(),
                'clients' => Client::count(),
                'suppliers' => Supplier::count(),
                'cost_centers' => CostCenter::count(),
                'constraints' => Constraint::count(),
                'pending_constraints' => Constraint::query()->pendingApproval()->count(),
                'approved_constraints' => Constraint::query()->approved()->count(),
                'qabd_bonds' => Bond::query()->where('type', BondTypes::Qabd->value)->count(),
                'sarf_bonds' => Bond::query()->where('type', BondTypes::Sarf->value)->count(),
            ],
            'rental_contracts_total_value' => (float) RentalContractInfo::sum('contract_value'),
            'recent_bonds' => $recentBonds,
            'recent_constraints' => $recentConstraints,
        ];
    }

    private function fillMissingMonths(array $data)
    {
        $filled = [];
        for ($month = 1; $month <= 12; $month++) {
            $filled[$month] = $data[$month] ?? 0;
        }
        return array_values($filled); // Return as array of values indexed from 0
    }
}
