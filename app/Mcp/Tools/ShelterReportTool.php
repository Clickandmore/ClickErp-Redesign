<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\ReportsOnPeriods;
use App\Models\auth\Admin;
use App\Models\Contract;

/**
 * لقطة الإيواء الآن: عقود contracts بحالة shelter، داخل الإيواء وخارجه، بنفس scopes شاشة
 * عقود الإيواء (Contract::insideShelter / outsideShelter).
 */
class ShelterReportTool extends McpTool
{
    use ReportsOnPeriods;

    public function name(): string
    {
        return 'shelter_report';
    }

    public function description(): string
    {
        return 'لقطة الإيواء الآن: عدد العاملات داخل الإيواء وخارجه (في إيجار أو تجربة نقل)، وتوزيع من في الداخل حسب الجنسية '
            . 'والرغبة في العمل والجاهزية للاستلام الفوري، ومن دخل الإيواء في فترة. Shelter snapshot: inside/outside counts and breakdowns.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => $this->periodProperties('تاريخ الوصول للإيواء (لـ arrived_in_period فقط)'),
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'ShelterContract.index';
    }

    public function category(): string
    {
        return 'contracts';
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $period = $this->resolvePeriod($arguments);
        $inside = Contract::query()->insideShelter();
        $locale = $this->locale();

        return [
            'inside_now' => (clone $inside)->count(),
            'outside_now' => Contract::query()->outsideShelter()->count(),
            'inside_by_nationality' => $this->countByJoin($inside, 'contracts', fn ($q) => $q
                ->leftJoin('maids', 'maids.id', '=', 'contracts.maid_id')
                ->leftJoin('nationalities_translations as nt', fn ($join) => $join->on('nt.nationality_id', '=', 'maids.nationality_id')->where('nt.locale', $locale)), 'nt.title'),
            // yes / no / unknown
            'inside_by_wants_to_work' => $this->countByJoin($inside, 'contracts', fn ($q) => $q
                ->leftJoin('contract_infos as ci', 'ci.contract_id', '=', 'contracts.id'), 'ci.wants_to_work'),
            'inside_available_for_instant' => (clone $inside)->whereHas('info', fn ($info) => $info->where('is_available_for_instant', true))->count(),
            'arrived_in_period' => (clone $inside)->whereHas('info', fn ($info) => $this->applyPeriod($info, 'contract_infos.arrival_date', $period))->count(),
        ] + $this->periodSummary($period) + [
            'notes' => 'outside_now: بحالة إيواء لكنها خارج المبنى (مسجلة خارج الإيواء، أو في عقد إيجار سارٍ، أو في تجربة نقل كفالة).',
        ];
    }
}
