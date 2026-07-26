<?php

namespace App\Support;

final class RentalContractPricing
{
    public const VAT_RATE = 0.15;
    public const DAYS_PER_MONTH = 30;

    /**
     * Split a VAT-exclusive rental package price into an exempt worker salary
     * and a taxable service, then add VAT to the service only.
     */
    public static function fromNetPrice(
        float $netPrice,
        float $monthlySalary,
        int $durationDays,
        int $durationMonths = 0,
        float $originalPrice = 0
    ): array {
        $netPrice = self::money(max(0, $netPrice));
        $durationDays = self::durationDays($durationDays, $durationMonths);
        $salaryForPeriod = $durationDays > 0
            ? $monthlySalary * ($durationDays / self::DAYS_PER_MONTH)
            : $monthlySalary * max(1, $durationMonths);
        $salaryExemptAmount = self::money(min($netPrice, max(0, $salaryForPeriod)));
        $taxableSubtotal = self::money(max(0, $netPrice - $salaryExemptAmount));
        $vatAmount = self::money($taxableSubtotal * self::VAT_RATE);
        $totalDue = self::money($netPrice + $vatAmount);
        $originalPrice = self::money(max($netPrice, $originalPrice));

        return [
            'subtotal' => $originalPrice,
            'discount' => self::money(max(0, $originalPrice - $netPrice)),
            'net_subtotal' => $netPrice,
            'taxable_subtotal' => $taxableSubtotal,
            'salary_exempt_amount' => $salaryExemptAmount,
            'vat_amount' => $vatAmount,
            'total_due' => $totalDue,
            'duration_days' => $durationDays,
        ];
    }

    /**
     * Split an agreed VAT-inclusive total while keeping the customer total
     * unchanged. This is used by packages whose stored price is already final.
     */
    public static function fromGrossPrice(
        float $grossPrice,
        float $monthlySalary,
        int $durationDays,
        int $durationMonths = 0
    ): array {
        $grossPrice = self::money(max(0, $grossPrice));
        $durationDays = self::durationDays($durationDays, $durationMonths);
        $salaryForPeriod = $durationDays > 0
            ? $monthlySalary * ($durationDays / self::DAYS_PER_MONTH)
            : $monthlySalary * max(1, $durationMonths);
        $salaryExemptAmount = self::money(min($grossPrice, max(0, $salaryForPeriod)));
        $taxableGross = self::money(max(0, $grossPrice - $salaryExemptAmount));
        $taxableSubtotal = $taxableGross > 0
            ? self::money($taxableGross / (1 + self::VAT_RATE))
            : 0.0;
        $vatAmount = self::money(max(0, $taxableGross - $taxableSubtotal));
        $netSubtotal = self::money($salaryExemptAmount + $taxableSubtotal);

        return [
            'subtotal' => $netSubtotal,
            'discount' => 0.0,
            'net_subtotal' => $netSubtotal,
            'taxable_subtotal' => $taxableSubtotal,
            'salary_exempt_amount' => $salaryExemptAmount,
            'vat_amount' => $vatAmount,
            'total_due' => $grossPrice,
            'duration_days' => $durationDays,
        ];
    }

    public static function durationDays(int $durationDays, int $durationMonths = 0): int
    {
        if ($durationDays > 0) {
            return $durationDays;
        }

        return max(0, $durationMonths) * self::DAYS_PER_MONTH;
    }

    private static function money(float $amount): float
    {
        return round($amount, 2);
    }
}
