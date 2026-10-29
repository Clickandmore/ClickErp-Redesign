<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\ReportsOnPeriods;
use App\Models\auth\Admin;
use App\Models\ingaz\Order;
use App\Support\RecruitmentOrderStatusLabels;
use Illuminate\Support\Facades\Schema;

/**
 * تقرير طلبات الاستقدام والإيجار لفترة حسب تاريخ الإنشاء. Order عليه BranchScoped وSoftDeletes.
 * التصنيف الفعلي (جارٍ/تعاقد/لم يكتمل) بنفس منطق OrderController::applyEffectiveStatusFilter.
 */
class OrdersReportTool extends McpTool
{
    use ReportsOnPeriods;

    private const OPEN = ['new', 'under_process'];

    private const CLOSED_WITHOUT_CONTRACT = ['refused', 'return', 'canceled_by_system'];

    public function name(): string
    {
        return 'orders_report';
    }

    public function description(): string
    {
        return 'تقرير الطلبات لفترة: الإجمالي، العدد حسب الحالة، الجارية والمتعاقد عليها وغير المكتملة، نسبة التحويل إلى عقد، '
            . 'المصدر (من الموقع/التطبيق أو أنشأها موظف)، الموظف المنشئ، الجنسية، الفرع، والاتجاه الشهري. '
            . 'type: recruitment (افتراضي) أو rental أو all. Orders report: status, conversion to contract, source, nationality, trend.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => $this->periodProperties('تاريخ إنشاء الطلب') + [
                'type' => ['type' => 'string', 'enum' => ['recruitment', 'rental', 'all'], 'default' => 'recruitment', 'description' => 'نوع الطلبات'],
            ],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'orders.index';
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $period = $this->resolvePeriod($arguments);
        $type = (string) ($arguments['type'] ?? 'recruitment');

        $query = Order::query();
        match ($type) {
            'rental' => $query->rentalRequests(),
            'all' => $query->whereIn('orders.job_type_id', [1, 2]),
            default => $query->recruitmentRequests(),
        };
        $this->applyPeriod($query, 'orders.created_at', $period);

        $total = (clone $query)->count();
        $converted = (clone $query)->where(fn ($q) => $q->whereHas('contracts')->orWhereHas('rentalContracts'))->count();
        $noContract = fn ($q) => $q->whereDoesntHave('contracts')->whereDoesntHave('rentalContracts');
        $locale = $this->locale();

        $result = $this->periodSummary($period) + [
            'type' => $type,
            'total' => $total,
            'by_status' => $this->labeled($this->countBy($query, 'orders.status'), fn ($status) => RecruitmentOrderStatusLabels::label($status)),
            'effective' => [
                'open_without_contract' => $noContract((clone $query)->whereIn('orders.status', self::OPEN))->count(),
                'converted_to_contract' => $converted,
                'closed_without_contract' => $noContract((clone $query)->whereIn('orders.status', self::CLOSED_WITHOUT_CONTRACT))->count(),
            ],
            'conversion_rate_percent' => $this->ratio($converted, $total),
            'by_nationality' => $this->countByJoin($query, 'orders', fn ($q) => $q
                ->leftJoin('maids', 'maids.id', '=', 'orders.maid_id')
                ->leftJoin('nationalities_translations as nt', fn ($join) => $join->on('nt.nationality_id', '=', 'maids.nationality_id')->where('nt.locale', $locale)), 'nt.title'),
            'by_branch' => $this->countByJoin($query, 'orders', fn ($q) => $q
                ->leftJoin('branches', 'branches.id', '=', 'orders.branch_id'), 'branches.name_ar'),
            'monthly' => $this->monthlyTrend($query, 'orders.created_at'),
        ];

        // created_by_admin_id: فارغ = من الموقع أو التطبيق (لا يمكن التمييز بينهما)، غير فارغ = موظف.
        if (Schema::hasColumn('orders', 'created_by_admin_id')) {
            $byEmployee = (clone $query)->whereNotNull('orders.created_by_admin_id')->count();
            $result['by_source'] = ['site_or_app' => $total - $byEmployee, 'employee' => $byEmployee];
            $result['by_creating_employee'] = $this->countByJoin(
                (clone $query)->whereNotNull('orders.created_by_admin_id'),
                'orders',
                fn ($q) => $q->leftJoin('admins as creator', 'creator.id', '=', 'orders.created_by_admin_id'),
                'creator.name'
            );
        }

        $result['notes'] = 'converted_to_contract = طلب له عقد استقدام أو إيجار. open_without_contract = جديد أو قيد الإجراء بلا عقد. '
            . 'closed_without_contract = مرفوض أو مرتجع أو ألغاه النظام بلا عقد.';

        return $result;
    }
}
