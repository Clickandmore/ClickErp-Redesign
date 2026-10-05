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
        int $overdueDays = 0,
        ?string $dailyDiscountType = null,
        float $dailyDiscountValue = 0,
        ?float $earlyClosureDailyRate = null,
        ?float $overdueDailyRate = null
    ): array {
        $contractValue = self::money(max(0, $contractValue));
        $paidAmount = self::money(max(0, $paidAmount));
        $durationDays = max(0, $durationDays);
        $usedDays = max(0, min($usedDays, $durationDays));
        $overdueDays = max(0, $overdueDays);

        $rawDailyRate = $durationDays > 0 ? ($contractValue / $durationDays) : 0.0;

        if (($earlyClosureDailyRate !== null || $overdueDailyRate !== null) && $dailyDiscountType !== null) {
            throw new \InvalidArgumentException('Custom daily rates cannot be combined with a daily discount.');
        }
        foreach ([$earlyClosureDailyRate, $overdueDailyRate] as $rate) {
            if ($rate !== null && (!is_finite($rate) || $rate < 0)) {
                throw new \InvalidArgumentException('Daily rates must be finite and non-negative.');
            }
        }
        // A custom early rate applies only to an incomplete period. A full
        // contract keeps its agreed price, regardless of the overdue rate.
        $hasEarlyRate = $earlyClosureDailyRate !== null && $durationDays > 0 && $usedDays < $durationDays;

        $dailyDiscountValue = max(0, $dailyDiscountValue);
        $discountBaseDailyRate = self::money($rawDailyRate);
        $discountPerDay = match ($dailyDiscountType) {
            'percentage' => $discountBaseDailyRate * min(100, $dailyDiscountValue) / 100,
            'fixed' => min($discountBaseDailyRate, $dailyDiscountValue),
            default => 0.0,
        };
        $adjustedDailyRate = $dailyDiscountType
            ? max(0, $discountBaseDailyRate - $discountPerDay)
            : $rawDailyRate;

        $usedDailyRate = $hasEarlyRate ? self::money($earlyClosureDailyRate) : $adjustedDailyRate;
        $effectiveOverdueDailyRate = $overdueDailyRate !== null
            ? self::money($overdueDailyRate) : $adjustedDailyRate;
        $usedCost = $hasEarlyRate
            ? self::money($usedDailyRate * $usedDays)
            : self::money(min($contractValue, $usedDailyRate * $usedDays));
        $overdueCost = self::money($effectiveOverdueDailyRate * $overdueDays);
        $earnedAmount = self::money($usedCost + $overdueCost);
        $undiscountedEarnedAmount = self::money(
            min($contractValue, $rawDailyRate * $usedDays) + ($rawDailyRate * $overdueDays)
        );

        $balance = self::money($paidAmount - $earnedAmount);

        return [
            'daily_rate' => self::money($rawDailyRate),
            'raw_daily_rate' => $rawDailyRate,
            'adjusted_daily_rate' => self::money($adjustedDailyRate),
            'used_daily_rate' => self::money($usedDailyRate),
            'overdue_daily_rate' => self::money($effectiveOverdueDailyRate),
            'early_closure_daily_rate_override' => $hasEarlyRate ? self::money($earlyClosureDailyRate) : null,
            'overdue_daily_rate_override' => $overdueDailyRate !== null ? self::money($overdueDailyRate) : null,
            'daily_discount_type' => $dailyDiscountType,
            'daily_discount_value' => self::money($dailyDiscountValue),
            'discount_per_day' => self::money($discountPerDay),
            'total_discount' => self::money(max(0, $undiscountedEarnedAmount - $earnedAmount)),
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
