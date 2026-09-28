<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * توحيد صيغة الوقت الواردة من تطبيقات العملاء قبل التحقق منها.
 *
 * العقد يبقى Y-m-d H:i؛ هذا الصنف يقبل الصيغ المكافئة التي لا لبس فيها ويحوّلها إليه:
 * الفاصل T الذي تنتجه DateTime.toIso8601String في Flutter، والثواني التي ترجعها /slots
 * في starts_at، والإزاحة الزمنية الصريحة (تُحوَّل إلى توقيت التطبيق بدل قصّها).
 * أي صيغة أخرى تُترك كما وردت ليرفضها date_format برسالتها المعتادة، فلا يتوسّع العقد.
 */
class HourlyTimeInput
{
    /** تاريخ، ثم مسافة أو T، ثم HH:MM، وثوانٍ اختيارية، وإزاحة اختيارية. */
    private const PATTERN = '/^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2})(:\d{2}(?:\.\d+)?)?(Z|[+-]\d{2}:?\d{2})?$/';

    public static function normalize(mixed $value): mixed
    {
        if (!is_string($value)) return $value;
        $trimmed = trim($value);
        if (!preg_match(self::PATTERN, $trimmed, $matches)) return $value;
        // الإزاحة الصريحة تجعل القيمة لحظة مطلقة، فتُحوَّل إلى توقيت التطبيق ولا تُقصّ.
        if (!empty($matches[4])) return Carbon::parse($trimmed)->setTimezone(config('app.timezone'))->format('Y-m-d H:i');

        return $matches[1].' '.$matches[2];
    }

    /** نفس التحويل لقيمة مفردة أو لمصفوفة مواعيد مثل scheduled_starts. */
    public static function normalizeAll(mixed $value): mixed
    {
        return is_array($value) ? array_map([self::class, 'normalize'], $value) : self::normalize($value);
    }
}
