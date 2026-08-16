<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ClearOldLogs extends Command
{
    protected $signature = 'log:clear
        {--keep=30 : Number of days to retain log files}
        {--dry-run : Show files that would be deleted without deleting them}';

    protected $description = 'Delete old .log files from storage/logs without touching application data';

    public function handle(): int
    {
        $keepDays = filter_var($this->option('keep'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 3650],
        ]);

        if ($keepDays === false) {
            $this->error('The --keep value must be an integer between 1 and 3650 days.');

            return self::INVALID;
        }

        $logsDirectory = storage_path('logs');
        $realLogsDirectory = realpath($logsDirectory);

        if ($realLogsDirectory === false || !is_dir($realLogsDirectory)) {
            $this->info('The storage/logs directory does not exist; nothing to clear.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($keepDays)->getTimestamp();
        $dryRun = (bool) $this->option('dry-run');
        $matched = 0;
        $deleted = 0;
        $freedBytes = 0;
        $failed = 0;

        foreach (File::allFiles($realLogsDirectory) as $file) {
            if (Str::lower($file->getExtension()) !== 'log' || $file->getMTime() >= $cutoff) {
                continue;
            }

            $path = $file->getRealPath();
            if ($path === false || !Str::startsWith($path, $realLogsDirectory . DIRECTORY_SEPARATOR)) {
                continue;
            }

            $matched++;
            $size = $file->getSize();

            if ($dryRun) {
                $this->line('Would delete: ' . $path);
                continue;
            }

            if (File::delete($path)) {
                $deleted++;
                $freedBytes += $size;
            } else {
                $failed++;
                $this->warn('Could not delete: ' . $path);
            }
        }

        if ($dryRun) {
            $this->info("Dry run complete: {$matched} old log file(s) matched.");
        } else {
            $this->info(sprintf(
                'Log cleanup complete: %d file(s) deleted, %s freed, %d failure(s).',
                $deleted,
                $this->formatBytes($freedBytes),
                $failed
            ));
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return number_format($bytes / (1024 * 1024), 2) . ' MB';
    }
}
