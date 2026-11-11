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
    public const SITE_SORT_ORDER_COLUMN = 'cv_site_sort_order';
    public const SITE_SORT_ORDERS = ['random', 'oldest', 'newest'];

    public static function siteSortOrder(): string
    {
        if (!Schema::hasColumn('settings', self::SITE_SORT_ORDER_COLUMN)) {
            return 'random';
        }

        $order = Setting::query()->without('translations')->value(self::SITE_SORT_ORDER_COLUMN);

        return in_array($order, self::SITE_SORT_ORDERS, true) ? $order : 'random';
    }

    public const RENTAL_TRANSFER_COLUMN = 'rental_cv_transfer_enabled';
    public const MAID_TRANSACTION_EDIT_OVERRIDE_COLUMN = 'maid_transaction_edit_override_enabled';

    public const DISPLAY_SOURCE_COLUMN = 'cv_display_source';
    public const DISPLAY_GENERATED = 'generated';
    public const DISPLAY_UPLOADED = 'uploaded';
    public const DISPLAY_SOURCES = [self::DISPLAY_GENERATED, self::DISPLAY_UPLOADED];

    private static ?string $displaySource = null;
    private static ?bool $rentalTransferEnabled = null;
    private static ?bool $maidTransactionEditOverrideEnabled = null;

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

    /**
     * هل يُسمح بتجاوز قفل بيانات السيرة المرتبطة بمعاملات مالية أو إرسالية مكتب خارجي؟
     */
    public static function maidTransactionEditOverrideEnabled(): bool
    {
        if (self::$maidTransactionEditOverrideEnabled !== null) {
            return self::$maidTransactionEditOverrideEnabled;
        }

        if (
            !Schema::hasTable('settings')
            || !Schema::hasColumn('settings', self::MAID_TRANSACTION_EDIT_OVERRIDE_COLUMN)
        ) {
            return self::$maidTransactionEditOverrideEnabled = false;
        }

        return self::$maidTransactionEditOverrideEnabled = (bool) Setting::query()
            ->value(self::MAID_TRANSACTION_EDIT_OVERRIDE_COLUMN);
    }

    /**
     * السيرة التي تُعرض للعميل في الموقع الخارجي والـ API:
     *  - generated: السيرة المولّدة من القالب (الافتراضي)
     *  - uploaded : ملف السيرة الذي رفعته الشركة (maid_cv_file)، ثم المولّدة إن لم يُرفع ملف
     */
    public static function displaySource(): string
    {
        if (self::$displaySource !== null) {
            return self::$displaySource;
        }

        if (!Schema::hasTable('settings') || !Schema::hasColumn('settings', self::DISPLAY_SOURCE_COLUMN)) {
            return self::$displaySource = self::DISPLAY_GENERATED;
        }

        $source = Setting::query()->without('translations')->value(self::DISPLAY_SOURCE_COLUMN);

        return self::$displaySource = in_array($source, self::DISPLAY_SOURCES, true) ? $source : self::DISPLAY_GENERATED;
    }

    public static function displaysUploadedCv(): bool
    {
        return self::displaySource() === self::DISPLAY_UPLOADED;
    }

    public static function fakeDisplaySource(?string $source): void
    {
        self::$displaySource = $source;
    }

    public static function clearCache(): void
    {
        self::$displaySource = null;
        self::$rentalTransferEnabled = null;
        self::$maidTransactionEditOverrideEnabled = null;
    }

    /**
     * تثبيت القيمة داخل الاختبارات دون الحاجة لصف إعدادات في قاعدة البيانات.
     */
    public static function fakeRentalTransferEnabled(?bool $enabled): void
    {
        self::$rentalTransferEnabled = $enabled;
    }

    public static function fakeMaidTransactionEditOverrideEnabled(?bool $enabled): void
    {
        self::$maidTransactionEditOverrideEnabled = $enabled;
    }
}
