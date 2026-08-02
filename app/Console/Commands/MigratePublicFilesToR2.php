<?php

namespace App\Console\Commands;

use App\Services\ManagedFileStorage;
use App\Support\R2StorageSettings;
use Doctrine\DBAL\Schema\Column;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MigratePublicFilesToR2 extends Command
{
    protected $signature = 'storage:migrate-to-r2
        {--apply : Persist changes; without this option the command is a dry run}
        {--table=* : Only scan the specified table(s)}
        {--chunk=500 : Number of database rows to read at a time}';

    protected $description = 'Convert existing public-disk paths to verified R2 paths';

    private ManagedFileStorage $storage;
    private string $rootPrefix = '';
    private bool $apply = false;

    /** @var array<string, bool> */
    private array $r2ExistenceCache = [];

    private int $rowsScanned = 0;
    private int $rowsChanged = 0;
    private int $pathsMigrated = 0;
    private int $pathsMissing = 0;
    private int $valuesSkipped = 0;

    /** @var array<string, true> */
    private array $missingPaths = [];

    public function handle(ManagedFileStorage $storage): int
    {
        $this->storage = $storage;
        $this->apply = (bool) $this->option('apply');

        $settings = R2StorageSettings::all();
        if (!$this->hasVerifiedR2Configuration($settings)) {
            $this->error('R2 settings must be complete and successfully connection-tested before migration.');

            return self::FAILURE;
        }

        $this->rootPrefix = trim((string) $settings['root_prefix'], '/');
        $chunkSize = max(1, (int) $this->option('chunk'));
        $connection = DB::connection();
        $connection->getDoctrineSchemaManager()
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('enum', 'string');

        $tables = $this->selectedTables($connection);
        if ($tables === []) {
            $this->error('No matching database tables were found.');

            return self::FAILURE;
        }

        $this->info($this->apply
            ? 'APPLY mode: verified database paths will be updated.'
            : 'DRY RUN: no database values will be changed. Add --apply after reviewing this report.');
        $this->line('R2 destination prefix: ' . ($this->rootPrefix !== '' ? $this->rootPrefix . '/' : '(bucket root)'));

        foreach ($tables as $table) {
            $this->scanTable($connection, $table, $chunkSize);
        }

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['Rows scanned', $this->rowsScanned],
            [$this->apply ? 'Rows updated' : 'Rows that would update', $this->rowsChanged],
            [$this->apply ? 'Paths migrated' : 'Paths ready to migrate', $this->pathsMigrated],
            ['Local paths missing from R2', $this->pathsMissing],
            ['Non-path values skipped', $this->valuesSkipped],
        ]);

        if ($this->missingPaths !== []) {
            $this->warn('Local files missing from R2 (database values were not changed):');
            foreach (array_keys($this->missingPaths) as $path) {
                $this->line(' - ' . $path);
            }
        }

        if (!$this->apply && $this->pathsMigrated > 0) {
            $this->newLine();
            $this->comment('Review the report, back up the database, then rerun with --apply.');
        }

        return $this->pathsMissing > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** @return list<string> */
    private function selectedTables(Connection $connection): array
    {
        $schema = $connection->getDoctrineSchemaManager();
        $available = $schema->listTableNames();
        $requested = array_values(array_filter((array) $this->option('table')));

        if ($requested === []) {
            return $available;
        }

        $unknown = array_values(array_diff($requested, $available));
        if ($unknown !== []) {
            $this->warn('Unknown table(s) skipped: ' . implode(', ', $unknown));
        }

        return array_values(array_intersect($available, $requested));
    }

    private function scanTable(Connection $connection, string $table, int $chunkSize): void
    {
        $schema = $connection->getDoctrineSchemaManager();
        $details = $schema->introspectTable($table);
        $primaryKey = $details->getPrimaryKey();
        $keyColumns = $primaryKey ? $primaryKey->getColumns() : [];

        if ($keyColumns === []) {
            $this->warn("Skipping {$table}: it has no primary key.");

            return;
        }

        $textColumns = array_values(array_map(
            static fn (Column $column): string => $column->getName(),
            array_filter(
                $details->getColumns(),
                static fn (Column $column): bool => in_array(
                    $column->getType()->getName(),
                    ['string', 'text', 'json'],
                    true
                )
            )
        ));

        if ($textColumns === []) {
            return;
        }

        $this->line("Scanning {$table}...");
        $offset = 0;

        do {
            $rows = $connection->table($table)
                ->select(array_values(array_unique(array_merge($keyColumns, $textColumns))))
                ->orderBy($keyColumns[0])
                ->offset($offset)
                ->limit($chunkSize)
                ->get();

            foreach ($rows as $row) {
                $this->rowsScanned++;
                $updates = [];

                foreach ($textColumns as $column) {
                    $original = $row->{$column};
                    if (!is_string($original) || $original === '') {
                        continue;
                    }

                    [$replacement, $changed] = $this->migrateDatabaseValue($original);
                    if ($changed) {
                        $updates[$column] = $replacement;
                    }
                }

                if ($updates === []) {
                    continue;
                }

                $this->rowsChanged++;
                if ($this->apply) {
                    $query = $connection->table($table);
                    foreach ($keyColumns as $keyColumn) {
                        $query->where($keyColumn, $row->{$keyColumn});
                    }
                    $query->update($updates);
                }
            }

            $count = $rows->count();
            $offset += $count;
        } while ($count === $chunkSize);
    }

    /** @return array{0: string, 1: bool} */
    private function migrateDatabaseValue(string $value): array
    {
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            [$migrated, $changed] = $this->migrateJsonValue($decoded);

            return $changed
                ? [json_encode($migrated, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), true]
                : [$value, false];
        }

        return $this->migratePath($value);
    }

    /** @return array{0: mixed, 1: bool} */
    private function migrateJsonValue($value): array
    {
        if (is_string($value)) {
            return $this->migratePath($value);
        }

        if (!is_array($value)) {
            return [$value, false];
        }

        $changed = false;
        foreach ($value as $key => $item) {
            [$value[$key], $itemChanged] = $this->migrateJsonValue($item);
            $changed = $changed || $itemChanged;
        }

        return [$value, $changed];
    }

    /** @return array{0: string, 1: bool} */
    private function migratePath(string $value): array
    {
        if (!$this->isPotentialLocalPath($value)) {
            $this->valuesSkipped++;

            return [$value, false];
        }

        $path = ltrim($value, '/');
        if (!$this->existsLocally($path) && !$this->looksLikeStoredFilePath($path)) {
            $this->valuesSkipped++;

            return [$value, false];
        }

        $r2ObjectPath = trim($this->rootPrefix . '/' . $path, '/');
        $r2StoredPath = ManagedFileStorage::R2_PREFIX . $r2ObjectPath;

        if (!array_key_exists($r2ObjectPath, $this->r2ExistenceCache)) {
            $this->r2ExistenceCache[$r2ObjectPath] = $this->storage->exists($r2StoredPath);
        }

        if (!$this->r2ExistenceCache[$r2ObjectPath]) {
            if (!isset($this->missingPaths[$path])) {
                $this->missingPaths[$path] = true;
                $this->pathsMissing++;
            }

            return [$value, false];
        }

        $this->pathsMigrated++;

        return [$r2StoredPath, true];
    }

    private function isPotentialLocalPath(string $value): bool
    {
        if ($value === ''
            || strlen($value) > 1024
            || trim($value) !== $value
            || preg_match('/[\x00-\x1F\x7F<>"\']/', $value)
        ) {
            return false;
        }

        if (str_starts_with($value, ManagedFileStorage::R2_PREFIX)
            || str_starts_with($value, '/')
            || str_contains($value, "\0")
            || str_contains($value, '\\')
            || preg_match('#(^|/)\.\.(/|$)#', $value)
        ) {
            return false;
        }

        return !preg_match('#^[a-z][a-z0-9+.-]*://#i', $value);
    }

    private function existsLocally(string $path): bool
    {
        try {
            return Storage::disk('public')->exists($path);
        } catch (\Throwable $exception) {
            return false;
        }
    }

    private function looksLikeStoredFilePath(string $path): bool
    {
        return !preg_match('/\s/', $path)
            && str_contains($path, '/')
            && (bool) preg_match(
                '/\.(?:avif|csv|docx?|gif|heic|jpe?g|mov|mp4|pdf|png|svg|txt|webm|webp|xlsx?|zip)$/i',
                $path
            );
    }

    private function hasVerifiedR2Configuration(array $settings): bool
    {
        foreach (['verified_at', 'endpoint', 'bucket', 'access_key_id', 'secret_access_key'] as $key) {
            if (!filled($settings[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
