<?php

namespace App\Support;

final class RentalContractSettlement
{
    /**
     * Settle a rental contract that is being closed.
     *
     * The company earns the days the client actually used, plus any days kept
     * past the end date. Everything else is a balance between what was paid
     * and what was earned:
     *
     *   paid > earned  ->  the difference goes back to the client
     *   paid < earned  ->  the difference is owed to the company
     *
     * A refund can therefore never exceed what the client actually paid, which
     * is the rule the previous "value of the unused days" formula broke: an
     * unpaid contract proposed refunding its full value.
     */
    public static function calculate(
        float $contractValue,
        int $durationDays,
        float $paidAmount,
        int $usedDays,
        int $overdueDays = 0
    ): array {
        $contractValue = self::money(max(0, $contractValue));
        $paidAmount = self::money(max(0, $paidAmount));
        $durationDays = max(0, $durationDays);
        $usedDays = max(0, min($usedDays, $durationDays));
        $overdueDays = max(0, $overdueDays);

        $rawDailyRate = $durationDays > 0 ? ($contractValue / $durationDays) : 0.0;

        $usedCost = self::money(min($contractValue, $rawDailyRate * $usedDays));
        $overdueCost = self::money($rawDailyRate * $overdueDays);
        $earnedAmount = self::money($usedCost + $overdueCost);

        $balance = self::money($paidAmount - $earnedAmount);

        return [
            'daily_rate' => self::money($rawDailyRate),
            'raw_daily_rate' => $rawDailyRate,
            'used_days' => $usedDays,
            'used_cost' => $usedCost,
            'overdue_days' => $overdueDays,
            'overdue_cost' => $overdueCost,
            'earned_amount' => $earnedAmount,
            'refund_value' => $balance > 0 ? $balance : 0.0,
            'company_due_value' => $balance < 0 ? self::money(-$balance) : 0.0,
            'settlement_type' => $balance < 0 ? 'company_due' : 'refund',
        ];
    }

    private static function money(float $amount): float
    {
        return round($amount, 2);
    }
}
