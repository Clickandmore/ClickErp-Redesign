<?php

namespace App\Services;

use App\Support\DatabaseBackupSettings;
use App\Support\R2StorageSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    private const FILE_PATTERN = '/^clickerp-(?:scheduled|manual|pre-restore)-\d{8}-\d{6}-[a-z0-9]{8}\.sql\.gz$/';
    private const LOCAL_DIRECTORY = 'database-backups';

    public function create(string $reason = 'manual'): array
    {
        $this->assertR2Ready();

        return Cache::lock('database-backup-operation', 7200)->block(5, function () use ($reason) {
            $reason = in_array($reason, ['scheduled', 'manual', 'pre-restore'], true) ? $reason : 'manual';
            $temporaryPath = tempnam(storage_path('app'), 'db-backup-');
            if ($temporaryPath === false) {
                throw new RuntimeException('تعذر إنشاء الملف المؤقت للنسخة الاحتياطية.');
            }

            $gzipPath = $temporaryPath . '.sql.gz';

            try {
                $this->dumpDatabase($gzipPath);
                $filename = sprintf(
                    'clickerp-%s-%s-%s.sql.gz',
                    $reason,
                    now()->format('Ymd-His'),
                    Str::lower(Str::random(8))
                );
                $localPath = self::LOCAL_DIRECTORY . '/' . $filename;
                $this->storeLocalCopy($gzipPath, $localPath);
                $this->pruneLocal();
                $key = $this->directory() . '/' . $filename;
                $stream = fopen($gzipPath, 'rb');
                if (!is_resource($stream)) {
                    throw new RuntimeException('تعذرت قراءة النسخة الاحتياطية بعد إنشائها.');
                }

                try {
                    $this->disk()->writeStream($key, $stream);
                } finally {
                    fclose($stream);
                }

                $this->pruneR2();

                return [
                    'key' => $key,
                    'filename' => $filename,
                    'size' => filesize($gzipPath) ?: 0,
                    'created_at' => now(),
                    'local_path' => $localPath,
                ];
            } finally {
                @unlink($gzipPath);
                @unlink($temporaryPath);
            }
        });
    }

    public function backups(): array
    {
        if (!R2StorageSettings::isReady()) {
            return [];
        }

        $disk = $this->disk();
        $items = [];

        foreach ($disk->files($this->directory()) as $key) {
            $filename = basename($key);
            if (!preg_match(self::FILE_PATTERN, $filename)) {
                continue;
            }

            $items[] = [
                'key' => $key,
                'filename' => $filename,
                'size' => $disk->size($key),
                'created_at' => $disk->lastModified($key),
            ];
        }

        usort($items, fn (array $a, array $b) => $b['created_at'] <=> $a['created_at']);

        return $items;
    }

    public function localBackups(): array
    {
        $disk = Storage::disk('local');
        $items = [];

        foreach ($disk->files(self::LOCAL_DIRECTORY) as $path) {
            $filename = basename($path);
            if (!preg_match(self::FILE_PATTERN, $filename)) {
                continue;
            }

            $items[] = [
                'path' => $path,
                'filename' => $filename,
                'size' => $disk->size($path),
                'created_at' => $disk->lastModified($path),
            ];
        }

        usort($items, fn (array $a, array $b) => $b['created_at'] <=> $a['created_at']);

        return $items;
    }

    public function readLocalStream(string $path)
    {
        if (!Str::startsWith($path, self::LOCAL_DIRECTORY . '/') || !preg_match(self::FILE_PATTERN, basename($path))) {
            throw new RuntimeException('مسار النسخة المحلية غير صالح.');
        }

        $stream = Storage::disk('local')->readStream($path);
        if (!is_resource($stream)) {
            throw new RuntimeException('تعذرت قراءة النسخة المحلية من السيرفر.');
        }

        return $stream;
    }

    public function restore(string $key): void
    {
        $this->assertValidKey($key);
        $this->assertR2Ready();

        Cache::lock('database-backup-operation', 7200)->block(5, function () use ($key): void {
            $disk = $this->disk();
            if (!$disk->exists($key)) {
                throw new RuntimeException('النسخة الاحتياطية المطلوبة غير موجودة في R2.');
            }

            // Always leave a recovery point containing the state immediately before restore.
            $this->createWithoutLock('pre-restore');

            $stream = $disk->readStream($key);
            if (!is_resource($stream)) {
                throw new RuntimeException('تعذرت قراءة النسخة الاحتياطية من R2.');
            }

            $temporaryPath = tempnam(storage_path('app'), 'db-restore-');
            if ($temporaryPath === false) {
                fclose($stream);
                throw new RuntimeException('تعذر إنشاء ملف الاستعادة المؤقت.');
            }

            try {
                $local = fopen($temporaryPath, 'wb');
                if (!is_resource($local)) {
                    throw new RuntimeException('تعذر تجهيز ملف الاستعادة.');
                }
                stream_copy_to_stream($stream, $local);
                fclose($local);
                fclose($stream);
                $stream = null;

                $sql = gzopen($temporaryPath, 'rb');
                if (!is_resource($sql)) {
                    throw new RuntimeException('ملف النسخة تالف أو ليس بصيغة SQL مضغوطة.');
                }

                DB::disconnect();
                $process = new Process($this->mysqlCommand(), base_path(), $this->processEnvironment());
                $process->setTimeout(7200);
                $process->setInput($sql);
                $process->run();
                gzclose($sql);

                if (!$process->isSuccessful()) {
                    throw new RuntimeException('فشلت استعادة قاعدة البيانات: ' . trim($process->getErrorOutput()));
                }
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
                @unlink($temporaryPath);
            }
        });
    }

    public function delete(string $key): void
    {
        $this->assertValidKey($key);
        $this->assertR2Ready();

        if (!$this->disk()->delete($key)) {
            throw new RuntimeException('تعذر حذف النسخة الاحتياطية من R2.');
        }
    }

    public function readStream(string $key)
    {
        $this->assertValidKey($key);
        $this->assertR2Ready();
        $stream = $this->disk()->readStream($key);

        if (!is_resource($stream)) {
            throw new RuntimeException('تعذرت قراءة النسخة الاحتياطية.');
        }

        return $stream;
    }

    private function createWithoutLock(string $reason): array
    {
        $temporaryPath = tempnam(storage_path('app'), 'db-backup-');
        if ($temporaryPath === false) {
            throw new RuntimeException('تعذر إنشاء نسخة الأمان قبل الاستعادة.');
        }
        $gzipPath = $temporaryPath . '.sql.gz';

        try {
            $this->dumpDatabase($gzipPath);
            $filename = 'clickerp-' . $reason . '-' . now()->format('Ymd-His') . '-' . Str::lower(Str::random(8)) . '.sql.gz';
            $localPath = self::LOCAL_DIRECTORY . '/' . $filename;
            $this->storeLocalCopy($gzipPath, $localPath);
            $this->pruneLocal();
            $key = $this->directory() . '/' . $filename;
            $stream = fopen($gzipPath, 'rb');
            if (!is_resource($stream)) {
                throw new RuntimeException('تعذرت قراءة نسخة الأمان.');
            }
            try {
                $this->disk()->writeStream($key, $stream);
            } finally {
                fclose($stream);
            }

            $this->pruneR2();

            return ['key' => $key, 'filename' => $filename, 'size' => filesize($gzipPath) ?: 0, 'local_path' => $localPath];
        } finally {
            @unlink($gzipPath);
            @unlink($temporaryPath);
        }
    }

    private function dumpDatabase(string $gzipPath): void
    {
        $gzip = gzopen($gzipPath, 'wb9');
        if (!is_resource($gzip)) {
            throw new RuntimeException('تعذر فتح ملف النسخة الاحتياطية للكتابة.');
        }

        $process = new Process($this->mysqldumpCommand(), base_path(), $this->processEnvironment());
        $process->setTimeout(7200);
        $process->disableOutput();
        $errorOutput = '';
        $process->run(function (string $type, string $buffer) use ($gzip, &$errorOutput): void {
            if ($type === Process::OUT) {
                gzwrite($gzip, $buffer);
            } else {
                $errorOutput .= $buffer;
            }
        });
        gzclose($gzip);

        if (!$process->isSuccessful()) {
            throw new RuntimeException('فشل إنشاء نسخة قاعدة البيانات: ' . trim($errorOutput));
        }
    }

    private function mysqldumpCommand(): array
    {
        $binary = $this->binary('mysqldump');
        $connection = $this->connection();
        $command = array_merge([$binary], $this->connectionArguments($connection), [
            '--single-transaction', '--quick', '--routines', '--triggers', '--events',
            '--hex-blob', '--no-tablespaces', '--default-character-set=utf8mb4', '--add-drop-table', '--databases',
            (string) $connection['database'],
        ]);

        $version = new Process([$binary, '--version']);
        $version->run();
        if (preg_match('/Distrib\s+8\./i', $version->getOutput())) {
            array_splice($command, -2, 0, ['--column-statistics=0']);
        }

        return $command;
    }

    private function mysqlCommand(): array
    {
        return array_merge([$this->binary('mysql')], $this->connectionArguments($this->connection()));
    }

    private function connectionArguments(array $connection): array
    {
        $arguments = [
            '--user=' . $connection['username'],
            '--default-character-set=utf8mb4',
        ];

        if (filled($connection['unix_socket'] ?? null)) {
            $arguments[] = '--socket=' . $connection['unix_socket'];
        } else {
            $arguments[] = '--host=' . $connection['host'];
            $arguments[] = '--port=' . $connection['port'];
        }

        return $arguments;
    }

    private function connection(): array
    {
        $connection = DB::connection()->getConfig();
        if (($connection['driver'] ?? null) !== 'mysql') {
            throw new RuntimeException('النسخ الاحتياطي الحالي يدعم قواعد MySQL فقط.');
        }

        foreach (['database', 'username'] as $field) {
            if (!filled($connection[$field] ?? null)) {
                throw new RuntimeException('إعداد اتصال قاعدة البيانات غير مكتمل: ' . $field);
            }
        }

        return array_replace(['host' => '127.0.0.1', 'port' => 3306, 'password' => '', 'unix_socket' => ''], $connection);
    }

    private function processEnvironment(): array
    {
        return ['MYSQL_PWD' => (string) ($this->connection()['password'] ?? '')];
    }

    private function binary(string $name): string
    {
        $configured = (string) config('database.backup_binaries.' . $name, '');
        $candidates = array_filter([
            $configured,
            '/Applications/MAMP/Library/bin/mysql80/bin/' . $name,
            '/Applications/MAMP/Library/bin/mysql57/bin/' . $name,
            '/usr/local/bin/' . $name,
            '/usr/bin/' . $name,
            '/opt/homebrew/bin/' . $name,
        ]);

        foreach ($candidates as $candidate) {
            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException("لم يتم العثور على أداة {$name} على الخادم.");
    }

    private function directory(): string
    {
        $r2 = R2StorageSettings::all();
        $backup = DatabaseBackupSettings::all();

        return trim(($r2['root_prefix'] ?: 'clickerp') . '/' . $backup['path'], '/');
    }

    private function storeLocalCopy(string $source, string $path): void
    {
        if (!DatabaseBackupSettings::all()['local_enabled']) {
            return;
        }

        $stream = fopen($source, 'rb');
        if (!is_resource($stream)) {
            throw new RuntimeException('تعذرت قراءة النسخة لحفظها على السيرفر.');
        }

        try {
            if (!Storage::disk('local')->writeStream($path, $stream)) {
                throw new RuntimeException('تعذر حفظ النسخة المحلية على السيرفر.');
            }
        } finally {
            fclose($stream);
        }
    }

    private function pruneR2(): void
    {
        $cutoff = now()->subDays(DatabaseBackupSettings::all()['retention_days'])->getTimestamp();
        foreach ($this->backups() as $backup) {
            if ($backup['created_at'] < $cutoff) {
                $this->disk()->delete($backup['key']);
            }
        }
    }

    private function pruneLocal(): void
    {
        $settings = DatabaseBackupSettings::all();
        $disk = Storage::disk('local');

        if (!$settings['local_enabled']) {
            return;
        }

        $cutoff = now()->subDays($settings['local_retention_days'])->getTimestamp();
        foreach ($this->localBackups() as $backup) {
            if ($backup['created_at'] < $cutoff) {
                $disk->delete($backup['path']);
            }
        }
    }

    private function assertValidKey(string $key): void
    {
        if (!Str::startsWith($key, $this->directory() . '/') || !preg_match(self::FILE_PATTERN, basename($key))) {
            throw new RuntimeException('مسار النسخة الاحتياطية غير صالح.');
        }
    }

    private function assertR2Ready(): void
    {
        if (!R2StorageSettings::isReady()) {
            throw new RuntimeException('يجب تفعيل R2 واختبار الاتصال بنجاح قبل استخدام النسخ الاحتياطي.');
        }
    }

    private function disk()
    {
        config(['filesystems.disks.database_backup_r2' => R2StorageSettings::diskConfig()]);
        Storage::forgetDisk('database_backup_r2');

        return Storage::disk('database_backup_r2');
    }
}
