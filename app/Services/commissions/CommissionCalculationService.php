<?php

namespace App\Services\commissions;

use App\Models\commissions\CommissionBalanceTransaction;
use App\Models\commissions\CommissionBatch;
use App\Models\commissions\CommissionBatchItem;
use App\Models\commissions\CommissionMarketerOverride;
use App\Models\commissions\CommissionSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommissionCalculationService
{
    public function __construct(
        private readonly CommissionOperationService $operationService,
        private readonly DelegationCommissionService $delegationCommissionService,
        private readonly RecruitmentCommissionService $recruitmentCommissionService,
        private readonly CommissionAccrualService $accrualService
    ) {}

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

    public function previewBatch(int $marketerId, array $operationKeys, array $adjustments = [], array $filters = []): array
    {
        $operations = $this->operationService->findSelected($marketerId, $operationKeys);

        $items = [];
        $totalBase = 0;
        $totalCommission = 0;

        foreach ($operations as $operation) {
            $baseAmount = (float) $operation['base_amount'];
            $serviceType = $operation['service_type'];
            $setting = $this->getApplicableSetting($marketerId, $serviceType);
            $adjustment = $adjustments[$operation['key']] ?? null;
            // The review screen always starts with a fixed amount. A percentage is
            // only used when the reviewer explicitly selects it for this approval.
            $method = $adjustment['method'] ?? 'fixed';
            $rateOrAmount = (float) ($adjustment['value'] ?? $setting['value'] ?? 0);
            $commissionAmount = $this->calculateAmount($baseAmount, $method, $rateOrAmount);

            $totalBase += $baseAmount;
            $totalCommission += $commissionAmount;

            $items[] = [
                'operation'        => $operation,
                'service_type'     => $serviceType,
                'base_amount'      => $baseAmount,
                'method'           => $method,
                'rate_or_amount'   => $rateOrAmount,
                'commission_amount' => $commissionAmount,
            ];
        }

        $deductions = $this->recruitmentCommissionService->pendingDeductions($marketerId, $filters);
        $totalDeductions = round((float) $deductions->sum('commission_amount'), 2);

        return [
            'returned_deductions' => $deductions,
            'total_deductions' => $totalDeductions,
            'net_commission' => round($totalCommission, 2),
            'items'            => $items,
            'total_requests'   => count($items),
            'total_base'       => round($totalBase, 2),
            'total_commission' => round($totalCommission, 2),
        ];
    }

    public function approveBatch(
        int $marketerId,
        array $operationKeys,
        array $adjustments,
        ?string $notes,
        int $adminId,
        array $returnedItemIds = []
    ): CommissionBatch
    {
        return DB::transaction(function () use ($marketerId, $operationKeys, $adjustments, $notes, $adminId, $returnedItemIds) {
            \App\Models\auth\Admin::query()->whereKey($marketerId)->lockForUpdate()->firstOrFail();
            $preview = $this->previewBatch($marketerId, $operationKeys, $adjustments);

            $delegationIds = collect($operationKeys)
                ->filter(fn ($key) => is_string($key) && str_starts_with($key, CommissionOperationService::SOURCE_DELEGATION . ':'))
                ->map(fn ($key) => (int) substr($key, strlen(CommissionOperationService::SOURCE_DELEGATION . ':')))
                ->filter()
                ->unique()
                ->values();

            if ($delegationIds->isNotEmpty()) {
                $lockedDelegations = \App\Models\IngazDelegation::query()
                    ->with('invoice.recruitmentCreditNotes')
                    ->whereIn('id', $delegationIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($delegationIds as $delegationId) {
                    $delegation = $lockedDelegations->get($delegationId);
                    if (!$delegation) {
                        throw ValidationException::withMessages([
                            'operations' => 'تعذر اعتماد العمولة لأن أحد التفاويض لم يعد موجودًا.',
                        ]);
                    }
                    $this->delegationCommissionService->assertEligible($delegation);
                }
            }

            $recruitmentIds = collect($operationKeys)
                ->filter(fn ($key) => is_string($key) && str_starts_with($key, CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT . ':'))
                ->map(fn ($key) => (int) substr($key, strlen(CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT . ':')))
                ->filter()
                ->unique()
                ->values();

            if ($recruitmentIds->isNotEmpty()) {
                $lockedContracts = \App\Models\Contract::query()
                    ->whereIn('id', $recruitmentIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($recruitmentIds as $contractId) {
                    $contract = $lockedContracts->get($contractId);
                    if (! $contract) {
                        throw ValidationException::withMessages([
                            'operations' => 'تعذر اعتماد العمولة لأن أحد عقود الاستقدام لم يعد موجودًا.',
                        ]);
                    }
                    $this->recruitmentCommissionService->assertEligible($contract);
                }
            }

            if ($preview['total_requests'] !== count(array_unique($operationKeys))) {
                throw ValidationException::withMessages([
                    'operations' => 'تعذر اعتماد بعض العمليات: قد تكون احتُسبت سابقًا أو لا تخص هذا الموظف.',
                ]);
            }
            if ($preview['total_commission'] <= 0) {
                throw ValidationException::withMessages([
                    'values' => 'يجب أن يكون إجمالي العمولة أكبر من صفر قبل الاعتماد.',
                ]);
            }

            $currentBalance = app(CommissionProfileService::class)->getMarketerBalance($marketerId);
            $existingAccrualBatch = CommissionBatch::query()
                ->where('marketer_id', $marketerId)
                ->whereNotNull('accrual_constraint_id')
                ->latest('approved_at')
                ->first(['id', 'payable_account_id']);

            if ($currentBalance > 0 && ! $existingAccrualBatch) {
                throw ValidationException::withMessages([
                    'operations' => 'يوجد رصيد عمولات قديم بلا قيد استحقاق. يجب صرفه أو تسويته أولًا قبل اعتماد دفعة بالنظام المحاسبي الجديد.',
                ]);
            }

            if ($currentBalance > 0
                && $existingAccrualBatch?->payable_account_id) {
                $automaticPayableAccount = $this->accrualService->payableAccountForMarketer($marketerId, $adminId);

                if ((int) $existingAccrualBatch->payable_account_id !== (int) $automaticPayableAccount->id) {
                    throw ValidationException::withMessages([
                        'operations' => 'الرصيد القائم مرتبط بحساب استحقاق مختلف عن حساب الموظف التلقائي رقم '
                            . $automaticPayableAccount->code . '؛ يجب تسويته أولًا.',
                    ]);
                }
            }

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
                $operation = $item['operation'];
                CommissionBatchItem::create([
                    'batch_id'             => $batch->id,
                    'ingaz_delegation_id'  => $operation['source_type'] === CommissionOperationService::SOURCE_DELEGATION
                        ? $operation['source_id']
                        : null,
                    'source_type'          => $operation['source_type'],
                    'source_id'            => $operation['source_id'],
                    'order_id'             => $operation['order_id'],
                    'contract_id'          => $operation['contract_id'],
                    'operation_reference'  => $operation['reference'],
                    'client_name'          => $operation['client_name'],
                    'operation_date'       => $operation['operation_date'],
                    'service_type'         => $item['service_type'],
                    'base_amount'          => $item['base_amount'],
                    'method'               => $item['method'],
                    'rate_or_amount'       => $item['rate_or_amount'],
                    'commission_amount'    => $item['commission_amount'],
                ]);

                if ($operation['source_type'] === CommissionOperationService::SOURCE_DELEGATION) {
                    \App\Models\IngazDelegation::query()
                        ->whereKey($operation['source_id'])
                        ->update([
                            'commissioned_at' => now(),
                            'commission_batch_id' => $batch->id,
                        ]);
                }
            }

            // Add to marketer balance
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

            $this->accrualService->createForBatch($batch, $adminId);


            return $batch->fresh(['accrualConstraint', 'expenseAccount', 'payableAccount']);
        });
    }

    public function calculateAmount(float $baseAmount, string $method, float $rateOrAmount): float
    {
        return $method === 'percentage'
            ? round($baseAmount * $rateOrAmount / 100, 2)
            : round($rateOrAmount, 2);
    }
}
