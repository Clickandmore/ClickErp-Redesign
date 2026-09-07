<?php

namespace App\Support;

use App\Models\NewSettings;
use Illuminate\Support\Facades\Schema;

class BranchSharingSettings
{
    private const SETTINGS_KEY = 'branch_sharing_settings';

    private const DEFAULTS = [
        'share_maids' => true,
        'shared_cost_center_root' => true,
        'accounts_per_branch' => false,
    ];

    private static ?array $cache = null;

    public static function keys(): array
    {
        return array_keys(self::DEFAULTS);
    }

    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $settings = self::DEFAULTS;

        if (Schema::hasTable('new_settings')) {
            $raw = NewSettings::query()
                ->where('key', self::SETTINGS_KEY)
                ->value('value');

            $stored = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
            if (is_array($stored)) {
                $settings = array_replace($settings, array_intersect_key($stored, self::DEFAULTS));
            }
        }

        return self::$cache = self::normalize($settings);
    }

    public static function persist(array $values): void
    {
        $settings = self::normalize(array_replace(self::all(), array_intersect_key($values, self::DEFAULTS)));

        NewSettings::query()->updateOrCreate(
            ['key' => self::SETTINGS_KEY],
            ['value' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );

        self::$cache = $settings;
    }

    public static function accountsPerBranch(): bool
    {
        return self::boolValue('accounts_per_branch');
    }

    public static function shareMaids(): bool
    {
        return self::boolValue('share_maids');
    }

    public static function sharedCostCenterRoot(): bool
    {
        return self::boolValue('shared_cost_center_root');
    }

    public static function flush(): void
    {
        self::$cache = null;
    }

    private static function boolValue(string $key): bool
    {
        return (bool) (self::all()[$key] ?? self::DEFAULTS[$key] ?? false);
    }

    private static function normalize(array $settings): array
    {
        foreach (self::DEFAULTS as $key => $default) {
            $settings[$key] = filter_var($settings[$key] ?? $default, FILTER_VALIDATE_BOOLEAN);
        }

        return array_intersect_key($settings, self::DEFAULTS);
    }
}
