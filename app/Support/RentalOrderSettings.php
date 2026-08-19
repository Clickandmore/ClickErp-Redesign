<?php

namespace App\Support;

use App\Models\ingaz\Setting;
use Illuminate\Support\Facades\Schema;

/**
 * وضع طلب الإيجار على الموقع الخارجي.
 *
 * full        : التسلسل الحالي — اختيار الباقة ثم العنوان ثم تأكيد الطلب.
 * simplified  : تأكيد الطلب مباشرة بعد إدخال الاسم ورقم الجوال.
 */
class RentalOrderSettings
{
    public const MODE_FULL = 'full';
    public const MODE_SIMPLIFIED = 'simplified';

    public static function mode(): string
    {
        try {
            if (!Schema::hasTable('settings') || !Schema::hasColumn('settings', 'rental_order_mode')) {
                return self::MODE_FULL;
            }

            $mode = Setting::query()->value('rental_order_mode');
        } catch (\Throwable) {
            return self::MODE_FULL;
        }

        return $mode === self::MODE_SIMPLIFIED
            ? self::MODE_SIMPLIFIED
            : self::MODE_FULL;
    }

    public static function usesSimplifiedOrdering(): bool
    {
        return self::mode() === self::MODE_SIMPLIFIED;
    }
}
