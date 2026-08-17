<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductUpdatesSettings
{
    private const SETTINGS_KEY = 'product_updates';

    private const DEFAULTS = [
        'is_enabled' => true,
        'title' => 'جديد في كليك',
        'summary' => 'تحسينات جديدة أصبحت متاحة الآن',
        'published_at' => null,
        'items' => [
            'أصبح بإمكانك إنشاء إشعار دائن مباشرة من الفواتير في الاستقدام والتفاويض.',
        ],
    ];

    public static function all(): array
    {
        $settings = self::DEFAULTS;
        $version = 'default';

        if (Schema::hasTable('dev_superadmin_settings')) {
            $row = DB::table('dev_superadmin_settings')
                ->where('key', self::SETTINGS_KEY)
                ->first(['value', 'updated_at']);

            $stored = $row && is_string($row->value) ? json_decode($row->value, true) : null;
            if (is_array($stored)) {
                $settings = array_replace($settings, $stored);
            }

            if ($row?->value) {
                $version = sha1((string) $row->value);
            }
        }

        $settings['is_enabled'] = filter_var($settings['is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $settings['title'] = trim((string) ($settings['title'] ?? ''));
        $settings['summary'] = trim((string) ($settings['summary'] ?? ''));
        $settings['published_at'] = filled($settings['published_at'] ?? null)
            ? (string) $settings['published_at']
            : null;
        $settings['items'] = collect($settings['items'] ?? [])
            ->filter(fn ($item) => is_string($item) && trim($item) !== '')
            ->map(fn ($item) => trim($item))
            ->values()
            ->all();
        $settings['version'] = (string) $version;

        return $settings;
    }

    public static function persist(array $payload): void
    {
        if (! Schema::hasTable('dev_superadmin_settings')) {
            return;
        }

        $settings = [
            'is_enabled' => (bool) ($payload['is_enabled'] ?? false),
            'title' => trim((string) ($payload['title'] ?? '')),
            'summary' => trim((string) ($payload['summary'] ?? '')),
            'published_at' => filled($payload['published_at'] ?? null) ? $payload['published_at'] : null,
            'items' => collect($payload['items'] ?? [])
                ->filter(fn ($item) => is_string($item) && trim($item) !== '')
                ->map(fn ($item) => trim($item))
                ->values()
                ->all(),
        ];

        DB::table('dev_superadmin_settings')->updateOrInsert(
            ['key' => self::SETTINGS_KEY],
            [
                'value' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
