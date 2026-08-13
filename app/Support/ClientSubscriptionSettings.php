<?php

namespace App\Support;

use App\Models\NewSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;

class ClientSubscriptionSettings
{
    private const SETTINGS_KEY = 'client_subscription';

    private const DEFAULTS = [
        'client_name' => '',
        'client_code' => '',
        'plan_name' => 'الخطة الأساسية',
        'status' => 'active',
        'work_started_at' => null,
        'subscription_started_at' => null,
        'subscription_ends_at' => null,
        'notes' => '',
    ];

    private static ?array $cached = null;

    public static function all(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        $defaults = array_replace(self::DEFAULTS, [
            'client_name' => (string) config('app.name', ''),
        ]);

        if (! Schema::hasTable('new_settings')) {
            return self::$cached = self::withSummary($defaults);
        }

        $raw = NewSettings::query()->where('key', self::SETTINGS_KEY)->value('value');
        $stored = is_string($raw) ? json_decode($raw, true) : null;

        return self::$cached = self::withSummary(self::normalize(is_array($stored) ? $stored : [], $defaults));
    }

    public static function persist(array $payload): array
    {
        $defaults = array_replace(self::DEFAULTS, [
            'client_name' => (string) config('app.name', ''),
        ]);
        $settings = self::normalize(array_replace(self::all(), $payload), $defaults);

        NewSettings::query()->updateOrCreate(
            ['key' => self::SETTINGS_KEY],
            ['value' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );

        return self::$cached = self::withSummary($settings);
    }

    private static function normalize(array $settings, ?array $defaults = null): array
    {
        $settings = array_replace($defaults ?? self::DEFAULTS, $settings);
        $allowedStatuses = ['active', 'trial', 'paused', 'expired'];

        foreach (['client_name', 'client_code', 'plan_name', 'notes'] as $key) {
            $settings[$key] = trim((string) ($settings[$key] ?? ''));
        }

        $settings['status'] = in_array($settings['status'] ?? null, $allowedStatuses, true)
            ? $settings['status']
            : 'active';

        foreach (['work_started_at', 'subscription_started_at', 'subscription_ends_at'] as $key) {
            $settings[$key] = self::normalizeDate($settings[$key] ?? null);
        }

        return array_intersect_key($settings, self::DEFAULTS);
    }

    private static function withSummary(array $settings): array
    {
        $today = CarbonImmutable::today();
        $endDate = $settings['subscription_ends_at']
            ? CarbonImmutable::parse($settings['subscription_ends_at'])->startOfDay()
            : null;
        $workStart = $settings['work_started_at']
            ? CarbonImmutable::parse($settings['work_started_at'])->startOfDay()
            : null;

        $settings['days_remaining'] = $endDate ? $today->diffInDays($endDate, false) : null;
        $settings['days_using_system'] = $workStart && $workStart->lte($today)
            ? $workStart->diffInDays($today)
            : null;
        $settings['is_expired'] = $endDate?->lt($today) ?? false;

        return $settings;
    }

    private static function normalizeDate(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
