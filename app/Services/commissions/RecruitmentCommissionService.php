<?php

namespace App\Services\commissions;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\commissions\CommissionBalanceTransaction;
use App\Models\commissions\CommissionBatch;
use App\Models\commissions\CommissionBatchItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecruitmentCommissionService
{
    public const ZERO_SOURCE = 'recruitment_returned_zero';
    public const SETTLEMENT_PENDING = 'pending';
    public const SETTLEMENT_DEDUCTED = 'deducted';
    public const SETTLEMENT_ZEROED = 'zeroed';

    public function isEligible(Contract $contract): bool
    {
        return ! in_array((string) $contract->status, [
            ContractStatusEnum::Refunded->value,
            ContractStatusEnum::Back->value,
        ], true);
    }

    public function assertEligible(Contract $contract): void
    {
        if ($this->isEligible($contract)) {
            return;
        }

        throw ValidationException::withMessages([
            'operations' => 'عقد الاستقدام مسترد أو مرتجع، ولذلك لا يستحق عمولة.',
        ]);
    }

    public function settleReturnedContract(
        Contract $contract,
        ?int $adminId = null,
        ?string $reason = null
    ): ?CommissionBatchItem {
        return DB::transaction(function () use ($contract, $adminId, $reason) {
            $lockedContract = Contract::query()
                ->with(['order.details', 'user', 'invoice', 'info'])
                ->whereKey($contract->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedContract || $this->isEligible($lockedContract)) {
                return null;
            }

            $reason = trim((string) ($reason ?: $this->defaultReason($lockedContract)));
            $item = CommissionBatchItem::query()
                ->with('batch')
                ->where('source_type', CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT)
                ->where('source_id', $lockedContract->id)
                ->lockForUpdate()
                ->first();

            if ($item) {
                if (
                    $item->batch?->status === CommissionBatch::STATUS_APPROVED
                    && (float) $item->commission_amount > 0
                    && ! $item->commission_reversal_transaction_id
                    && ! $this->isZeroed($item)
                ) {
                    $item->update([
                        'commission_reversal_reason' => $reason,
                    ]);
                }

                return $item->fresh();
            }

            $marketerId = (int) ($lockedContract->marketer_id
                ?: $lockedContract->order?->resolveResponsibleAdminId());
            if ($marketerId <= 0) {
                return null;
            }

            $baseAmount = round((float) ($lockedContract->invoice?->total_due
                ?? $lockedContract->info?->contract_value
                ?? $lockedContract->contract_cost
                ?? 0), 2);

            $batch = CommissionBatch::query()->create([
                'marketer_id' => $marketerId,
                'total_requests' => 1,
                'total_base_amount' => $baseAmount,
                'total_commission' => 0,
                'status' => CommissionBatch::STATUS_APPROVED,
                'notes' => 'إقفال عمولة عقد مسترد/مرتجع بصفر - عقد #' . $lockedContract->id,
                'approved_by' => $adminId,
                'approved_at' => now(),
                'created_by' => $adminId,
            ]);

            return CommissionBatchItem::query()->create([
                'batch_id' => $batch->id,
                'source_type' => CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT,
                'source_id' => $lockedContract->id,
                'order_id' => $lockedContract->order_id,
                'contract_id' => $lockedContract->id,
                'operation_reference' => (string) $lockedContract->id,
                'client_name' => $lockedContract->user?->name,
                'operation_date' => $lockedContract->contract_date ?: $lockedContract->created_at,
                'service_type' => 'recruitment',
                'base_amount' => $baseAmount,
                'method' => 'fixed',
                'rate_or_amount' => 0,
                'commission_amount' => 0,
                'legacy_source' => self::ZERO_SOURCE,
                'commission_reversed_at' => now(),
                'commission_reversal_reason' => $reason,
            ]);
        });
    }

    public function resolveSettlement(
        CommissionBatchItem $item,
        string $decision,
        int $adminId,
        ?string $notes = null
    ): CommissionBatchItem {
        return DB::transaction(function () use ($item, $decision, $adminId, $notes) {
            $lockedItem = CommissionBatchItem::query()
                ->with('batch')
                ->whereKey($item->id)
                ->lockForUpdate()
                ->firstOrFail();
            $contract = Contract::query()->whereKey($lockedItem->source_id)->lockForUpdate()->firstOrFail();

            if (
                $lockedItem->source_type !== CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT
                || $this->isEligible($contract)
                || $lockedItem->commission_reversal_transaction_id
                || $this->isZeroed($lockedItem)
                || (float) $lockedItem->commission_amount <= 0
            ) {
                throw ValidationException::withMessages([
                    'settlement' => 'هذه العمولة ليست في حالة تسمح باعتماد تسوية استرداد.',
                ]);
            }

            if ($decision === self::SETTLEMENT_DEDUCTED) {
                $this->reverseItem(
                    $lockedItem,
                    $contract,
                    $adminId,
                    trim((string) ($notes ?: $lockedItem->commission_reversal_reason ?: $this->defaultReason($contract)))
                );
            } elseif ($decision === self::SETTLEMENT_ZEROED) {
                $this->zeroAvailableCommission($lockedItem, $contract, $adminId, $notes);
            } else {
                throw ValidationException::withMessages([
                    'settlement' => 'قرار تسوية العمولة غير صحيح.',
                ]);
            }

            $lockedItem->update([
                'commission_reversed_at' => $decision === self::SETTLEMENT_ZEROED ? now() : $lockedItem->commission_reversed_at,
                'commission_reversal_reason' => $decision === self::SETTLEMENT_ZEROED
                    ? '[ZEROED] ' . (trim((string) $notes) ?: 'أُقفلت عمولة العقد المسترد بصفر دون إنشاء مستحق على المسوق.')
                    : $lockedItem->commission_reversal_reason,
            ]);

            return $lockedItem->fresh();
        });
    }

    private function zeroAvailableCommission(
        CommissionBatchItem $item,
        Contract $contract,
        int $adminId,
        ?string $notes
    ): ?CommissionBalanceTransaction {
        $marketerId = (int) $item->batch?->marketer_id;
        $credit = (float) CommissionBalanceTransaction::query()
            ->where('marketer_id', $marketerId)
            ->where('type', CommissionBalanceTransaction::TYPE_CREDIT)
            ->sum('amount');
        $debit = (float) CommissionBalanceTransaction::query()
            ->where('marketer_id', $marketerId)
            ->where('type', CommissionBalanceTransaction::TYPE_DEBIT)
            ->sum('amount');
        $currentBalance = round($credit - $debit, 2);
        $amount = min(round((float) $item->commission_amount, 2), max($currentBalance, 0));

        if ($amount <= 0) {
            return null;
        }

        return CommissionBalanceTransaction::query()->create([
            'marketer_id' => $marketerId,
            'type' => CommissionBalanceTransaction::TYPE_DEBIT,
            'amount' => $amount,
            'reference_type' => 'commission_zero_adjustment',
            'reference_id' => $item->id,
            'balance_after' => round($currentBalance - $amount, 2),
            'notes' => 'تصفير عمولة عقد استقدام مسترد #' . $contract->id
                . ($notes ? ' - ' . trim($notes) : ''),
            'created_by' => $adminId,
        ]);
    }

    private function reverseItem(
        CommissionBatchItem $item,
        Contract $contract,
        ?int $adminId,
        string $reason
    ): ?CommissionBalanceTransaction {
        $item->refresh();
        if ($item->commission_reversal_transaction_id) {
            return CommissionBalanceTransaction::query()->find($item->commission_reversal_transaction_id);
        }

        $marketerId = (int) $item->batch?->marketer_id;
        if ($marketerId <= 0) {
            throw new \RuntimeException('تعذر تحديد المسوق المرتبط بعمولة عقد الاستقدام لإجراء العكس.');
        }

        $credit = (float) CommissionBalanceTransaction::query()
            ->where('marketer_id', $marketerId)
            ->where('type', CommissionBalanceTransaction::TYPE_CREDIT)
            ->sum('amount');
        $debit = (float) CommissionBalanceTransaction::query()
            ->where('marketer_id', $marketerId)
            ->where('type', CommissionBalanceTransaction::TYPE_DEBIT)
            ->sum('amount');
        $amount = round((float) $item->commission_amount, 2);

        $transaction = CommissionBalanceTransaction::query()->create([
            'marketer_id' => $marketerId,
            'type' => CommissionBalanceTransaction::TYPE_DEBIT,
            'amount' => $amount,
            'reference_type' => 'commission_reversal',
            'reference_id' => $item->id,
            'balance_after' => round($credit - $debit - $amount, 2),
            'notes' => 'عكس عمولة عقد استقدام #' . $contract->id . ' - ' . $reason,
            'created_by' => $adminId,
        ]);

        $item->update([
            'commission_reversal_transaction_id' => $transaction->id,
            'commission_reversed_at' => now(),
            'commission_reversal_reason' => $reason,
        ]);

        return $transaction;
    }

    private function defaultReason(Contract $contract): string
    {
        return $contract->status === ContractStatusEnum::Refunded->value
            ? 'العقد مسترد ولا يستحق عمولة.'
            : 'العقد مرتجع ولا يستحق عمولة.';
    }

    public function isZeroed(CommissionBatchItem $item): bool
    {
        return $item->legacy_source === self::ZERO_SOURCE
            || (
                ! $item->commission_reversal_transaction_id
                && $item->commission_reversed_at !== null
                && str_starts_with((string) $item->commission_reversal_reason, '[ZEROED]')
            );
    }
}
