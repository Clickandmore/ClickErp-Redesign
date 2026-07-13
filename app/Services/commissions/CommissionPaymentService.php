<?php

namespace App\Services\commissions;

use App\Models\commissions\CommissionBalanceTransaction;
use App\Models\commissions\CommissionPayment;
use Illuminate\Support\Facades\DB;

class CommissionPaymentService
{
    public function __construct(
        private readonly CommissionProfileService $profileService
    ) {}

    public function issuePayment(int $marketerId, float $amount, string $paymentDate, ?string $notes, int $adminId): CommissionPayment
    {
        return DB::transaction(function () use ($marketerId, $amount, $paymentDate, $notes, $adminId) {
            $payment = CommissionPayment::create([
                'marketer_id'  => $marketerId,
                'amount'       => $amount,
                'payment_date' => $paymentDate,
                'notes'        => $notes,
                'status'       => CommissionPayment::STATUS_APPROVED,
                'approved_by'  => $adminId,
                'approved_at'  => now(),
                'created_by'   => $adminId,
            ]);

            $currentBalance = $this->profileService->getMarketerBalance($marketerId);
            $balanceAfter = max(0, $currentBalance - $amount);

            CommissionBalanceTransaction::create([
                'marketer_id'    => $marketerId,
                'type'           => CommissionBalanceTransaction::TYPE_DEBIT,
                'amount'         => $amount,
                'reference_type' => 'commission_payment',
                'reference_id'   => $payment->id,
                'balance_after'  => $balanceAfter,
                'notes'          => "سداد عمولة - سند رقم #{$payment->id}",
                'created_by'     => $adminId,
            ]);

            return $payment;
        });
    }

    public function getPaymentsForMarketer(int $marketerId)
    {
        return CommissionPayment::query()
            ->with(['approvedBy', 'createdBy'])
            ->where('marketer_id', $marketerId)
            ->latest()
            ->get();
    }
}
