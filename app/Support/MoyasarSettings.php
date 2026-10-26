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
            'company_name' => trim((string) ($settings->company_name ?? '')),
            'payment_description' => trim((string) ($settings->payment_description ?? '')),
            'reports_enabled' => (bool) ($settings->reports_enabled ?? true),
        ];
    }

    /** إظهار تقارير مدفوعات ميسر في موديول المالية؛ يُتحكم به من لوحة Super Admin. */
    public static function reportsEnabled(): bool
    {
        return (bool) (self::current()['reports_enabled'] ?? true);
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
        // الأعمدة تُضاف بترحيل لاحق؛ لا نكتبها قبل وجودها.
        if (Schema::hasColumn('moyasar_settings', 'company_name')) {
            $data['company_name'] = trim((string) ($payload['company_name'] ?? $current['company_name']));
            $data['payment_description'] = trim((string) ($payload['payment_description'] ?? $current['payment_description']));
        }
        if (Schema::hasColumn('moyasar_settings', 'reports_enabled')) {
            $data['reports_enabled'] = array_key_exists('reports_enabled', $payload)
                ? filter_var($payload['reports_enabled'], FILTER_VALIDATE_BOOLEAN)
                : $current['reports_enabled'];
        }

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

    /** وصف فاتورة ميسر؛ بلا نص مضبوط يبقى الوصف الأصلي كما هو. */
    public static function invoiceDescription(string $description): string
    {
        $settings = self::current();

        return implode(' — ', array_filter([
            $settings['company_name'] ?? '', $settings['payment_description'] ?? '', $description,
        ], fn ($value) => $value !== ''));
    }

    public static function containsMaskCharacters(?string $value): bool
    {
        return str_contains(trim((string) $value), '*');
    }

    public static function defaults(): array
    {
        return [
            'is_enabled' => false,
            'base_url' => 'https://api.moyasar.com/v1',
            'publishable_key' => '',
            'secret_key' => '',
            'webhook_secret' => '',
            'company_name' => '',
            'payment_description' => '',
            'reports_enabled' => true,
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
