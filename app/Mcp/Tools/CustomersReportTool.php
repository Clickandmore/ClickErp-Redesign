<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\ReportsOnPeriods;
use App\Mcp\Tools\Concerns\ScopesCustomersToBranch;
use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\ingaz\User;
use App\Models\RentalContract;

/**
 * تقرير العملاء: الإجمالي، المسجلون في فترة حسب مصدر التسجيل ونوع العميل والمدينة، والعملاء النشطون
 * (لهم عقد في الفترة)، وأكثر العملاء عقوداً. عزل الفروع يدوي عبر ScopesCustomersToBranch.
 */
class CustomersReportTool extends McpTool
{
    use ReportsOnPeriods;
    use ScopesCustomersToBranch;

    public function name(): string
    {
        return 'customers_report';
    }

    public function description(): string
    {
        return 'تقرير العملاء: الإجمالي، المحظورون، العملاء الجدد في الفترة حسب مصدر التسجيل (الموقع/التطبيق) ونوع العميل (فرد/شركة) والمدينة، '
            . 'والعملاء الذين لهم عقد استقدام أو إيجار في الفترة، وأكثر 10 عملاء عقوداً. للبحث عن عميل بعينه استخدم search_customers. '
            . 'Customers report: totals, new customers by source/type/city, active customers and top customers.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => $this->periodProperties('تاريخ تسجيل العميل وتاريخ العقد'),
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'clients.index';
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $period = $this->resolvePeriod($arguments);
        $all = User::query();
        $this->applyCustomerBranchScope($all, $admin);

        $new = $this->applyPeriod(clone $all, 'users.created_at', $period);

        // العقود Branch-scoped، فتطابق فرع الموظف تلقائياً.
        $recruitmentClients = $this->applyPeriod(Contract::query()->whereIn('contracts.type', ['recruitment', 'maid']), 'COALESCE(contracts.contract_date, DATE(contracts.created_at))', $period)
            ->whereNotNull('contracts.user_id')->distinct()->pluck('contracts.user_id');
        $rentalClients = $this->applyPeriod(RentalContract::query(), 'COALESCE(rental_contracts.contract_date, DATE(rental_contracts.created_at))', $period)
            ->whereNotNull('rental_contracts.user_id')->distinct()->pluck('rental_contracts.user_id');
        $activeIds = $recruitmentClients->merge($rentalClients)->unique();
        $visibleActive = $activeIds->isEmpty() ? 0 : (clone $all)->whereIn('users.id', $activeIds)->count();

        return $this->periodSummary($period) + [
            'total_now' => (clone $all)->count(),
            'blocked_now' => (clone $all)->where('users.is_blocked', 1)->count(),
            'new_in_period' => [
                'total' => (clone $new)->count(),
                // web / android / ios / app
                'by_register_source' => $this->countBy($new, 'users.register_from'),
                // person / company
                'by_client_type' => $this->countBy($new, 'users.client_type'),
                'by_city' => $this->countBy($new, 'users.city', 15),
                'monthly' => $this->monthlyTrend($new, 'users.created_at'),
            ],
            'with_contract_in_period' => [
                'total' => $visibleActive,
                'with_recruitment_contract' => $recruitmentClients->count(),
                'with_rental_contract' => $rentalClients->count(),
            ],
            'top_customers' => $this->topCustomers($all),
            'notes' => 'total_now وblocked_now وtop_customers لقطة الآن (top_customers بعدد كل العقود). المدينة نص حر كما أُدخل.',
        ];
    }

    private function topCustomers($scopedUsers): array
    {
        $recruitment = Contract::query()->whereIn('contracts.type', ['recruitment', 'maid'])->whereNotNull('contracts.user_id')
            ->selectRaw('contracts.user_id as user_id, COUNT(*) as total')->groupBy('contracts.user_id')
            ->orderByDesc('total')->limit(50)->pluck('total', 'user_id');
        $rental = RentalContract::query()->whereNotNull('rental_contracts.user_id')
            ->selectRaw('rental_contracts.user_id as user_id, COUNT(*) as total')->groupBy('rental_contracts.user_id')
            ->orderByDesc('total')->limit(50)->pluck('total', 'user_id');

        $totals = collect($recruitment->keys())->merge($rental->keys())->unique()
            ->mapWithKeys(fn ($id) => [$id => (int) ($recruitment[$id] ?? 0) + (int) ($rental[$id] ?? 0)])
            ->sortDesc();

        $customers = (clone $scopedUsers)->whereIn('users.id', $totals->keys())->get(['users.id', 'users.name', 'users.phone'])->keyBy('id');

        return $totals->filter(fn ($total, $id) => $customers->has($id))->take(10)->map(fn ($total, $id) => [
            'id' => (int) $id,
            'name' => $customers[$id]->name,
            'phone' => $this->mask($customers[$id]->phone),
            'recruitment_contracts' => (int) ($recruitment[$id] ?? 0),
            'rental_contracts' => (int) ($rental[$id] ?? 0),
            'total_contracts' => $total,
        ])->values()->all();
    }
}
