<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SystemModuleManager
{
    private const CACHE_KEY = 'superadmin.modules.enabled_map';
    private const SETTINGS_CACHE_KEY = 'superadmin.settings';
    private const BRANCH_LIMIT_KEY = 'branches_limit';
    private const USER_LIMIT_KEY = 'users_limit';
    private const PRODUCT_PROFILE_KEY = 'product_profile';

    public static function modules(): array
    {
        return config('superadmin_modules', []);
    }

    public static function enabledMap(): array
    {
        return collect(self::configuredMap())
            ->mapWithKeys(fn (bool $enabled, string $key) => [
                $key => ($enabled || self::isRequiredForProfile($key))
                    && self::isAvailableForProfile($key),
            ])
            ->all();
    }

    /**
     * The saved switches before applying the active product profile.
     * Keeping this map separate means changing profile never destroys a client's choices.
     */
    public static function configuredMap(): array
    {
        $defaults = self::defaultEnabledMap();

        $stored = Cache::remember(self::CACHE_KEY, now()->addMinutes(5), function () {
            if (! Schema::hasTable('system_module_settings')) {
                return [];
            }

            return DB::table('system_module_settings')
                ->pluck('is_enabled', 'key')
                ->map(fn ($value) => (bool) $value)
                ->all();
        });

        return array_merge($defaults, $stored);
    }

    public static function isEnabled(string $key): bool
    {
        return self::enabledMap()[$key] ?? true;
    }

    public static function productProfiles(): array
    {
        return config('product_profiles', []);
    }

    public static function productProfileKey(): string
    {
        if (! Schema::hasTable('dev_superadmin_settings')) {
            return 'recruitment';
        }

        $value = Cache::remember(self::SETTINGS_CACHE_KEY . '.' . self::PRODUCT_PROFILE_KEY, now()->addMinutes(5), function () {
            return DB::table('dev_superadmin_settings')
                ->where('key', self::PRODUCT_PROFILE_KEY)
                ->value('value');
        });

        return array_key_exists((string) $value, self::productProfiles())
            ? (string) $value
            : 'recruitment';
    }

    public static function updateProductProfile(string $key): void
    {
        if (! array_key_exists($key, self::productProfiles()) || ! Schema::hasTable('dev_superadmin_settings')) {
            return;
        }

        \App\Support\Audit\AuditedSettings::updateOrInsert('dev_superadmin_settings',
            ['key' => self::PRODUCT_PROFILE_KEY],
            [
                'value' => $key,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        Cache::forget(self::SETTINGS_CACHE_KEY . '.' . self::PRODUCT_PROFILE_KEY);
    }

    public static function isAvailableForProfile(string $key, ?string $profile = null): bool
    {
        $module = self::modules()[$key] ?? null;

        if (! is_array($module)) {
            return false;
        }

        $profiles = $module['profiles'] ?? [];

        return $profiles === [] || in_array($profile ?? self::productProfileKey(), $profiles, true);
    }

    public static function requiredModules(?string $profile = null): array
    {
        $profile ??= self::productProfileKey();

        return array_values(array_intersect(
            (array) (self::productProfiles()[$profile]['required_modules'] ?? []),
            array_keys(self::modules())
        ));
    }

    public static function isRequiredForProfile(string $key, ?string $profile = null): bool
    {
        return in_array($key, self::requiredModules($profile), true);
    }

    public static function defaultDashboardForProfile(?string $profile = null): string
    {
        $profile ??= self::productProfileKey();
        $dashboard = (string) (self::productProfiles()[$profile]['default_dashboard'] ?? 'recruitment');

        return array_key_exists($dashboard, self::dashboards()) ? $dashboard : 'recruitment';
    }

    public static function update(array $enabledKeys, ?int $adminId = null): void
    {
        if (! Schema::hasTable('system_module_settings')) {
            return;
        }

        $enabledKeys = array_values(array_unique(array_merge(
            $enabledKeys,
            self::requiredModules()
        )));

        foreach (self::modules() as $key => $module) {
            // A profile can make a module unavailable without erasing its saved
            // state. Switching back restores the client's previous setup.
            if (! self::isAvailableForProfile($key)) {
                continue;
            }

            \App\Support\Audit\AuditedSettings::updateOrInsert('system_module_settings',
                ['key' => $key],
                [
                    'name' => $module['name'] ?? $key,
                    'description' => $module['description'] ?? null,
                    'is_enabled' => in_array($key, $enabledKeys, true),
                    'updated_by' => $adminId,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        Cache::forget(self::CACHE_KEY);
    }

    public static function dashboards(): array
    {
        return config('superadmin_dashboards', []);
    }

    public static function defaultDashboardKey(): string
    {
        $dashboards = self::dashboards();

        if (! Schema::hasTable('dev_superadmin_settings')) {
            return 'recruitment';
        }

        $value = Cache::remember(self::SETTINGS_CACHE_KEY . '.default_dashboard', now()->addMinutes(5), function () {
            return DB::table('dev_superadmin_settings')
                ->where('key', 'default_dashboard')
                ->value('value');
        });

        return array_key_exists((string) $value, $dashboards) ? (string) $value : 'recruitment';
    }

    public static function updateDefaultDashboard(string $key): void
    {
        if (! array_key_exists($key, self::dashboards()) || ! Schema::hasTable('dev_superadmin_settings')) {
            return;
        }

        \App\Support\Audit\AuditedSettings::updateOrInsert('dev_superadmin_settings',
            ['key' => 'default_dashboard'],
            [
                'value' => $key,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        Cache::forget(self::SETTINGS_CACHE_KEY . '.default_dashboard');
    }

    public static function branchLimit(): ?int
    {
        if (! Schema::hasTable('dev_superadmin_settings')) {
            return null;
        }

        $value = Cache::remember(self::SETTINGS_CACHE_KEY . '.' . self::BRANCH_LIMIT_KEY, now()->addMinutes(5), function () {
            return DB::table('dev_superadmin_settings')
                ->where('key', self::BRANCH_LIMIT_KEY)
                ->value('value');
        });

        return is_numeric($value) ? max((int) $value, 0) : null;
    }

    public static function updateBranchLimit(?int $limit): void
    {
        if (! Schema::hasTable('dev_superadmin_settings')) {
            return;
        }

        \App\Support\Audit\AuditedSettings::updateOrInsert('dev_superadmin_settings',
            ['key' => self::BRANCH_LIMIT_KEY],
            [
                'value' => $limit === null ? null : (string) max($limit, 0),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        Cache::forget(self::SETTINGS_CACHE_KEY . '.' . self::BRANCH_LIMIT_KEY);
    }

    public static function userLimit(): ?int
    {
        if (! Schema::hasTable('dev_superadmin_settings')) {
            return null;
        }

        $value = Cache::remember(self::SETTINGS_CACHE_KEY . '.' . self::USER_LIMIT_KEY, now()->addMinutes(5), function () {
            return DB::table('dev_superadmin_settings')
                ->where('key', self::USER_LIMIT_KEY)
                ->value('value');
        });

        return is_numeric($value) ? max((int) $value, 0) : null;
    }

    public static function updateUserLimit(?int $limit): void
    {
        if (! Schema::hasTable('dev_superadmin_settings')) {
            return;
        }

        \App\Support\Audit\AuditedSettings::updateOrInsert('dev_superadmin_settings',
            ['key' => self::USER_LIMIT_KEY],
            [
                'value' => $limit === null ? null : (string) max($limit, 0),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        Cache::forget(self::SETTINGS_CACHE_KEY . '.' . self::USER_LIMIT_KEY);
    }

    public static function defaultDashboardUrl(): string
    {
        $dashboard = self::dashboards()[self::defaultDashboardKey()] ?? self::dashboards()['recruitment'];
        $routeName = $dashboard['route'] ?? null;

        if ($routeName && app('router')->has($routeName)) {
            return route($routeName);
        }

        return url($dashboard['url'] ?? '/dashboard');
    }

    public static function disabledModuleForRoute(?string $routeName, string $path): ?array
    {
        foreach (self::modules() as $key => $module) {
            if (self::isEnabled($key)) {
                continue;
            }

            if ($routeName && self::matchesAny($routeName, $module['route_patterns'] ?? [])) {
                return ['key' => $key, 'module' => $module];
            }

            if (self::matchesAny($path, $module['path_patterns'] ?? [])) {
                return ['key' => $key, 'module' => $module];
            }
        }

        return null;
    }

    private static function matchesAny(string $value, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    private static function defaultEnabled(array $module): bool
    {
        return array_key_exists('default_enabled', $module)
            ? (bool) $module['default_enabled']
            : true;
    }

    private static function defaultEnabledMap(): array
    {
        return collect(self::modules())
            ->mapWithKeys(fn ($module, $key) => [$key => self::defaultEnabled($module)])
            ->all();
    }
}
