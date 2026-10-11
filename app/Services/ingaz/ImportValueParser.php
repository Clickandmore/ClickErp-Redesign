<?php

namespace App\Services\ingaz;

use Carbon\Carbon;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

final class ImportValueParser
{
    public static function isMarkerRow(array $row): bool
    {
        $values = array_filter(array_diff_key($row, array_flip(['source_sheet', 'source_row', '__source_file', '__source_row'])));
        return $values !== [] && count(array_filter($values, fn ($value) => !is_scalar($value) || !in_array(trim((string) $value), ['مطلوب', 'اختياري'], true))) === 0;
    }

    public static function digits($value): string
    {
        return strtr(trim((string) $value), array_combine(
            preg_split('//u', '٠١٢٣٤٥٦٧٨٩۰۱۲۳۴۵۶۷۸۹', -1, PREG_SPLIT_NO_EMPTY),
            str_split('01234567890123456789')
        ));
    }

    public static function decimal($value): ?float
    {
        if ($value === null || trim((string) $value) === '') return null;
        $number = str_replace(['٬', '٫'], [',', '.'], self::digits($value));
        if (!preg_match('/^[+-]?(?:\d+|\d{1,3}(?:,\d{3})+)(?:\.\d+)?$/D', $number)) {
            throw new InvalidArgumentException('قيمة رقمية غير صحيحة: ' . (string) $value);
        }
        $result = (float) str_replace(',', '', $number);
        if (!is_finite($result)) throw new InvalidArgumentException('القيمة الرقمية خارج النطاق.');
        return $result;
    }

    public static function date($value, bool $withTime = false): ?Carbon
    {
        if ($value === null || trim((string) $value) === '') return null;
        $value = self::digits($value);
        if (is_numeric($value) && !preg_match('/^(19|20)\d{6}$/D', $value)) {
            if ((float) $value < 1 || (float) $value > 2958465) {
                throw new InvalidArgumentException('تاريخ Excel خارج النطاق.');
            }
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
        }
        $formats = ['Ymd', 'Y-m-d', 'Y/m/d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'm/d/Y', 'm-d-Y',
            'Y-n-j', 'Y/n/j', 'j/n/Y', 'j-n-Y', 'n/j/Y', 'n-j-Y'];
        if ($withTime) {
            $formats = array_merge(array_map(fn ($f) => $f . ' H:i:s', $formats),
                array_map(fn ($f) => $f . ' H:i', $formats),
                array_map(fn ($f) => $f . ' g:i A', $formats), $formats);
        }
        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat('!' . $format, $value);
                // Round-trip checking rejects PHP's silent overflow (31 February, etc.).
                if ($date && $date->format($format) === $value) return $date;
            } catch (\Throwable) {
            }
        }
        throw new InvalidArgumentException('تاريخ غير صحيح؛ استخدم YYYY-MM-DD: ' . $value);
    }
}
