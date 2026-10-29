<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\ReportsOnPeriods;
use App\Models\auth\Admin;
use App\Models\RentalContract;

/**
 * تقرير عقود الإيجار (تأجير العمالة) لفترة. RentalContract وRentalContractInfo عليهما BranchScoped.
 * المبالغ من rental_contract_infos.
 */
class RentalContractsReportTool extends McpTool
{
    use ReportsOnPeriods;

    private const DATE = 'COALESCE(rental_contracts.contract_date, DATE(rental_contracts.created_at))';

    private const RUNNING = ['maid_delivered', 'active'];

    public function name(): string
    {
        return 'rental_contracts_report';
    }

    public function description(): string
    {
        return 'تقرير عقود الإيجار لفترة: الإجمالي، العدد حسب الحالة، قيمة العقود والمدفوع والمتبقي، الجنسية، المسوّق، الفرع، '
            . 'الاتجاه الشهري، العقود المنتهية في الفترة حسب نوع الإغلاق، والعقود السارية الآن وما ينتهي منها خلال 30 يوماً. '
            . 'للبحث عن عقود بعينها استخدم list_rental_contracts. Rental contracts report: totals, money, breakdowns and expiring contracts.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => $this->periodProperties('تاريخ العقد') + [
                'status' => [
                    'type' => 'string',
                    'enum' => ['new', 'waiting_contract_send', 'contract_sent', 'contract_signed', 'maid_delivered', 'active', 'completed', 'cancelled', 'suspended', 'frozen'],
                    'description' => 'حصر التقرير في حالة واحدة',
                ],
            ],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'RentContract.index';
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $period = $this->resolvePeriod($arguments);
        $base = RentalContract::query();

        $query = $this->applyPeriod(clone $base, self::DATE, $period);
        if (!empty($arguments['status'])) {
            $query->where('rental_contracts.status', (string) $arguments['status']);
        }

        $locale = $this->locale();
        $money = \DB::table('rental_contract_infos')
            ->whereIn('rental_contract_id', (clone $query)->select('rental_contracts.id')->toBase())
            ->selectRaw('COALESCE(SUM(contract_value), 0) as value, COALESCE(SUM(paid_amount), 0) as paid, COALESCE(SUM(remaining_amount), 0) as remaining')
            ->first();

        $closed = $this->applyPeriod((clone $base)->where('rental_contracts.status', 'completed'), 'COALESCE(rental_contracts.closed_at, rental_contracts.updated_at)', $period);
        $running = (clone $base)->whereIn('rental_contracts.status', self::RUNNING);

        return $this->periodSummary($period) + [
            'total' => (clone $query)->count(),
            'by_status' => $this->labeled($this->countBy($query, 'rental_contracts.status'), fn ($status) => $this->statusLabel($status)),
            'money' => [
                'currency' => 'SAR',
                'contracts_value' => round((float) $money->value, 2),
                'paid' => round((float) $money->paid, 2),
                'remaining' => round((float) $money->remaining, 2),
            ],
            'by_nationality' => $this->countByJoin($query, 'rental_contracts', fn ($q) => $q
                ->leftJoin('maids', 'maids.id', '=', 'rental_contracts.maid_id')
                ->leftJoin('nationalities_translations as nt', fn ($join) => $join->on('nt.nationality_id', '=', 'maids.nationality_id')->where('nt.locale', $locale)), 'nt.title'),
            'by_marketer' => $this->countByJoin($query, 'rental_contracts', fn ($q) => $q
                ->leftJoin('admins as marketer', 'marketer.id', '=', 'rental_contracts.marketer_id'), 'marketer.name'),
            'by_branch' => $this->countByJoin($query, 'rental_contracts', fn ($q) => $q
                ->leftJoin('branches', 'branches.id', '=', 'rental_contracts.branch_id'), 'branches.name_ar'),
            'monthly' => $this->monthlyTrend($query, self::DATE),
            'closed_in_period' => [
                'total' => (clone $closed)->count(),
                // available = عادت العاملة للإيواء، transferred = تحوّل لنقل كفالة.
                'by_closure_type' => $this->countBy($closed, 'rental_contracts.closure_type'),
            ],
            // لقطة الآن (لا تتبع الفترة).
            'running_now' => (clone $running)->count(),
            'ending_within_30_days' => (clone $running)->whereHas('info', fn ($info) => $info
                ->whereBetween('ingaz_to', [now()->toDateString(), now()->addDays(30)->toDateString()]))->count(),
            'notes' => 'كل الأرقام للعقود المؤرخة في الفترة، عدا running_now وending_within_30_days فهي لقطة الآن، '
                . 'وclosed_in_period للعقود المكتملة التي أُغلقت في الفترة.',
        ];
    }

    private function statusLabel(string $status): ?string
    {
        return (new RentalContract())->forceFill(['status' => $status])->status_text;
    }
}
