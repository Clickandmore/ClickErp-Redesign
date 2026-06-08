<?php

namespace App\Support;

use App\Models\Branch;
use Illuminate\Support\Facades\Schema;

class ZohoSalesIqSettings
{
    private const SECRET_KEYS = ['client_secret', 'refresh_token'];

    private const FIELD_KEYS = [
        'salesiq_base_url',
        'accounts_base_url',
        'screen_name',
        'client_id',
        'client_secret',
        'refresh_token',
        'widget_url',
        'timeout',
    ];

    private const REQUIRED_KEYS = [
        'client_id' => 'ZOHO_SALESIQ_CLIENT_ID',
        'client_secret' => 'ZOHO_SALESIQ_CLIENT_SECRET',
        'refresh_token' => 'ZOHO_SALESIQ_REFRESH_TOKEN',
        'screen_name' => 'ZOHO_SALESIQ_SCREEN_NAME',
    ];

    public static function forBranch(?Branch $branch = null): array
    {
        return self::mergeWithDefaults(self::storedForBranch($branch));
    }

    public static function missingKeys(?Branch $branch = null): array
    {
        $settings = self::forBranch($branch);
        $missing = [];

        foreach (self::REQUIRED_KEYS as $key => $label) {
            if (self::trimString($settings[$key] ?? '') === '') {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    public static function normalizeForStorage(array $payload, array $current = []): ?array
    {
        $settings = [];

        foreach (self::FIELD_KEYS as $key) {
            $value = $payload[$key] ?? null;

            if (in_array($key, self::SECRET_KEYS, true) && self::trimString($value) === '') {
                $value = $current[$key] ?? null;
            }

            if ($key === 'timeout') {
                if ($value !== null && $value !== '') {
                    $settings[$key] = max(5, (int) $value);
                }

                continue;
            }

            $value = self::trimString($value);

            if (in_array($key, ['salesiq_base_url', 'accounts_base_url'], true)) {
                $value = rtrim($value, '/');
            }

            if ($value !== '') {
                $settings[$key] = $value;
            }
        }

        return $settings === [] ? null : $settings;
    }

    public static function publicWidgetUrl(): string
    {
        $branch = self::defaultPublicBranch();
        $settings = self::forBranch($branch);

        return self::trimString($settings['widget_url'] ?? '');
    }

    public static function defaults(): array
    {
        return [
            'salesiq_base_url' => rtrim((string) config('services.zoho_salesiq.salesiq_base_url', 'https://salesiq.zoho.sa'), '/'),
            'accounts_base_url' => rtrim((string) config('services.zoho_salesiq.accounts_base_url', 'https://accounts.zoho.sa'), '/'),
            'screen_name' => self::trimString(config('services.zoho_salesiq.screen_name', '')),
            'client_id' => self::trimString(config('services.zoho_salesiq.client_id', '')),
            'client_secret' => self::trimString(config('services.zoho_salesiq.client_secret', '')),
            'refresh_token' => self::trimString(config('services.zoho_salesiq.refresh_token', '')),
            'widget_url' => self::trimString(config('services.zoho_salesiq.widget_url', '')),
            'timeout' => max(5, (int) config('services.zoho_salesiq.timeout', 20)),
        ];
    }

    private static function storedForBranch(?Branch $branch): array
    {
        if (!$branch) {
            return [];
        }

        $settings = $branch->zoho_salesiq_settings ?? [];

        return is_array($settings) ? $settings : [];
    }

    private static function mergeWithDefaults(array $stored): array
    {
        $settings = self::defaults();

        foreach (self::FIELD_KEYS as $key) {
            if (!array_key_exists($key, $stored)) {
                continue;
            }

            if ($key === 'timeout') {
                $settings[$key] = max(5, (int) $stored[$key]);
                continue;
            }

            $value = self::trimString($stored[$key]);
            if ($value !== '') {
                $settings[$key] = in_array($key, ['salesiq_base_url', 'accounts_base_url'], true)
                    ? rtrim($value, '/')
                    : $value;
            }
        }

        return $settings;
    }

    private static function defaultPublicBranch(): ?Branch
    {
        try {
            if (!Schema::hasTable('branches')) {
                return null;
            }

            return Branch::query()
                ->where('is_main', true)
                ->first()
                ?: Branch::query()->active()->ordered()->first();
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private static function trimString($value): string
    {
        return trim((string) $value);
    }
}
