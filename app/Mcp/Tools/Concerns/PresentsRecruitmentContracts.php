<?php

namespace App\Mcp\Tools\Concerns;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;

trait PresentsRecruitmentContracts
{
    /** نفس الأنواع التي تعرضها شاشة عقود الاستقدام. */
    protected function recruitmentContracts()
    {
        return Contract::query()->whereIn('type', ['recruitment', 'maid']);
    }

    protected function statusLabel(?string $status): ?string
    {
        return $status ? (ContractStatusEnum::tryFrom($status)?->lang() ?? $status) : null;
    }

    protected function contractSummary(Contract $contract): array
    {
        $client = $contract->user;

        return [
            'id' => $contract->id,
            'url' => $this->link('RecruitmentContract.show', $contract->id),
            'contract_number' => $contract->arabia_order_number,
            'contract_date' => $this->date($contract->contract_date),
            'status' => $contract->status,
            'status_label' => $this->statusLabel($contract->status),
            'client' => $client ? [
                'id' => $client->id ?? null,
                'name' => $client->name ?? null,
                'phone' => $this->mask($client->phone ?? null),
            ] : null,
            'maid' => $contract->maid ? [
                'id' => $contract->maid->id,
                'name' => $contract->maid->name,
            ] : null,
            'marketer' => $contract->marketer?->name,
            'branch' => $contract->branch?->name,
        ];
    }
}
