<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\ReportsOnPeriods;
use App\Models\auth\Admin;
use App\Models\DeportationProcedure;

/** إجراءات الترحيل. DeportationProcedure عليه BranchScoped. */
class DeportationReportTool extends McpTool
{
    use ReportsOnPeriods;

    private const DATE = 'COALESCE(deportation_procedures.started_at, deportation_procedures.created_at)';

    private const STEPS = [
        'salary_received' => 'استلام الراتب',
        'has_passport' => 'الجواز',
        'has_deportation_letter' => 'خطاب الترحيل',
        'has_settlement_session' => 'جلسة التسوية',
        'has_final_exit' => 'الخروج النهائي',
    ];

    public function name(): string
    {
        return 'deportation_report';
    }

    public function description(): string
    {
        return 'تقرير إجراءات الترحيل لفترة: العدد حسب الحالة، والمكتملة في الفترة، وما زال مفتوحاً الآن وأي خطوة تنقصه. '
            . 'Deportation procedures report: counts by status, and open procedures by missing step.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => $this->periodProperties('تاريخ بدء الإجراء')];
    }

    protected function gateRoute(): ?string
    {
        return 'DeportationTravel.index';
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $period = $this->resolvePeriod($arguments);
        $query = $this->applyPeriod(DeportationProcedure::query(), self::DATE, $period);
        $open = DeportationProcedure::query()->whereIn('deportation_procedures.status', ['pending', 'in_progress']);
        $total = (clone $query)->count();

        $missing = [];
        foreach (self::STEPS as $column => $label) {
            $missing[$label] = (clone $open)->where(fn ($q) => $q->where($column, false)->orWhereNull($column))->count();
        }

        return $this->periodSummary($period) + [
            'total' => $total,
            'comparison' => $this->comparison($arguments, $period, $total, fn (array $other) => $this->applyPeriod(DeportationProcedure::query(), self::DATE, $other)->count()),
            'by_status' => $this->countBy($query, 'deportation_procedures.status'),
            'completed_in_period' => $this->applyPeriod(DeportationProcedure::query()->whereNotNull('deportation_procedures.completed_at'), 'deportation_procedures.completed_at', $period)->count(),
            'open_now' => (clone $open)->count(),
            'open_missing_step' => $missing,
            'notes' => 'open_now وopen_missing_step لقطة الآن ولا يتبعان الفترة.',
        ];
    }
}
