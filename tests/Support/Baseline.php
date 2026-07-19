<?php

namespace Tests\Support;

use Illuminate\Support\Facades\File;

/**
 * Approval-style baselines for project-wide checks.
 *
 * Several whole-project assertions (dead routes, duplicate route names) start out
 * failing against a large amount of pre-existing debt. Deleting the assertion loses the
 * signal; leaving it red makes the suite useless. Instead each check records the known
 * problems once, then fails only when something NEW appears — so the debt is visible and
 * cannot grow, and fixing an entry is rewarded by shrinking the baseline.
 *
 * Workflow:
 *   - New check, or after fixing entries: BASELINE_UPDATE=1 vendor/bin/phpunit
 *   - Everyday runs: new problems fail the build; fixed problems are reported as stale.
 */
class Baseline
{
    public static function path(string $key): string
    {
        return base_path("tests/baselines/{$key}.json");
    }

    /**
     * @return list<string>
     */
    public static function load(string $key): array
    {
        $path = self::path($key);

        return File::exists($path)
            ? (array) json_decode(File::get($path), true)
            : [];
    }

    /**
     * @param  list<string>  $entries
     */
    public static function save(string $key, array $entries): void
    {
        $path = self::path($key);

        File::ensureDirectoryExists(dirname($path));
        sort($entries);

        File::put($path, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
    }

    public static function shouldUpdate(): bool
    {
        return (bool) (getenv('BASELINE_UPDATE') ?: ($_SERVER['BASELINE_UPDATE'] ?? false));
    }

    public static function exists(string $key): bool
    {
        return File::exists(self::path($key));
    }
}
