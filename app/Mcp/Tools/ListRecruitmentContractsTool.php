<?php

namespace App\Mcp\Tools;

use App\Enums\ingaz\ContractStatusEnum;
use App\Mcp\Tools\Concerns\PresentsRecruitmentContracts;
use App\Models\auth\Admin;

/** قائمة عقود الاستقدام. عزل الفروع يطبّقه BranchScope على موديل Contract تلقائياً. */
class ListRecruitmentContractsTool extends McpTool
{
    use PresentsRecruitmentContracts;

    public function name(): string
    {
        return 'list_recruitment_contracts';
    }

    public function description(): string
    {
        return 'قائمة عقود الاستقدام مع فلترة بالحالة أو التاريخ أو اسم/جوال العميل أو رقم العقد، '
            . 'مع عدد العقود في كل حالة. List recruitment contracts with filters and a per-status count.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'status' => [
                    'type' => 'string',
                    'enum' => array_map(fn (ContractStatusEnum $case) => $case->value, ContractStatusEnum::cases()),
                ],
                'query' => ['type' => 'string', 'description' => 'رقم العقد أو رقم التأشيرة أو اسم/جوال العميل'],
                'from' => ['type' => 'string', 'format' => 'date', 'description' => 'تاريخ العقد من (Y-m-d)'],
                'to' => ['type' => 'string', 'format' => 'date', 'description' => 'تاريخ العقد إلى (Y-m-d)'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 20],
                'offset' => $this->offsetProperty(),
            ],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'RecruitmentContract.index';
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $query = $this->recruitmentContracts();

        $term = trim((string) ($arguments['query'] ?? ''));
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                // arabia_order_number رقمي؛ مقارنته بنص في MySQL تطابق كل صف قيمته 0.
                if (ctype_digit($term)) {
                    $q->orWhere('arabia_order_number', (int) $term);
                }

                $q->orWhere('visa_number', $term)
                    ->orWhere('msaned_number', $term)
                    ->orWhereHas('user', function ($client) use ($term) {
                        $client->where('name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%");
                    });
            });
        }

        foreach (['from' => '>=', 'to' => '<='] as $key => $operator) {
            if (!empty($arguments[$key])) {
                $query->whereDate('contract_date', $operator, $arguments[$key]);
            }
        }

        // العدّ حسب الحالة قبل فلتر الحالة، ليعرف المساعد توزيع النتائج كله.
        $byStatus = (clone $query)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total)
            ->all();

        if (!empty($arguments['status'])) {
            $query->where('status', $arguments['status']);
        }

        $total = (clone $query)->count();
        $contracts = $query->with(['user', 'maid', 'marketer', 'branch'])
            ->latest('id')
            ->offset($this->offset($arguments))
            ->limit($this->limit($arguments))
            ->get();

        return [
            ...$this->pageInfo($total, $contracts->count(), $arguments),
            'count_by_status' => $byStatus,
            'contracts' => $contracts->map(fn ($contract) => $this->contractSummary($contract))->all(),
        ];
    }
}
