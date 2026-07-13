<?php

namespace App\Services\commissions;

use App\Models\auth\Admin;
use App\Models\commissions\CommissionBalanceTransaction;
use App\Models\commissions\CommissionBatch;
use App\Models\commissions\CommissionBatchItem;
use App\Models\commissions\CommissionMarketerOverride;
use App\Models\commissions\CommissionSetting;
use App\Models\IngazDelegation;
use Illuminate\Support\Facades\DB;

class CommissionCalculationService
{
    public function getApplicableSetting(int $marketerId, string $serviceType): ?array
    {
        // Check per-marketer + per-service-type override first
        $override = CommissionMarketerOverride::query()
            ->where('admin_id', $marketerId)
            ->where('service_type', $serviceType)
            ->first();

        if ($override) {
            return ['method' => $override->method, 'value' => (float) $override->value];
        }

        // Then check per-marketer general override (null service_type)
        $generalOverride = CommissionMarketerOverride::query()
            ->where('admin_id', $marketerId)
            ->whereNull('service_type')
            ->first();

        if ($generalOverride) {
            return ['method' => $generalOverride->method, 'value' => (float) $generalOverride->value];
        }

        // Fall back to global settings
        $setting = CommissionSetting::where('service_type', $serviceType)->first();
        if ($setting) {
            return ['method' => $setting->method, 'value' => (float) $setting->value];
        }

        return null;
    }

    public function previewBatch(int $marketerId, array $delegationIds): array
    {
        $delegations = IngazDelegation::query()
            ->with('invoice')
            ->whereIn('id', $delegationIds)
            ->where('marketer_id', $marketerId)
            ->whereNull('commission_batch_id')
            ->get();

        $items = [];
        $totalBase = 0;
        $totalCommission = 0;

        foreach ($delegations as $delegation) {
            $baseAmount = (float) optional($delegation->invoice)->total_due ?? 0;
            $serviceType = $delegation->visa_type ?? 'electronic_ingaz_delegation';
            $setting = $this->getApplicableSetting($marketerId, $serviceType);

            if (! $setting) {
                $method = 'fixed';
                $rateOrAmount = 0;
                $commissionAmount = 0;
            } else {
                $method = $setting['method'];
                $rateOrAmount = $setting['value'];
                $commissionAmount = $method === 'percentage'
                    ? round($baseAmount * $rateOrAmount / 100, 2)
                    : $rateOrAmount;
            }

            $totalBase += $baseAmount;
            $totalCommission += $commissionAmount;

            $items[] = [
                'delegation'       => $delegation,
                'service_type'     => $serviceType,
                'base_amount'      => $baseAmount,
                'method'           => $method,
                'rate_or_amount'   => $rateOrAmount,
                'commission_amount' => $commissionAmount,
            ];
        }

        return [
            'items'            => $items,
            'total_requests'   => count($items),
            'total_base'       => round($totalBase, 2),
            'total_commission' => round($totalCommission, 2),
        ];
    }

    public function approveBatch(int $marketerId, array $delegationIds, ?string $notes, int $adminId): CommissionBatch
    {
        return DB::transaction(function () use ($marketerId, $delegationIds, $notes, $adminId) {
            $preview = $this->previewBatch($marketerId, $delegationIds);

            $batch = CommissionBatch::create([
                'marketer_id'       => $marketerId,
                'total_requests'    => $preview['total_requests'],
                'total_base_amount' => $preview['total_base'],
                'total_commission'  => $preview['total_commission'],
                'status'            => CommissionBatch::STATUS_APPROVED,
                'notes'             => $notes,
                'approved_by'       => $adminId,
                'approved_at'       => now(),
                'created_by'        => $adminId,
            ]);

            foreach ($preview['items'] as $item) {
                CommissionBatchItem::create([
                    'batch_id'             => $batch->id,
                    'ingaz_delegation_id'  => $item['delegation']->id,
                    'service_type'         => $item['service_type'],
                    'base_amount'          => $item['base_amount'],
                    'method'               => $item['method'],
                    'rate_or_amount'       => $item['rate_or_amount'],
                    'commission_amount'    => $item['commission_amount'],
                ]);

                // Mark delegation as commissioned
                $item['delegation']->update([
                    'commissioned_at'      => now(),
                    'commission_batch_id'  => $batch->id,
                ]);
            }

            // Add to marketer balance
            $currentBalance = app(CommissionProfileService::class)->getMarketerBalance($marketerId);
            $balanceAfter = $currentBalance + $preview['total_commission'];

            CommissionBalanceTransaction::create([
                'marketer_id'    => $marketerId,
                'type'           => CommissionBalanceTransaction::TYPE_CREDIT,
                'amount'         => $preview['total_commission'],
                'reference_type' => 'commission_batch',
                'reference_id'   => $batch->id,
                'balance_after'  => $balanceAfter,
                'notes'          => "اعتماد دفعة عمولة رقم #{$batch->id}",
                'created_by'     => $adminId,
            ]);

            return $batch;
        });
    }
}
