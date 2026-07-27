<?php

namespace App\Support;

use App\Models\NewSettings;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class R2StorageSettings
{
    private const SETTINGS_KEY = 'r2_storage';
    private static ?array $cached = null;

    public static function all(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        $settings = self::defaults();

        if (!Schema::hasTable('new_settings')) {
            return self::$cached = $settings;
        }

        $raw = NewSettings::query()->where('key', self::SETTINGS_KEY)->value('value');
        $stored = is_string($raw) ? json_decode($raw, true) : null;

        if (!is_array($stored)) {
            return self::$cached = $settings;
        }

        if (str_starts_with((string) ($stored['secret_access_key'] ?? ''), 'encrypted:')) {
            try {
                $stored['secret_access_key'] = Crypt::decryptString(substr($stored['secret_access_key'], 10));
            } catch (\Throwable $exception) {
                $stored['secret_access_key'] = '';
            }
        }

        return self::$cached = array_replace($settings, $stored);
    }

    public static function persist(array $payload): array
    {
        $current = self::all();
        $settings = self::normalize(array_replace($current, $payload));

        if (trim((string) ($payload['secret_access_key'] ?? '')) === '') {
            $settings['secret_access_key'] = $current['secret_access_key'];
        }

        if (self::connectionFields($current) !== self::connectionFields($settings)) {
            $settings['verified_at'] = null;
        }

        $stored = $settings;
        if ($stored['secret_access_key'] !== '') {
            $stored['secret_access_key'] = 'encrypted:' . Crypt::encryptString($stored['secret_access_key']);
        }

        NewSettings::query()->updateOrCreate(
            ['key' => self::SETTINGS_KEY],
            ['value' => json_encode($stored, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );

        self::$cached = $settings;

        return $settings;
    }

    public static function markVerified(): void
    {
        $settings = self::all();
        $settings['verified_at'] = now()->toIso8601String();
        self::persistWithoutReset($settings);
    }

    public static function isReady(?array $settings = null): bool
    {
        $settings ??= self::all();

        return (bool) ($settings['enabled'] ?? false)
            && filled($settings['verified_at'] ?? null)
            && filled($settings['endpoint'] ?? null)
            && filled($settings['bucket'] ?? null)
            && filled($settings['access_key_id'] ?? null)
            && filled($settings['secret_access_key'] ?? null);
    }

    public static function diskConfig(?array $settings = null): array
    {
        $settings ??= self::all();

        return [
            'driver' => 's3',
            'key' => $settings['access_key_id'],
            'secret' => $settings['secret_access_key'],
            'region' => 'auto',
            'bucket' => $settings['bucket'],
            'endpoint' => rtrim($settings['endpoint'], '/'),
            'url' => rtrim($settings['public_url'], '/'),
            'use_path_style_endpoint' => (bool) $settings['use_path_style_endpoint'],
            'throw' => true,
        ];
    }

    private static function defaults(): array
    {
        return [
            'enabled' => false,
            'endpoint' => '',
            'bucket' => '',
            'access_key_id' => '',
            'secret_access_key' => '',
            'public_url' => '',
            'root_prefix' => 'clickerp',
            'use_path_style_endpoint' => false,
            'verified_at' => null,
        ];
    }

    private static function normalize(array $settings): array
    {
        $settings['enabled'] = filter_var($settings['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $settings['endpoint'] = rtrim(trim((string) ($settings['endpoint'] ?? '')), '/');
        $settings['bucket'] = trim((string) ($settings['bucket'] ?? ''));
        $settings['access_key_id'] = trim((string) ($settings['access_key_id'] ?? ''));
        $settings['secret_access_key'] = trim((string) ($settings['secret_access_key'] ?? ''));
        $settings['public_url'] = rtrim(trim((string) ($settings['public_url'] ?? '')), '/');
        $settings['root_prefix'] = trim((string) ($settings['root_prefix'] ?? ''), '/');
        $settings['use_path_style_endpoint'] = filter_var(
            $settings['use_path_style_endpoint'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        return array_replace(self::defaults(), $settings);
    }

    private static function connectionFields(array $settings): array
    {
        return [
            $settings['endpoint'] ?? '',
            $settings['bucket'] ?? '',
            $settings['access_key_id'] ?? '',
            $settings['secret_access_key'] ?? '',
            $settings['root_prefix'] ?? '',
            (bool) ($settings['use_path_style_endpoint'] ?? false),
        ];
    }

    private static function persistWithoutReset(array $settings): void
    {
        $stored = self::normalize($settings);
        if ($stored['secret_access_key'] !== '') {
            $stored['secret_access_key'] = 'encrypted:' . Crypt::encryptString($stored['secret_access_key']);
        }

        NewSettings::query()->updateOrCreate(
            ['key' => self::SETTINGS_KEY],
            ['value' => json_encode($stored, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );

        self::$cached = self::normalize($settings);
    }
}
