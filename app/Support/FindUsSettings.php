<?php

namespace App\Support;

use App\Models\ingaz\Setting;
use Illuminate\Support\Facades\Schema;

/**
 * وضع عرض خريطة «أين تجدنا» في الموقع الخارجي:
 *
 * - single   : رابط خريطة واحد يؤخذ من إعدادات الموقع (map_link) — وهو الوضع الافتراضي
 *              لكل النسخ المرتبطة بالداشبورد حتى لا يتغير سلوكها.
 * - branches : لكل فرع رابط خريطة مستقل يُدار من شاشة «أين تجدنا».
 */
class FindUsSettings
{
    public const MODE_SINGLE = 'single';

    public const MODE_BRANCHES = 'branches';

    public static function modes(): array
    {
        return [self::MODE_SINGLE, self::MODE_BRANCHES];
    }

    public static function mode(): string
    {
        try {
            if (!self::columnAvailable('find_us_mode')) {
                return self::MODE_SINGLE;
            }

            $mode = Setting::query()->value('find_us_mode');
        } catch (\Throwable) {
            return self::MODE_SINGLE;
        }

        return $mode === self::MODE_BRANCHES
            ? self::MODE_BRANCHES
            : self::MODE_SINGLE;
    }

    public static function usesBranches(): bool
    {
        return self::mode() === self::MODE_BRANCHES;
    }

    public static function usesSingleLink(): bool
    {
        return self::mode() === self::MODE_SINGLE;
    }

    /**
     * رابط الخريطة الموحّد المستخدم في وضع الرابط الواحد.
     */
    public static function singleLink(): string
    {
        try {
            if (!self::columnAvailable('map_link')) {
                return '';
            }

            return trim((string) (Setting::query()->value('map_link') ?? ''));
        } catch (\Throwable) {
            return '';
        }
    }

    public static function setMode(string $mode): string
    {
        $mode = in_array($mode, self::modes(), true) ? $mode : self::MODE_SINGLE;

        if (!self::columnAvailable('find_us_mode')) {
            return $mode;
        }

        $setting = Setting::query()->first() ?: Setting::create();
        $setting->forceFill(['find_us_mode' => $mode])->save();

        return $mode;
    }

    private static function columnAvailable(string $column): bool
    {
        return Schema::hasTable('settings') && Schema::hasColumn('settings', $column);
    }
}
