<?php

namespace App\Mcp\Tools;

use App\Models\auth\Admin;
use App\Models\RentalContract;

/** عقود الإيجار (تأجير العمالة). RentalContract عليه BranchScoped. */
class ListRentalContractsTool extends McpTool
{
    public function name(): string
    {
        return 'list_rental_contracts';
    }

    public function description(): string
    {
        return 'قائمة عقود الإيجار (تأجير العمالة) مع العدد حسب الحالة، وقيمة العقد والمدفوع والمتبقي وتاريخ الانتهاء. '
            . 'Rental contracts with counts by status, value, paid, remaining and end date.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'status' => [
                    'type' => 'string',
                    'enum' => ['new', 'waiting_contract_send', 'contract_sent', 'contract_signed', 'maid_delivered', 'active', 'completed', 'cancelled', 'suspended', 'frozen'],
                ],
                'from' => ['type' => 'string', 'format' => 'date', 'description' => 'تاريخ العقد من'],
                'to' => ['type' => 'string', 'format' => 'date', 'description' => 'تاريخ العقد إلى'],
                'ending_within_days' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 365, 'description' => 'العقود السارية التي تنتهي خلال عدد أيام'],
                'query' => ['type' => 'string', 'description' => 'رقم العقد أو اسم/جوال العميل'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 20],
            ],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'RentContract.index';
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $query = RentalContract::query();

        foreach (['from' => '>=', 'to' => '<='] as $key => $operator) {
            if (!empty($arguments[$key])) {
                $query->whereDate('rental_contracts.contract_date', $operator, $arguments[$key]);
            }
        }

        if (!empty($arguments['query'])) {
            $term = (string) $arguments['query'];
            $query->where(fn ($q) => $q->where('contract_number', $term)
                ->orWhereHas('user', fn ($client) => $client->where('name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%")));
        }

        if (!empty($arguments['ending_within_days'])) {
            $query->whereNotIn('status', ['completed', 'cancelled'])
                ->whereHas('info', fn ($info) => $info->whereBetween('ingaz_to', [now()->toDateString(), now()->addDays((int) $arguments['ending_within_days'])->toDateString()]));
        }

        $byStatus = (clone $query)->selectRaw('status as k, count(*) as total')->groupBy('status')
            ->pluck('total', 'k')->map(fn ($total) => (int) $total)->all();

        if (!empty($arguments['status'])) {
            $query->where('status', (string) $arguments['status']);
        }

        $total = (clone $query)->count();
        $contracts = $query->with(['user', 'maid', 'info', 'marketer', 'branch'])->latest('id')->limit($this->limit($arguments))->get();

        return [
            'total' => $total,
            'count_by_status' => $byStatus,
            'currency' => 'SAR',
            'contracts' => $contracts->map(fn (RentalContract $contract) => [
                'id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'contract_date' => $this->date($contract->contract_date),
                'status' => $contract->status,
                'status_label' => $contract->status_text,
                'client' => $contract->user ? ['name' => $contract->user->name, 'phone' => $this->mask($contract->user->phone)] : null,
                'maid' => $contract->maid?->name,
                'from' => $this->date($contract->info?->ingaz_from),
                'to' => $this->date($contract->info?->ingaz_to),
                'value' => $contract->info?->contract_value,
                'paid' => $contract->info?->paid_amount,
                'remaining' => $contract->info?->remaining_amount,
                'marketer' => $contract->marketer?->name,
                'branch' => $contract->branch?->name,
            ])->all(),
        ];
    }
}
