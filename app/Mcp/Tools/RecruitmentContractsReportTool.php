<?php

namespace App\Mcp\Tools;

use App\Enums\ingaz\ContractStatusEnum;
use App\Mcp\Tools\Concerns\ReportsOnPeriods;
use App\Models\auth\Admin;
use App\Models\Contract;

/**
 * تقرير عقود الاستقدام لفترة. Contract عليه BranchScoped فيُعزل الفرع تلقائياً.
 * تاريخ العقد = contract_date، وإن كان فارغاً فتاريخ الإنشاء.
 */
class RecruitmentContractsReportTool extends McpTool
{
    use ReportsOnPeriods;

    private const DATE = 'COALESCE(contracts.contract_date, DATE(contracts.created_at))';

    public function name(): string
    {
        return 'recruitment_contracts_report';
    }

    public function description(): string
    {
        return 'تقرير عقود الاستقدام لفترة: الإجمالي، العدد حسب المرحلة، الجنسية، المسوّق، الفرع، الاتجاه الشهري، '
            . 'والعقود الواصلة والمكتملة في الفترة، وعدد العقود الجارية الآن. للبحث عن عقود بعينها استخدم list_recruitment_contracts. '
            . 'Recruitment contracts report for a period: totals by stage, nationality, marketer, branch, monthly trend, arrivals and completions.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => $this->periodProperties('تاريخ العقد') + [
                'status' => ['type' => 'string', 'enum' => array_map(fn ($case) => $case->value, ContractStatusEnum::cases()), 'description' => 'حصر التقرير في مرحلة واحدة'],
            ],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'RecruitmentContract.index';
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $period = $this->resolvePeriod($arguments);
        $base = Contract::query()->whereIn('contracts.type', ['recruitment', 'maid']);

        $query = $this->applyPeriod(clone $base, self::DATE, $period);
        if (!empty($arguments['status'])) {
            $query->where('contracts.status', (string) $arguments['status']);
        }

        $total = (clone $query)->count();
        $locale = $this->locale();

        $arrivals = (clone $base)->whereHas('info', fn ($info) => $this->applyPeriod($info, 'contract_infos.arrival_date', $period))->count();
        $completed = $this->applyPeriod((clone $base)->whereNotNull('contracts.completed_at'), 'contracts.completed_at', $period)->count();

        return $this->periodSummary($period) + [
            'total' => $total,
            'by_status' => $this->labeled(
                $this->countBy($query, 'contracts.status'),
                fn ($status) => ContractStatusEnum::tryFrom($status)?->lang()
            ),
            'by_nationality' => $this->countByJoin($query, 'contracts', fn ($q) => $q
                ->leftJoin('maids', 'maids.id', '=', 'contracts.maid_id')
                ->leftJoin('nationalities_translations as nt', function ($join) use ($locale) {
                    $join->on('nt.nationality_id', '=', \DB::raw('COALESCE(maids.nationality_id, contracts.required_maid_nationality_id)'))
                        ->where('nt.locale', $locale);
                }), 'nt.title'),
            'by_marketer' => $this->countByJoin($query, 'contracts', fn ($q) => $q
                ->leftJoin('admins as marketer', 'marketer.id', '=', 'contracts.marketer_id'), 'marketer.name'),
            'by_branch' => $this->countByJoin($query, 'contracts', fn ($q) => $q
                ->leftJoin('branches', 'branches.id', '=', 'contracts.branch_id'), 'branches.name_ar'),
            'monthly' => $this->monthlyTrend($query, self::DATE),
            'arrived_in_period' => $arrivals,
            'completed_in_period' => $completed,
            // لقطة الآن (لا تتبع الفترة): العقود في المراحل الجارية قبل الوصول.
            'current_pipeline_now' => (clone $base)->whereIn('contracts.status', Contract::invoiceEligibleStatuses())->count(),
            'notes' => 'by_status وby_nationality وby_marketer وby_branch وmonthly للعقود المؤرخة في الفترة. '
                . 'arrived_in_period حسب تاريخ الوصول، وcompleted_in_period حسب تاريخ الإكمال، لنفس الفترة. القيم المالية في invoices_summary.',
        ];
    }
}
