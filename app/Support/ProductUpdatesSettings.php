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
        'updates' => [
            [
                'title' => 'إشعارات الدائن لفواتير الاستقدام والتفاويض',
                'details' => 'يمكنك الآن إنشاء إشعار دائن مباشرة من الفاتورة مع إنشاء القيد المالي المرتبط به.',
                'url' => '/dashboard/invoices',
                'link_label' => 'الانتقال إلى الفواتير',
            ],
        ],
    ];

    public static function all(): array
    {
        $settings = self::DEFAULTS;
        $version = sha1(json_encode(self::DEFAULTS, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $stored = null;

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
        $rawUpdates = is_array($stored) && array_key_exists('updates', $stored)
            ? ($settings['updates'] ?? [])
            : self::convertLegacyItems($settings['items'] ?? null);

        if ($rawUpdates === null) {
            $rawUpdates = self::DEFAULTS['updates'];
        }

        $settings['updates'] = collect($rawUpdates)
            ->filter(fn ($update) => is_array($update))
            ->map(fn ($update) => self::normalizeUpdate($update))
            ->filter(fn ($update) => $update['title'] !== '')
            ->values()
            ->all();
        unset($settings['items']);
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
            'updates' => collect($payload['updates'] ?? [])
                ->filter(fn ($update) => is_array($update))
                ->map(fn ($update) => self::normalizeUpdate($update))
                ->filter(fn ($update) => $update['title'] !== '')
                ->values()
                ->all(),
        ];

        \App\Support\Audit\AuditedSettings::updateOrInsert('dev_superadmin_settings',
            ['key' => self::SETTINGS_KEY],
            [
                'value' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private static function normalizeUpdate(array $update): array
    {
        return [
            'title' => trim((string) ($update['title'] ?? '')),
            'details' => trim((string) ($update['details'] ?? '')),
            'url' => trim((string) ($update['url'] ?? '')),
            'link_label' => trim((string) ($update['link_label'] ?? '')),
        ];
    }

    private static function convertLegacyItems(mixed $items): ?array
    {
        if (! is_array($items)) {
            return null;
        }

        return collect($items)
            ->filter(fn ($item) => is_string($item) && trim($item) !== '')
            ->map(fn ($item) => [
                'title' => trim($item),
                'details' => '',
                'url' => '',
                'link_label' => '',
            ])
            ->values()
            ->all();
    }
}
