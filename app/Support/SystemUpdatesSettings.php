<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * إعدادات «تحديثات النظام»: إظهار الشريط للموظفين، ومصدر التحديثات الذي تسحب منه
 * هذه النسخة (Base URL + مفتاح).
 *
 * حالة المزامنة (آخر مزامنة، المؤشر، آخر خطأ) تُحفظ في مفتاح منفصل خارج
 * AuditedSettings حتى لا تملأ كل مزامنة سجل النشاط.
 */
class SystemUpdatesSettings
{
    private const SETTINGS_KEY = 'system_updates';

    private const STATE_KEY = 'system_updates_sync_state';

    private const CACHE_KEY = 'superadmin.system_updates';

    private const DEFAULTS = [
        'banner_enabled' => true,
        'banner_days' => 30,
        'sync_enabled' => false,
        'source_url' => '',
        'source_key' => '',
        'sync_interval_minutes' => 1,
    ];

    /** خيارات مدة السحب بالدقائق؛ أقل من ساعة يعني أن التحديث يصل للعميل تقريبًا فور نشره. */
    public const INTERVAL_OPTIONS = [1, 5, 15, 30, 60, 360, 1440];

    private const STATE_DEFAULTS = [
        'cursor' => null,
        'last_attempt_at' => null,
        'last_synced_at' => null,
        'last_error' => null,
        'last_received' => 0,
    ];

    public static function all(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(5), function () {
            $settings = array_replace(self::DEFAULTS, self::read(self::SETTINGS_KEY));

            return [
                'banner_enabled' => filter_var($settings['banner_enabled'], FILTER_VALIDATE_BOOLEAN),
                'banner_days' => max(1, min(365, (int) $settings['banner_days'])),
                'sync_enabled' => filter_var($settings['sync_enabled'], FILTER_VALIDATE_BOOLEAN),
                'source_url' => self::normalizeBaseUrl((string) $settings['source_url']),
                'has_source_key' => filled($settings['source_key']),
                'sync_interval_minutes' => self::intervalFrom($settings),
            ];
        });
    }

    public static function bannerEnabled(): bool
    {
        return self::all()['banner_enabled'];
    }

    public static function bannerDays(): int
    {
        return self::all()['banner_days'];
    }

    public static function syncIntervalMinutes(): int
    {
        return self::all()['sync_interval_minutes'];
    }

    /** تعبير cron لمدة السحب: كل N دقيقة تحت الساعة، وكل N ساعة فوقها. */
    public static function syncCronExpression(): string
    {
        $minutes = self::syncIntervalMinutes();

        return $minutes < 60
            ? AutomationSettings::minutesCronExpression($minutes)
            : AutomationSettings::hoursCronExpression(intdiv($minutes, 60));
    }

    public static function sourceKey(): ?string
    {
        $encrypted = (string) (self::read(self::SETTINGS_KEY)['source_key'] ?? '');
        if ($encrypted === '') {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable $exception) {
            // APP_KEY تغيّر بعد الحفظ: نعامل المفتاح كغير موجود حتى يُعاد إدخاله
            return null;
        }
    }

    public static function syncConfigured(): bool
    {
        $settings = self::all();

        return $settings['sync_enabled'] && $settings['source_url'] !== '' && self::sourceKey() !== null;
    }

    /**
     * @param array $payload source_key الفارغ يعني «أبقِ المفتاح الحالي»
     */
    public static function persist(array $payload): void
    {
        if (! Schema::hasTable('dev_superadmin_settings')) {
            return;
        }

        $current = array_replace(self::DEFAULTS, self::read(self::SETTINGS_KEY));
        $sourceUrl = self::normalizeBaseUrl((string) ($payload['source_url'] ?? ''));
        $newKey = trim((string) ($payload['source_key'] ?? ''));

        $settings = [
            'banner_enabled' => (bool) ($payload['banner_enabled'] ?? false),
            'banner_days' => max(1, min(365, (int) ($payload['banner_days'] ?? self::DEFAULTS['banner_days']))),
            'sync_enabled' => (bool) ($payload['sync_enabled'] ?? false),
            'source_url' => $sourceUrl,
            'source_key' => $newKey !== '' ? Crypt::encryptString($newKey) : (string) $current['source_key'],
            'sync_interval_minutes' => self::intervalFrom($payload),
        ];

        if (! empty($payload['clear_source_key'])) {
            $settings['source_key'] = '';
        }

        \App\Support\Audit\AuditedSettings::updateOrInsert('dev_superadmin_settings',
            ['key' => self::SETTINGS_KEY],
            [
                'value' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // مصدر جديد = سحب كامل من البداية
        if ($sourceUrl !== self::normalizeBaseUrl((string) $current['source_url'])) {
            self::recordState(['cursor' => null]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    public static function state(): array
    {
        return array_replace(self::STATE_DEFAULTS, self::read(self::STATE_KEY));
    }

    public static function recordState(array $changes): void
    {
        if (! Schema::hasTable('dev_superadmin_settings')) {
            return;
        }

        DB::table('dev_superadmin_settings')->updateOrInsert(
            ['key' => self::STATE_KEY],
            [
                'value' => json_encode(array_replace(self::state(), $changes), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /** يقبل الإعداد القديم sync_interval_hours ويحوّل أي قيمة لأقرب خيار مسموح. */
    private static function intervalFrom(array $values): int
    {
        $minutes = isset($values['sync_interval_minutes'])
            ? (int) $values['sync_interval_minutes']
            : (isset($values['sync_interval_hours']) ? (int) $values['sync_interval_hours'] * 60 : self::DEFAULTS['sync_interval_minutes']);

        foreach (self::INTERVAL_OPTIONS as $option) {
            if ($minutes <= $option) {
                return $option;
            }
        }

        return self::INTERVAL_OPTIONS[count(self::INTERVAL_OPTIONS) - 1];
    }

    public static function normalizeBaseUrl(string $url): string
    {
        $url = rtrim(trim($url), '/');

        // يقبل من يلصق رابط الـ API كاملًا بدل الدومين
        return preg_replace('#/api/system-updates/feed$#i', '', $url) ?? $url;
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private static function read(string $key): array
    {
        if (! Schema::hasTable('dev_superadmin_settings')) {
            return [];
        }

        $raw = DB::table('dev_superadmin_settings')->where('key', $key)->value('value');
        $decoded = is_string($raw) ? json_decode($raw, true) : null;

        return is_array($decoded) ? $decoded : [];
    }
}
