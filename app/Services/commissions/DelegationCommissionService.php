<?php

namespace App\Services\commissions;

use App\Models\IngazDelegation;
use App\Models\commissions\CommissionBalanceTransaction;
use App\Models\commissions\CommissionBatchItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DelegationCommissionService
{
    public function isEligible(IngazDelegation $delegation): bool
    {
        return $this->ineligibilityReason($delegation) === null;
    }

    public function ineligibilityReason(IngazDelegation $delegation): ?string
    {
        if ($delegation->status === IngazDelegation::STATUS_CANCELLED) {
            return 'التفويض ملغي — غير مؤهل للعمولة';
        }

        $delegation->loadMissing('invoice.recruitmentCreditNotes');
        $invoice = $delegation->invoice;

        if (!$invoice) {
            return null;
        }

        $invoiceTotal = round(max(0, (float) $invoice->total_due), 2);
        $creditedTotal = round((float) $invoice->recruitmentCreditNotes->sum('total'), 2);

        return $invoiceTotal > 0 && $creditedTotal + 0.01 >= $invoiceTotal
            ? 'تم عكس قيمة فاتورة التفويض بالكامل بإشعار دائن — غير مؤهل للعمولة'
            : null;
    }

    public function assertEligible(IngazDelegation $delegation): void
    {
        if ($this->isEligible($delegation)) {
            return;
        }

        throw ValidationException::withMessages([
            'operations' => 'التفويض غير مؤهل للعمولة: التفويض ملغي أو معكوس بالكامل بإشعار دائن.',
        ]);
    }

    public function reverseApprovedCommission(
        IngazDelegation $delegation,
        ?int $adminId,
        string $reason
    ): ?CommissionBalanceTransaction {
        return DB::transaction(function () use ($delegation, $adminId, $reason) {
            $item = CommissionBatchItem::query()
                ->with('batch')
                ->where('source_type', CommissionOperationService::SOURCE_DELEGATION)
                ->where('source_id', $delegation->id)
                ->lockForUpdate()
                ->first();

            if (
                !$item
                || $item->batch?->status !== \App\Models\commissions\CommissionBatch::STATUS_APPROVED
                || (float) $item->commission_amount <= 0
            ) {
                return null;
            }

            $item->refresh();
            if ($item->commission_reversal_transaction_id) {
                return CommissionBalanceTransaction::query()->find($item->commission_reversal_transaction_id);
            }

            $marketerId = (int) $item->batch?->marketer_id;
            if (!$marketerId) {
                throw new \RuntimeException('تعذر تحديد الموظف المرتبط بعمولة التفويض لإجراء العكس.');
            }

            $currentBalance = app(CommissionProfileService::class)->getMarketerBalance($marketerId);
            $amount = round((float) $item->commission_amount, 2);
            $transaction = CommissionBalanceTransaction::query()->create([
                'marketer_id' => $marketerId,
                'type' => CommissionBalanceTransaction::TYPE_DEBIT,
                'amount' => $amount,
                'reference_type' => 'commission_reversal',
                'reference_id' => $item->id,
                'balance_after' => round($currentBalance - $amount, 2),
                'notes' => 'عكس عمولة تفويض إنجاز #' . $delegation->id . ' - ' . trim($reason),
                'created_by' => $adminId,
            ]);

            $item->update([
                'commission_reversal_transaction_id' => $transaction->id,
                'commission_reversed_at' => now(),
                'commission_reversal_reason' => trim($reason),
            ]);

            return $transaction;
        });
    }

    public function cancelAfterFullCreditNote(IngazDelegation $delegation, ?int $adminId): bool
    {
        $delegation->refresh()->load('invoice.recruitmentCreditNotes');
        if ($this->hasFullCreditNote($delegation) === false) {
            return false;
        }

        if ($delegation->status !== IngazDelegation::STATUS_CANCELLED) {
            $delegation->update(['status' => IngazDelegation::STATUS_CANCELLED]);
        }

        $this->reverseApprovedCommission(
            $delegation,
            $adminId,
            'تم عكس فاتورة التفويض بالكامل بإشعار دائن.'
        );

        return true;
    }

    private function hasFullCreditNote(IngazDelegation $delegation): bool
    {
        $invoice = $delegation->invoice;
        if (!$invoice || (float) $invoice->total_due <= 0) {
            return false;
        }

        return round((float) $invoice->recruitmentCreditNotes->sum('total'), 2) + 0.01
            >= round((float) $invoice->total_due, 2);
    }
}
