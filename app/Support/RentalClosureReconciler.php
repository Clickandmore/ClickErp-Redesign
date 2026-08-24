<?php

namespace App\Support;

/**
 * حساب تسوية إنهاء عقد التأجير على محورين منفصلين.
 *
 * الخلل في المحرك الحالي (RentalContractSettlement) أنه يحسب محور النقدية وحده
 * ثم يرحّل نتيجته على حساب الذمم، فيُثبت على العميل مبلغاً سبق إثباته في
 * الفاتورة. المحوران هنا مفصولان، ولكل واحد مستنده:
 *
 *   الذمّة   = المفوتر − المستحَق  ->  إشعار دائن أو مدين ضريبي
 *   النقدية  = المدفوع − المستحَق  ->  سند صرف، أو مطالبة بلا قيد
 *
 * الثابتة التي تُختبر بها صحة أي عقد بعد الإنهاء:
 *
 *   رصيد حساب العميل ≡ المستحَق − المدفوع
 *
 * هذه الفئة حاسبة نقية لا تُنشئ أي قيد. يستخدمها مسار الإنهاء وأمر التدقيق كي
 * تبقى قاعدة الحساب واحدة في التشغيل والمراجعة.
 */
final class RentalClosureReconciler
{
    public const VAT_RATE = 0.15;

    /** فرق أقل من نصف ريال يُعتبر تقريباً حسابياً لا خللاً. */
    public const TOLERANCE = 0.50;

    public static function settle(float $invoiced, float $paid, float $earned): array
    {
        $invoiced = self::money(max(0, $invoiced));
        $paid = self::money(max(0, $paid));
        $earned = self::money(max(0, $earned));

        $receivableDelta = self::money($invoiced - $earned);
        $cashDelta = self::money($paid - $earned);

        return [
            'invoiced' => $invoiced,
            'paid' => $paid,
            'earned' => $earned,
            'credit_note' => $receivableDelta > 0 ? $receivableDelta : 0.0,
            'debit_note' => $receivableDelta < 0 ? self::money(-$receivableDelta) : 0.0,
            'refund_to_client' => $cashDelta > 0 ? $cashDelta : 0.0,
            'to_collect' => $cashDelta < 0 ? self::money(-$cashDelta) : 0.0,
            'expected_balance' => self::money($earned - $paid),
        ];
    }

    /**
     * المستحَق فعلياً: الأيام المستهلكة (بحد أقصى مدة العقد) زائد أيام ما بعد
     * الانتهاء، كلها بالسعر اليومي المشتق من القيمة المفوترة.
     */
    public static function earned(float $invoiced, int $totalDays, int $usedDays, int $overdueDays = 0): float
    {
        $invoiced = max(0, $invoiced);
        $totalDays = max(0, $totalDays);
        $usedDays = max(0, min($usedDays, $totalDays));
        $overdueDays = max(0, $overdueDays);

        if ($totalDays <= 0) {
            return 0.0;
        }

        $dailyRate = $invoiced / $totalDays;

        return self::money(min($invoiced, $dailyRate * $usedDays) + ($dailyRate * $overdueDays));
    }

    /**
     * تقسيم أي مبلغ بنفس تركيبة الفاتورة الأصلية.
     *
     * البديل عن ratio = min(1, amount / invoiceTotal) الذي يثبّت الضريبة عند سقف
     * ضريبة الفاتورة بينما ينتفخ الوعاء الخاضع، فتخرج نسب مثل 2.2% بدل 15%.
     * القسمة هنا على نسبة الإعفاء تحفظ الـ15% لأي مبلغ مهما تجاوز الفاتورة.
     */
    public static function splitByComposition(float $amount, float $exemptFraction): array
    {
        $amount = self::money(max(0, $amount));
        $exemptFraction = max(0.0, min(1.0, $exemptFraction));

        $exempt = self::money($amount * $exemptFraction);
        $taxableGross = self::money($amount - $exempt);
        $taxable = self::money($taxableGross / (1 + self::VAT_RATE));
        $vat = self::money($taxableGross - $taxable);

        // فروق الكسور تُضبط على الوعاء الخاضع حتى يساوي المجموع المبلغ تماماً
        $drift = self::money($amount - ($exempt + $taxable + $vat));
        if (abs($drift) >= 0.01) {
            $taxable = self::money($taxable + $drift);
        }

        return [
            'salary_exempt' => $exempt,
            'taxable' => $taxable,
            'vat' => $vat,
            'total' => self::money($exempt + $taxable + $vat),
            'effective_rate' => $taxable > 0 ? round($vat / $taxable * 100, 2) : 0.0,
        ];
    }

    /**
     * تركيبة الفاتورة مشتقة من ضريبتها المرحَّلة.
     *
     * سطر الفاتورة نفسه غير موثوق: 23 من 47 فاتورة مرحَّلة تحمل في عمود
     * vat_amount قيمة تخالف قيدها المحاسبي. الضريبة في القيد دائماً 15% من
     * الوعاء الخاضع، فمنها يُشتق الباقي.
     */
    public static function compositionFromPostedVat(float $total, float $postedVat): array
    {
        $total = self::money(max(0, $total));
        $postedVat = self::money(max(0, $postedVat));

        $taxable = self::money($postedVat / self::VAT_RATE);
        $exempt = self::money(max(0, $total - $taxable - $postedVat));

        return [
            'taxable' => $taxable,
            'salary_exempt' => $exempt,
            'vat' => $postedVat,
            'exempt_fraction' => $total > 0 ? $exempt / $total : 0.0,
        ];
    }

    private static function money(float $amount): float
    {
        return round($amount, 2);
    }
}
