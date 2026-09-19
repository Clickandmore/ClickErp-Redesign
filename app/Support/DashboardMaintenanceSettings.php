<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardMaintenanceSettings
{
    private const SETTINGS_KEY = 'dashboard_maintenance';

    private const CACHE_KEY = 'superadmin.dashboard_maintenance';

    private const DEFAULTS = [
        'enabled' => false,
        'message' => 'نعمل حاليًا على تحديث النظام وتحسين خدماته. نعتذر عن التوقف المؤقت، وسيعود النظام للعمل قريبًا.',
    ];

    public static function all(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(5), function () {
            $settings = self::DEFAULTS;

            if (Schema::hasTable('dev_superadmin_settings')) {
                $raw = DB::table('dev_superadmin_settings')
                    ->where('key', self::SETTINGS_KEY)
                    ->value('value');
                $stored = is_string($raw) ? json_decode($raw, true) : null;

                if (is_array($stored)) {
                    $settings = array_replace($settings, $stored);
                }
            }

            return [
                'enabled' => filter_var($settings['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'message' => trim((string) ($settings['message'] ?? self::DEFAULTS['message'])),
            ];
        });
    }

    public static function enabled(): bool
    {
        return self::all()['enabled'];
    }

    public static function persist(array $payload): void
    {
        if (! Schema::hasTable('dev_superadmin_settings')) {
            return;
        }

        $settings = [
            'enabled' => (bool) ($payload['enabled'] ?? false),
            'message' => trim((string) ($payload['message'] ?? self::DEFAULTS['message'])),
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
}
