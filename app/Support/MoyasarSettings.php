<?php

namespace App\Support;

use App\Models\MoyasarSetting;
use Illuminate\Support\Facades\Schema;

class MoyasarSettings
{
    public static function current(): array
    {
        if (!Schema::hasTable('moyasar_settings')) {
            return self::defaults();
        }

        $settings = MoyasarSetting::query()->latest('id')->first();

        if (!$settings) {
            return self::defaults();
        }

        return [
            'is_enabled' => (bool) $settings->is_enabled,
            'base_url' => self::trimUrl($settings->base_url ?: self::defaults()['base_url']),
            'publishable_key' => trim((string) $settings->publishable_key),
            'secret_key' => trim((string) $settings->secret_key),
            'webhook_secret' => trim((string) $settings->webhook_secret),
        ];
    }

    public static function isEnabled(): bool
    {
        $settings = self::current();

        return (bool) $settings['is_enabled']
            && $settings['base_url'] !== ''
            && $settings['publishable_key'] !== ''
            && $settings['secret_key'] !== '';
    }

    public static function persist(array $payload, ?int $adminId = null): MoyasarSetting
    {
        $current = self::current();
        $enabled = filter_var($payload['is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $data = [
            'is_enabled' => $enabled,
            'base_url' => self::trimUrl($payload['base_url'] ?? $current['base_url']),
            'publishable_key' => self::preserveSecret($payload['publishable_key'] ?? '', $current['publishable_key']),
            'secret_key' => self::preserveSecret($payload['secret_key'] ?? '', $current['secret_key']),
            'webhook_secret' => self::preserveSecret($payload['webhook_secret'] ?? '', $current['webhook_secret']),
            'updated_by' => $adminId,
        ];

        $record = MoyasarSetting::query()->latest('id')->first();

        if ($record) {
            $record->update($data);

            return $record->fresh();
        }

        $data['created_by'] = $adminId;

        return MoyasarSetting::query()->create($data);
    }

    public static function mask(?string $value, int $visible = 4): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 'غير محفوظ';
        }

        if (strlen($value) <= $visible) {
            return str_repeat('*', strlen($value));
        }

        return str_repeat('*', max(6, strlen($value) - $visible)) . substr($value, -$visible);
    }

    public static function defaults(): array
    {
        return [
            'is_enabled' => false,
            'base_url' => 'https://api.moyasar.com/v1',
            'publishable_key' => '',
            'secret_key' => '',
            'webhook_secret' => '',
        ];
    }

    private static function preserveSecret($newValue, string $currentValue): string
    {
        $trimmed = trim((string) $newValue);

        return $trimmed !== '' ? $trimmed : $currentValue;
    }

    private static function trimUrl($value): string
    {
        $url = trim((string) $value);

        return rtrim($url !== '' ? $url : self::defaults()['base_url'], '/');
    }
}
