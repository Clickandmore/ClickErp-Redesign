<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * إعدادات خادم MCP من شاشة Super Admin.
 *
 * المصدر الوحيد لإعدادات MCP؛ لا شيء من .env. القيم الثابتة في config/mcp.php
 * هي الافتراضي قبل أول حفظ من الشاشة فقط. الشرح في docs/MCP_INTEGRATION.md.
 */
class McpSettings
{
    private const SETTINGS_KEY = 'mcp_server';

    private const CACHE_KEY = 'superadmin.mcp_server';

    public const ACCESS_SELECTED = 'selected';

    public const ACCESS_ALL = 'all';

    public static function all(): array
    {
        $settings = array_replace(self::defaults(), self::stored());

        return [
            'enabled' => filter_var($settings['enabled'], FILTER_VALIDATE_BOOLEAN),
            'rate_limit_per_minute' => max(1, (int) $settings['rate_limit_per_minute']),
            'mask_sensitive' => filter_var($settings['mask_sensitive'], FILTER_VALIDATE_BOOLEAN),
            'token_days' => max(0, (int) $settings['token_days']),
            'oauth_enabled' => filter_var($settings['oauth_enabled'], FILTER_VALIDATE_BOOLEAN),
            'access_mode' => $settings['access_mode'] === self::ACCESS_ALL ? self::ACCESS_ALL : self::ACCESS_SELECTED,
            'write_enabled' => filter_var($settings['write_enabled'], FILTER_VALIDATE_BOOLEAN),
            'disabled_tools' => array_values(array_filter((array) $settings['disabled_tools'], 'is_string')),
            'audit_retention_days' => max(0, (int) $settings['audit_retention_days']),
            'write_limit_per_hour' => max(1, (int) $settings['write_limit_per_hour']),
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

    /** ربط claude.ai وChatGPT وClaude Code بتسجيل دخول الموظف (OAuth) بدل لصق توكن. */
    public static function oauthEnabled(): bool
    {
        return self::all()['oauth_enabled'];
    }

    /** selected = الموظفون المضافون في الشاشة فقط، all = أي صاحب توكن صالح. */
    public static function accessMode(): string
    {
        return self::all()['access_mode'];
    }

    public static function writeEnabled(): bool
    {
        return self::all()['write_enabled'];
    }

    public static function toolEnabled(string $name): bool
    {
        return !in_array($name, self::all()['disabled_tools'], true);
    }

    /** أقصى عدد عمليات إضافة عبر المساعد لكل موظف في الساعة. */
    public static function writeLimitPerHour(): int
    {
        return self::all()['write_limit_per_hour'];
    }

    /** 0 = بلا حذف. */
    public static function auditRetentionDays(): int
    {
        return self::all()['audit_retention_days'];
    }

    /**
     * يحفظ المفاتيح المرسلة فقط ويُبقي الباقي كما هو، لأن كل تبويب في الشاشة
     * (الإعدادات، الأدوات) يحفظ جزءاً منها.
     */
    public static function persist(array $payload): void
    {
        if (! Schema::hasTable('dev_superadmin_settings')) {
            return;
        }

        $current = array_replace(self::defaults(), self::stored());
        $normalizers = [
            'enabled' => fn ($value) => (bool) $value,
            'rate_limit_per_minute' => fn ($value) => max(1, (int) $value),
            'mask_sensitive' => fn ($value) => (bool) $value,
            'token_days' => fn ($value) => max(0, (int) $value),
            'oauth_enabled' => fn ($value) => (bool) $value,
            'access_mode' => fn ($value) => $value === self::ACCESS_ALL ? self::ACCESS_ALL : self::ACCESS_SELECTED,
            'write_enabled' => fn ($value) => (bool) $value,
            'disabled_tools' => fn ($value) => array_values(array_unique(array_filter((array) $value, 'is_string'))),
            'audit_retention_days' => fn ($value) => max(0, (int) $value),
            'write_limit_per_hour' => fn ($value) => max(1, (int) $value),
        ];

        $settings = [];
        foreach ($normalizers as $key => $normalize) {
            $settings[$key] = $normalize(array_key_exists($key, $payload) ? $payload[$key] : $current[$key]);
        }

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

    private static function stored(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(5), function () {
            if (! Schema::hasTable('dev_superadmin_settings')) {
                return [];
            }

            $raw = DB::table('dev_superadmin_settings')->where('key', self::SETTINGS_KEY)->value('value');
            $decoded = is_string($raw) ? json_decode($raw, true) : null;

            return is_array($decoded) ? $decoded : [];
        });
    }

    private static function defaults(): array
    {
        return [
            'enabled' => (bool) config('mcp.enabled', false),
            'rate_limit_per_minute' => (int) config('mcp.rate_limit_per_minute', 60),
            'mask_sensitive' => (bool) config('mcp.mask_sensitive', true),
            'token_days' => (int) config('mcp.token_days', 90),
            'oauth_enabled' => (bool) config('mcp.oauth.enabled', false),
            'access_mode' => (string) config('mcp.access_mode', self::ACCESS_SELECTED),
            'write_enabled' => (bool) config('mcp.write_enabled', false),
            'disabled_tools' => [],
            'audit_retention_days' => (int) config('mcp.audit_retention_days', 180),
            'write_limit_per_hour' => (int) config('mcp.write_limit_per_hour', 30),
        ];
    }
}
