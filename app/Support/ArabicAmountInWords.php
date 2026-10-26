<?php

namespace App\Support;

/**
 * تفقيط المبالغ بالعربية (ريال / هللة) دون الاعتماد على امتداد intl.
 */
class ArabicAmountInWords
{
    private const ONES = ['', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة', 'ستة', 'سبعة', 'ثمانية', 'تسعة', 'عشرة',
        'أحد عشر', 'اثنا عشر', 'ثلاثة عشر', 'أربعة عشر', 'خمسة عشر', 'ستة عشر', 'سبعة عشر', 'ثمانية عشر', 'تسعة عشر'];
    private const TENS = ['', '', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون', 'ستون', 'سبعون', 'ثمانون', 'تسعون'];
    private const HUNDREDS = ['', 'مائة', 'مائتان', 'ثلاثمائة', 'أربعمائة', 'خمسمائة', 'ستمائة', 'سبعمائة', 'ثمانمائة', 'تسعمائة'];
    /** [مفرد، مثنى، جمع (3-10)] لكل مرتبة */
    private const SCALES = [1 => ['ألف', 'ألفان', 'آلاف'], 2 => ['مليون', 'مليونان', 'ملايين'], 3 => ['مليار', 'ملياران', 'مليارات']];

    public static function sar(float $amount): string
    {
        $amount = round(abs($amount), 2);
        $riyals = (int) floor($amount);
        $halalas = (int) round(($amount - $riyals) * 100);
        if ($halalas === 100) { $riyals++; $halalas = 0; }

        $text = ($riyals > 0 ? self::number($riyals) : 'صفر').' ريال سعودي';
        if ($halalas > 0) $text .= ' و'.self::number($halalas).' هللة';

        return 'فقط '.$text.' لا غير';
    }

    public static function number(int $n): string
    {
        if ($n === 0) return 'صفر';
        $parts = [];
        $scale = 0;
        while ($n > 0) {
            $group = $n % 1000;
            if ($group > 0) $parts[] = self::scaled($group, $scale);
            $n = intdiv($n, 1000);
            $scale++;
        }

        return implode(' و', array_reverse($parts));
    }

    private static function scaled(int $group, int $scale): string
    {
        if ($scale === 0) return self::belowThousand($group);
        [$one, $two, $plural] = self::SCALES[$scale] ?? self::SCALES[3];
        if ($group === 1) return $one;
        if ($group === 2) return $two;
        if ($group <= 10) return self::belowThousand($group).' '.$plural;

        return self::belowThousand($group).' '.$one;
    }

    private static function belowThousand(int $n): string
    {
        $words = [];
        if ($n >= 100) { $words[] = self::HUNDREDS[intdiv($n, 100)]; $n %= 100; }
        if ($n > 0 && $n < 20) $words[] = self::ONES[$n];
        elseif ($n >= 20) $words[] = ($n % 10 ? self::ONES[$n % 10].' و' : '').self::TENS[intdiv($n, 10)];

        return implode(' و', $words);
    }
}
