<?php

namespace App\Support;

use App\Models\ingaz\Setting;
use Illuminate\Support\Facades\Schema;

/**
 * إعدادات الكاش في المواقع الخارجية المربوطة بهذه الداشبورد.
 *
 * المواقع تقرأ هذه القيم من `GET /api/client/site-cache-settings` كل دقيقة تقريبًا،
 * وتستخدم قيمها الافتراضية الخاصة (المطابقة لـ DEFAULTS هنا) عندما لا تكون الداشبورد
 * محدّثة بعد. تُخزَّن كصف JSON واحد في جدول settings (key/value) فلا تحتاج ترحيلًا.
 *
 * `version` لا يُعدَّل من النموذج: زر "مسح كاش المواقع" يزيده، والمواقع تُدخله في مفاتيح
 * الكاش فتتجاهل كل ما خُزّن قبله.
 */
class ExternalSiteCacheSettings
{
    public const STORAGE_KEY = 'external_site_cache';
    public const GROUP = 'external_site_cache';

    public const DEFAULTS = [
        'enabled' => true,
        'settings_ttl_minutes' => 5,
        'home_ttl_minutes' => 2,
        'workers_ttl_minutes' => 2,
        'content_ttl_minutes' => 5,
        'stale_fallback_enabled' => true,
        'stale_ttl_hours' => 24,
        'failure_backoff_seconds' => 30,
        'request_timeout_seconds' => 8,
        'connect_timeout_seconds' => 3,
        'version' => 1,
        'flushed_at' => null,
    ];

    /** الحدود المسموحة لكل حقل رقمي: [min, max]. */
    public const RANGES = [
        'settings_ttl_minutes' => [1, 1440],
        // روابط الصور في الرئيسية والسير موقّعة لمدة 30 دقيقة، فالكاش أطول من ذلك يكسرها.
        'home_ttl_minutes' => [1, 20],
        'workers_ttl_minutes' => [1, 20],
        'content_ttl_minutes' => [1, 1440],
        'stale_ttl_hours' => [1, 168],
        'failure_backoff_seconds' => [0, 600],
        'request_timeout_seconds' => [2, 30],
        'connect_timeout_seconds' => [1, 10],
    ];

    /** كم ثانية يحتفظ الموقع بهذه الإعدادات قبل أن يطلبها من جديد. */
    public const SITE_REFRESH_SECONDS = 60;

    public static function all(): array
    {
        $stored = self::stored();
        $settings = self::DEFAULTS;

        foreach (['enabled', 'stale_fallback_enabled'] as $key) {
            if (array_key_exists($key, $stored)) {
                $settings[$key] = filter_var($stored[$key], FILTER_VALIDATE_BOOL);
            }
        }

        foreach (self::RANGES as $key => [$min, $max]) {
            if (array_key_exists($key, $stored) && is_numeric($stored[$key])) {
                $settings[$key] = min(max((int) $stored[$key], $min), $max);
            }
        }

        $settings['version'] = max(1, (int) ($stored['version'] ?? 1));
        $settings['flushed_at'] = $stored['flushed_at'] ?? null;

        return $settings;
    }

    /**
     * يحفظ قيم النموذج مع الإبقاء على version/flushed_at كما هي.
     */
    public static function save(array $input): void
    {
        $current = self::all();
        $values = [
            'enabled' => (bool) ($input['enabled'] ?? false),
            'stale_fallback_enabled' => (bool) ($input['stale_fallback_enabled'] ?? false),
            'version' => $current['version'],
            'flushed_at' => $current['flushed_at'],
        ];

        foreach (self::RANGES as $key => [$min, $max]) {
            $values[$key] = min(max((int) ($input[$key] ?? $current[$key]), $min), $max);
        }

        self::persist($values);
    }

    /**
     * يُبطل كل الكاش المخزّن في المواقع. يظهر أثره خلال SITE_REFRESH_SECONDS.
     */
    public static function flushSites(): int
    {
        $values = self::all();
        $values['version']++;
        $values['flushed_at'] = now()->toDateTimeString();

        self::persist($values);

        return $values['version'];
    }

    /**
     * الشكل الذي تستهلكه المواقع. كل المدد بالثواني.
     */
    public static function apiPayload(): array
    {
        $settings = self::all();

        return [
            'enabled' => $settings['enabled'],
            'version' => $settings['version'],
            'ttl' => [
                'settings' => $settings['settings_ttl_minutes'] * 60,
                'home' => $settings['home_ttl_minutes'] * 60,
                'workers' => $settings['workers_ttl_minutes'] * 60,
                'content' => $settings['content_ttl_minutes'] * 60,
            ],
            'stale' => [
                'enabled' => $settings['stale_fallback_enabled'],
                'ttl' => $settings['stale_ttl_hours'] * 3600,
            ],
            'failure_backoff' => $settings['failure_backoff_seconds'],
            'timeout' => $settings['request_timeout_seconds'],
            'connect_timeout' => $settings['connect_timeout_seconds'],
            'refresh' => self::SITE_REFRESH_SECONDS,
        ];
    }

    private static function stored(): array
    {
        try {
            if (!Schema::hasTable('settings') || !Schema::hasColumn('settings', 'key')) {
                return [];
            }

            $value = Setting::getValue(self::STORAGE_KEY);
        } catch (\Throwable) {
            return [];
        }

        $decoded = is_string($value) ? json_decode($value, true) : null;

        return is_array($decoded) ? $decoded : [];
    }

    private static function persist(array $values): void
    {
        Setting::setValue(self::STORAGE_KEY, json_encode($values), self::GROUP);
    }
}
