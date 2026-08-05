<?php

namespace App\Support;

use App\Models\ingaz\Setting;
use Illuminate\Support\Facades\Schema;

/**
 * إعدادات السير الذاتية العامة — منفصلة عن إعدادات قالب الطباعة
 * في {@see MaidCvTemplateSettings}.
 */
class CvOptionsSettings
{
    public const RENTAL_TRANSFER_COLUMN = 'rental_cv_transfer_enabled';

    private static ?bool $rentalTransferEnabled = null;

    /**
     * هل يُسمح بإتاحة سيرة عاملة الإيجار لنقل الخدمات على الموقع الخارجي؟
     * عند التعطيل يُخفى حقل "الاستلام الفوري" لعاملات الإيجار في الإيواء.
     */
    public static function rentalTransferEnabled(): bool
    {
        if (self::$rentalTransferEnabled !== null) {
            return self::$rentalTransferEnabled;
        }

        if (!Schema::hasTable('settings') || !Schema::hasColumn('settings', self::RENTAL_TRANSFER_COLUMN)) {
            return self::$rentalTransferEnabled = false;
        }

        return self::$rentalTransferEnabled = (bool) Setting::query()->value(self::RENTAL_TRANSFER_COLUMN);
    }

    public static function clearCache(): void
    {
        self::$rentalTransferEnabled = null;
    }

    /**
     * تثبيت القيمة داخل الاختبارات دون الحاجة لصف إعدادات في قاعدة البيانات.
     */
    public static function fakeRentalTransferEnabled(?bool $enabled): void
    {
        self::$rentalTransferEnabled = $enabled;
    }
}
