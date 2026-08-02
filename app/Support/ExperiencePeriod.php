<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * حساب وعرض مدة الخبرة السابقة للعاملة.
 *
 * الإدخال في الواجهة بدقة شهر/سنة (<input type="month"> يرسل "YYYY-MM")،
 * ويُخزّن في قاعدة البيانات كتاريخ على أول يوم في الشهر.
 */
class ExperiencePeriod
{
    /**
     * تحويل "2018-03" أو "2018-03-17" إلى "2018-03-01".
     */
    public static function normalize($value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($value)->startOfMonth()->toDateString();
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{1,2})$/', $value, $matches) === 1) {
            $value .= '-01';
        }

        try {
            return CarbonImmutable::parse($value)->startOfMonth()->toDateString();
        } catch (Throwable $exception) {
            return null;
        }
    }

    /**
     * صيغة "YYYY-MM" المطلوبة لخانة <input type="month">.
     */
    public static function toMonthInput($value): ?string
    {
        $normalized = self::normalize($value);

        return $normalized ? substr($normalized, 0, 7) : null;
    }

    /**
     * عدد الأشهر بين البداية والنهاية. يرجع null إذا نقص أحد الطرفين أو كان الترتيب معكوسًا.
     */
    public static function months($start, $end): ?int
    {
        $start = self::normalize($start);
        $end = self::normalize($end);

        if (! $start || ! $end) {
            return null;
        }

        $startDate = CarbonImmutable::parse($start);
        $endDate = CarbonImmutable::parse($end);

        if ($endDate->lessThan($startDate)) {
            return null;
        }

        return (int) $startDate->diffInMonths($endDate);
    }

    /**
     * المدة بالسنوات العشرية — نفس الصيغة المخزّنة في عمود number_of_years.
     */
    public static function years($start, $end): ?string
    {
        $months = self::months($start, $end);

        return $months === null ? null : number_format($months / 12, 2, '.', '');
    }

    /**
     * "4 سنوات و 5 أشهر" — بدلًا من إظهار المدة بالأشهر فقط.
     */
    public static function durationLabel(?int $months, ?string $locale = null): ?string
    {
        if ($months === null || $months < 0) {
            return null;
        }

        $locale = $locale ?: app()->getLocale();
        $years = intdiv($months, 12);
        $remainingMonths = $months % 12;

        $parts = array_filter([
            $years > 0 ? self::countLabel($years, 'year', $locale) : null,
            $remainingMonths > 0 ? self::countLabel($remainingMonths, 'month', $locale) : null,
        ]);

        if ($parts === []) {
            return $locale === 'ar' ? 'أقل من شهر' : 'less than a month';
        }

        return implode($locale === 'ar' ? ' و ' : ' and ', $parts);
    }

    /**
     * نفس ما سبق لكن انطلاقًا من التاريخين مباشرة.
     */
    public static function durationLabelFor($start, $end, ?string $locale = null): ?string
    {
        return self::durationLabel(self::months($start, $end), $locale);
    }

    /**
     * "2018م – 2022م" لعرض سنوات الخبرة بشكل احترافي.
     */
    public static function periodLabel($start, $end, ?string $locale = null): ?string
    {
        $start = self::normalize($start);
        $end = self::normalize($end);

        if (! $start && ! $end) {
            return null;
        }

        $locale = $locale ?: app()->getLocale();
        $suffix = $locale === 'ar' ? 'م' : '';

        $startYear = $start ? substr($start, 0, 4) . $suffix : null;
        $endYear = $end ? substr($end, 0, 4) . $suffix : null;

        if ($startYear && ! $endYear) {
            return $startYear . ' – ' . ($locale === 'ar' ? 'حتى الآن' : 'present');
        }

        if (! $startYear) {
            return $endYear;
        }

        return $startYear === $endYear ? $startYear : $startYear . ' – ' . $endYear;
    }

    /**
     * تصريف عربي سليم للعدد: سنة واحدة / سنتان / 3 سنوات / 11 سنة.
     */
    private static function countLabel(int $count, string $unit, string $locale): string
    {
        if ($locale !== 'ar') {
            $word = $unit === 'year' ? 'year' : 'month';

            return $count . ' ' . $word . ($count === 1 ? '' : 's');
        }

        $forms = $unit === 'year'
            ? ['سنة واحدة', 'سنتان', 'سنوات', 'سنة']
            : ['شهر واحد', 'شهران', 'أشهر', 'شهرًا'];

        if ($count === 1) {
            return $forms[0];
        }

        if ($count === 2) {
            return $forms[1];
        }

        if ($count >= 3 && $count <= 10) {
            return $count . ' ' . $forms[2];
        }

        return $count . ' ' . $forms[3];
    }
}
