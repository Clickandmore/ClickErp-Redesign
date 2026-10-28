<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * إعدادات خادم MCP من شاشة Super Admin.
 *
 * القيم المحفوظة تتقدم على config/mcp.php (أي .env)، والـ config يبقى الافتراضي
 * قبل أول حفظ من الشاشة. الشرح في docs/MCP_INTEGRATION.md.
 */
class McpSettings
{
    private const SETTINGS_KEY = 'mcp_server';

    private const CACHE_KEY = 'superadmin.mcp_server';

    public static function all(): array
    {
        $stored = Cache::remember(self::CACHE_KEY, now()->addMinutes(5), function () {
            if (! Schema::hasTable('dev_superadmin_settings')) {
                return [];
            }

            $raw = DB::table('dev_superadmin_settings')->where('key', self::SETTINGS_KEY)->value('value');
            $decoded = is_string($raw) ? json_decode($raw, true) : null;

            return is_array($decoded) ? $decoded : [];
        });

        $settings = array_replace(self::defaults(), $stored);

        return [
            'enabled' => filter_var($settings['enabled'], FILTER_VALIDATE_BOOLEAN),
            'rate_limit_per_minute' => max(1, (int) $settings['rate_limit_per_minute']),
            'mask_sensitive' => filter_var($settings['mask_sensitive'], FILTER_VALIDATE_BOOLEAN),
            'token_days' => max(0, (int) $settings['token_days']),
        ];
    }

    public static function enabled(): bool
    {
        return self::all()['enabled'];
    }

    public static function rateLimitPerMinute(): int
    {
        return self::all()['rate_limit_per_minute'];
    }

    public static function maskSensitive(): bool
    {
        return self::all()['mask_sensitive'];
    }

    /** 0 = بلا انتهاء. */
    public static function tokenDays(): int
    {
        return self::all()['token_days'];
    }

    public static function persist(array $payload): void
    {
        if (! Schema::hasTable('dev_superadmin_settings')) {
            return;
        }

        $settings = [
            'enabled' => (bool) ($payload['enabled'] ?? false),
            'rate_limit_per_minute' => max(1, (int) ($payload['rate_limit_per_minute'] ?? 60)),
            'mask_sensitive' => (bool) ($payload['mask_sensitive'] ?? false),
            'token_days' => max(0, (int) ($payload['token_days'] ?? 90)),
        ];

        \App\Support\Audit\AuditedSettings::updateOrInsert('dev_superadmin_settings',
            ['key' => self::SETTINGS_KEY],
            [
                'value' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        Cache::forget(self::CACHE_KEY);
    }

    private static function defaults(): array
    {
        return [
            'enabled' => (bool) config('mcp.enabled', false),
            'rate_limit_per_minute' => (int) config('mcp.rate_limit_per_minute', 60),
            'mask_sensitive' => (bool) config('mcp.mask_sensitive', true),
            'token_days' => (int) config('mcp.token_days', 90),
        ];
    }
}
