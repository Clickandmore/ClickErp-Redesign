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

    public static function modules(): array
    {
        return config('superadmin_modules', []);
    }

    public static function enabledMap(): array
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

    public static function update(array $enabledKeys, ?int $adminId = null): void
    {
        if (! Schema::hasTable('system_module_settings')) {
            return;
        }

        foreach (self::modules() as $key => $module) {
            DB::table('system_module_settings')->updateOrInsert(
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

        DB::table('dev_superadmin_settings')->updateOrInsert(
            ['key' => 'default_dashboard'],
            [
                'value' => $key,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        Cache::forget(self::SETTINGS_CACHE_KEY . '.default_dashboard');
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
