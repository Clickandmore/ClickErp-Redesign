<?php

namespace App\Support;

use App\Models\NewSettings;
use Illuminate\Support\Facades\Schema;

class DatabaseBackupSettings
{
    private const SETTINGS_KEY = 'database_backup';

    private const DEFAULTS = [
        'enabled' => true,
        'daily_time' => '03:30',
        'retention_days' => 30,
        'local_enabled' => true,
        'local_retention_days' => 7,
        'path' => 'database-backups',
    ];

    private static ?array $cached = null;

    public static function all(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        if (!Schema::hasTable('new_settings')) {
            return self::$cached = self::DEFAULTS;
        }

        $raw = NewSettings::query()->where('key', self::SETTINGS_KEY)->value('value');
        $stored = is_string($raw) ? json_decode($raw, true) : null;

        return self::$cached = self::normalize(is_array($stored) ? $stored : []);
    }

    public static function persist(array $payload): array
    {
        $settings = self::normalize(array_replace(self::all(), $payload));

        NewSettings::query()->updateOrCreate(
            ['key' => self::SETTINGS_KEY],
            ['value' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );

        return self::$cached = $settings;
    }

    private static function normalize(array $settings): array
    {
        if (!array_key_exists('retention_days', $settings) && array_key_exists('retention_count', $settings)) {
            $settings['retention_days'] = $settings['retention_count'];
        }
        $settings = array_replace(self::DEFAULTS, $settings);
        $settings['enabled'] = filter_var($settings['enabled'], FILTER_VALIDATE_BOOLEAN);
        $settings['local_enabled'] = filter_var($settings['local_enabled'], FILTER_VALIDATE_BOOLEAN);
        $settings['daily_time'] = preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', (string) $settings['daily_time'])
            ? (string) $settings['daily_time']
            : self::DEFAULTS['daily_time'];
        $settings['retention_days'] = max(1, min(365, (int) $settings['retention_days']));
        $settings['local_retention_days'] = max(1, min(30, (int) $settings['local_retention_days']));
        unset($settings['retention_count']);
        $settings['path'] = trim((string) $settings['path'], '/');
        if (!preg_match('/^[A-Za-z0-9_\-\/]+$/', $settings['path'])) {
            $settings['path'] = self::DEFAULTS['path'];
        }

        return $settings;
    }
}
