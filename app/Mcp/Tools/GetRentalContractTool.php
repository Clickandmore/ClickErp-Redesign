<?php

namespace App\Mcp\Tools;

use App\Mcp\McpToolException;
use App\Models\auth\Admin;
use App\Models\RentalContract;

/** تفاصيل عقد إيجار واحد. RentalContract عليه BranchScoped. */
class GetRentalContractTool extends McpTool
{
    public function name(): string
    {
        return 'get_rental_contract';
    }

    public function description(): string
    {
        return 'تفاصيل عقد إيجار واحد بالمعرّف أو رقم العقد: العميل والعاملة والمدة والقيمة والمدفوع والمتبقي وأيام التأخير. '
            . 'Full details of one rental contract by id or contract number.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'contract_number' => ['type' => 'string'],
            ],
        ];
    }

    protected function gateRoute(): ?string
    {
        // RentContract.show بلا إدخال في المصفوفة، فالصلاحية من قائمة العقود نفسها.
        return 'RentContract.index';
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $query = RentalContract::query()->with(['user', 'maid', 'info', 'marketer', 'branch']);

        if (!empty($arguments['id'])) {
            $query->whereKey((int) $arguments['id']);
        } elseif (!empty($arguments['contract_number'])) {
            $query->where('contract_number', (string) $arguments['contract_number']);
        } else {
            throw new McpToolException('أرسل id أو contract_number.');
        }

        $contract = $query->first();

        if (!$contract) {
            throw new McpToolException('العقد غير موجود أو خارج نطاق فرعك.');
        }

        $info = $contract->info;

        return [
            'id' => $contract->id,
            'url' => $this->link('RentContract.show', $contract->id, 'RentContract.index'),
            'contract_number' => $contract->contract_number,
            'contract_date' => $this->date($contract->contract_date),
            'status' => $contract->status,
            'status_label' => $contract->status_text,
            'client' => $contract->user ? ['id' => $contract->user->id, 'name' => $contract->user->name, 'phone' => $this->mask($contract->user->phone)] : null,
            'maid' => $contract->maid ? ['id' => $contract->maid->id, 'name' => $contract->maid->name] : null,
            'marketer' => $contract->marketer?->name,
            'branch' => $contract->branch?->name,
            'from' => $this->date($info?->ingaz_from),
            'to' => $this->date($info?->ingaz_to),
            'days_overdue' => app(\App\Services\RentalContractOverdueSmsService::class)->daysOverdue($contract),
            'currency' => 'SAR',
            'value' => $info?->contract_value,
            'paid' => $info?->paid_amount,
            'remaining' => $info?->remaining_amount,
            'payment_status' => $info?->payment_status,
            'closed_at' => $this->date($contract->closed_at),
            'closure_type' => $contract->closure_type,
            'notes' => $this->maskText($info?->notes),
        ];
    }
}
