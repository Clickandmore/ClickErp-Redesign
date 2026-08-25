<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;

class ImportLocalDatabase extends Command
{
    protected $signature = 'database:import-local
        {file : Absolute or project-relative path to a .sql or .sql.gz file}
        {--force : Skip the interactive IMPORT confirmation}';

    protected $description = 'Import a local SQL backup into the configured database after creating a local safety backup';

    public function handle(DatabaseBackupService $backups): int
    {
        $input = (string) $this->argument('file');
        $path = $this->resolvePath($input);

        if ($path === null) {
            $this->error('ملف قاعدة البيانات غير موجود: ' . $input);

            return self::FAILURE;
        }

        $this->warn('سيتم استبدال قاعدة البيانات المرتبطة بالمشروع بهذا الملف:');
        $this->line($path);
        $this->line('سيتم إنشاء نسخة أمان محلية أولًا داخل storage/app/database-backups.');

        if (!$this->option('force') && (string) $this->ask('اكتب IMPORT للتأكيد') !== 'IMPORT') {
            $this->info('تم إلغاء الاستيراد دون تغيير قاعدة البيانات.');

            return self::SUCCESS;
        }

        try {
            $backups->importFile($path);
            $this->info('تم استيراد قاعدة البيانات بنجاح وحفظ نسخة الأمان المحلية.');

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

        foreach ($candidates as $candidate) {
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
}
