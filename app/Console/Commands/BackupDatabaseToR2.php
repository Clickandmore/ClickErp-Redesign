<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use App\Support\DatabaseBackupSettings;
use Illuminate\Console\Command;

class BackupDatabaseToR2 extends Command
{
    protected $signature = 'database:backup-r2 {--force : Run even when the daily schedule is disabled}';
    protected $description = 'Create a compressed MySQL backup and upload it to the configured R2 bucket';

    public function handle(DatabaseBackupService $backups): int
    {
        if (!DatabaseBackupSettings::all()['enabled'] && !$this->option('force')) {
            $this->line('Daily database backups are disabled.');
            return self::SUCCESS;
        }

        try {
            $backup = $backups->create($this->option('force') ? 'manual' : 'scheduled');
            $this->info('Backup uploaded to R2: ' . $backup['filename']);
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }
}
