<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;

class ImportLocalDatabase extends Command
{
    protected $signature = 'database:import-local
        {file? : Absolute or project-relative path to a .sql or .sql.gz file}
        {--force : Skip the interactive IMPORT confirmation}';

    protected $description = 'Import a local SQL backup into the configured database after creating a local safety backup';

    public function handle(DatabaseBackupService $backups): int
    {
        $input = trim((string) $this->argument('file'));
        if ($input === '') {
            $input = $this->normalizeDraggedPath((string) $this->ask(
                'Drag the SQL or SQL.GZ file here, then press Enter'
            ));
        }

        $path = $this->resolvePath($input);

        if ($path === null) {
            $this->error('Database file not found: ' . $input);

            return self::FAILURE;
        }

        $this->warn('The database configured for this project will be replaced with:');
        $this->line($path);
        $this->line('A local safety backup will be created first in storage/app/database-backups.');

        if (!$this->option('force') && (string) $this->ask('Type IMPORT to confirm') !== 'IMPORT') {
            $this->info('Import cancelled. The database was not changed.');

            return self::SUCCESS;
        }

        try {
            $backups->importFile($path);
            $this->info('Database imported successfully. The local safety backup was preserved.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function resolvePath(string $input): ?string
    {
        $candidates = [$input];
        if (!$this->isAbsolutePath($input)) {
            $candidates[] = base_path($input);
        }

        if (basename($input) === $input) {
            $userDirectory = PHP_OS_FAMILY === 'Windows'
                ? (string) getenv('USERPROFILE')
                : (string) getenv('HOME');
            if ($userDirectory === '' && PHP_OS_FAMILY === 'Windows') {
                $userDirectory = (string) getenv('HOMEDRIVE') . (string) getenv('HOMEPATH');
            }
            $userDirectory = rtrim($userDirectory, '\\/');
            $searchDirectories = array_filter(array_merge(
                (array) config('database.backup_import_search_paths', []),
                [
                    storage_path('app/database-backups'),
                    $userDirectory !== '' ? $userDirectory . DIRECTORY_SEPARATOR . 'Downloads' : null,
                    $userDirectory !== '' ? $userDirectory . DIRECTORY_SEPARATOR . 'Desktop' : null,
                    $userDirectory !== '' ? $userDirectory . DIRECTORY_SEPARATOR . 'Documents' : null,
                ]
            ));

            foreach ($searchDirectories as $directory) {
                $candidates[] = rtrim((string) $directory, DIRECTORY_SEPARATOR)
                    . DIRECTORY_SEPARATOR . $input;
            }
        }

        foreach (array_unique($candidates) as $candidate) {
            $resolved = realpath($candidate);
            if ($resolved !== false && is_file($resolved)) {
                return $resolved;
            }
        }

        return null;
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }

    private function normalizeDraggedPath(string $path): string
    {
        $path = trim($path);
        if (strlen($path) >= 2) {
            $first = $path[0];
            $last = $path[strlen($path) - 1];
            if (($first === "'" && $last === "'") || ($first === '"' && $last === '"')) {
                $path = substr($path, 1, -1);
            }
        }

        // macOS/Linux terminals add shell escapes when a file is dragged in.
        // Windows paths use literal backslashes and must remain unchanged.
        return PHP_OS_FAMILY === 'Windows'
            ? $path
            : (string) preg_replace('/\\\\(.)/', '$1', $path);
    }
}
