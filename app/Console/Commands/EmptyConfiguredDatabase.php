<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;

class EmptyConfiguredDatabase extends Command
{
    protected $signature = 'database:empty-configured
        {--force : Skip the exact database-name confirmation}';

    protected $description = 'Back up and remove every table and view from the configured database';

    public function handle(DatabaseBackupService $backups): int
    {
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");
        $confirmation = 'EMPTY ' . $database;

        $this->error('DESTRUCTIVE OPERATION: every table and view will be removed.');
        $this->line('Target database: ' . $database);
        $this->line('A verified local safety backup will be created before deletion.');
        $this->warn('The database itself will remain available for the next import command.');

        if (!$this->option('force') && trim((string) $this->ask("Type {$confirmation} to continue")) !== $confirmation) {
            $this->info('Database emptying cancelled. Nothing was deleted.');

            return self::SUCCESS;
        }

        try {
            $result = $backups->emptyConfiguredDatabase();
            $this->info('Database emptied successfully.');
            $this->line('Removed tables: ' . $result['tables']);
            $this->line('Removed views: ' . $result['views']);
            $this->line('Safety backup: storage/app/' . $result['safety_backup']['path']);
            $this->newLine();
            $this->warn('Run the database:import-local command now to restore the selected SQL backup.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
