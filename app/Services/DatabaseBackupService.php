<?php

namespace App\Services;

use App\Support\DatabaseBackupSettings;
use App\Support\R2StorageSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    private const FILE_PATTERN = '/^clickerp-(?:scheduled|manual|uploaded|pre-restore|pre-import)-\d{8}-\d{6}-[a-z0-9]{8}\.sql\.gz$/';
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
                'terminal_command' => str_starts_with($filename, 'clickerp-uploaded-')
                    ? $this->localImportCommand($path)
                    : null,
            ];
        }

        usort($items, fn (array $a, array $b) => $b['created_at'] <=> $a['created_at']);

        return $items;
    }

    public function localImportCommandPrefix(): string
    {
        if ($this->terminalShell() === 'powershell') {
            return 'Set-Location ' . $this->terminalQuote(base_path())
                . '; & ' . $this->terminalQuote(PHP_BINARY)
                . ' artisan database:import-local ';
        }

        return 'cd ' . $this->terminalQuote(base_path())
            . ' && ' . $this->terminalQuote(PHP_BINARY)
            . ' artisan database:import-local ';
    }

    public function terminalShell(): string
    {
        return PHP_OS_FAMILY === 'Windows' ? 'powershell' : 'terminal';
    }

    public function localImportCommand(string $path): string
    {
        $filename = basename($path);
        if ($path !== self::LOCAL_DIRECTORY . '/' . $filename
            || !str_starts_with($filename, 'clickerp-uploaded-')
            || !preg_match(self::FILE_PATTERN, $filename)) {
            throw new RuntimeException('لا يمكن إنشاء أمر استيراد إلا لنسخة مرفوعة يدويًا صالحة.');
        }

        return $this->localImportCommandPrefix()
            . $this->terminalQuote(Storage::disk('local')->path($path));
    }

    private function terminalQuote(string $value): string
    {
        return $this->terminalShell() === 'powershell'
            ? "'" . str_replace("'", "''", $value) . "'"
            : escapeshellarg($value);
    }

    public function currentDatabaseDetails(): array
    {
        $connectionName = (string) config('database.default');
        $connection = DB::connection($connectionName);
        $configuration = $connection->getConfig();
        $details = [
            'connection' => $connectionName,
            'driver' => (string) ($configuration['driver'] ?? ''),
            'host' => (string) ($configuration['host'] ?? ''),
            'port' => (string) ($configuration['port'] ?? ''),
            'database' => (string) ($configuration['database'] ?? ''),
            'username' => (string) ($configuration['username'] ?? ''),
            'socket' => (string) ($configuration['unix_socket'] ?? ''),
            'charset' => (string) ($configuration['charset'] ?? ''),
            'password_configured' => filled($configuration['password'] ?? null),
            'connected' => false,
            'connected_database' => null,
            'server_version' => null,
            'error' => null,
        ];

        try {
            $selected = $connection->selectOne('SELECT DATABASE() AS database_name');
            $details['connected_database'] = $selected->database_name ?? null;
            $details['server_version'] = (string) $connection->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);
            $details['connected'] = true;
        } catch (\Throwable $exception) {
            report($exception);
            $details['error'] = 'تعذر تأكيد الاتصال بقاعدة البيانات حاليًا.';
        }

        return $details;
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

        // A restore can legitimately take longer than the web request's default
        // execution limit. Keep it running even if the browser/proxy disconnects.
        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }
        ignore_user_abort(true);

        Cache::lock('database-backup-operation', 7200)->block(5, function () use ($key): void {
            $disk = $this->disk();
            if (!$disk->exists($key)) {
                throw new RuntimeException('النسخة الاحتياطية المطلوبة غير موجودة في R2.');
            }

            // Always leave a verified recovery point containing the state
            // immediately before restore. Failure to create it aborts restore.
            $safetyBackup = $this->createWithoutLock('pre-restore');
            if (!$disk->exists($safetyBackup['key']) || $disk->size($safetyBackup['key']) <= 0) {
                throw new RuntimeException('لم يتم التحقق من نسخة الأمان السابقة للاستعادة؛ تم إلغاء الاستعادة دون تغيير قاعدة البيانات.');
            }

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

                $this->restoreLocalFile($temporaryPath, true);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
                @unlink($temporaryPath);
            }
        });
    }

    public function importUploaded(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if (!$file->isValid() || !is_string($path)) {
            throw new RuntimeException('ملف قاعدة البيانات المرفوع غير صالح أو لم يكتمل رفعه.');
        }

        $uploadedBackup = $this->storeUploadedBackup($path, $file->getClientOriginalName());
        try {
            $this->importFile(storage_path('app/' . $uploadedBackup['path']), $uploadedBackup['filename']);
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'تم حفظ الملف كنسخة محلية مرفوعة يدويًا باسم ' . $uploadedBackup['filename']
                . '، لكن تعذر استيراده: ' . $exception->getMessage(),
                0,
                $exception
            );
        }

        return $uploadedBackup;
    }

    public function importFile(string $filePath, ?string $originalName = null): void
    {
        $path = realpath($filePath);
        if ($path === false || !is_file($path) || !is_readable($path) || filesize($path) === 0) {
            throw new RuntimeException('ملف قاعدة البيانات غير موجود أو فارغ أو غير قابل للقراءة.');
        }

        $filename = strtolower($originalName ?: basename($path));
        if (!str_ends_with($filename, '.sql') && !str_ends_with($filename, '.sql.gz')) {
            throw new RuntimeException('الملفات المقبولة هي SQL أو SQL.GZ فقط.');
        }

        $handle = fopen($path, 'rb');
        if (!is_resource($handle)) {
            throw new RuntimeException('تعذرت قراءة ملف قاعدة البيانات.');
        }
        $magic = fread($handle, 2);
        fclose($handle);
        $isGzip = $magic === "\x1f\x8b";
        $expectsGzip = str_ends_with($filename, '.gz');
        if ($isGzip !== $expectsGzip) {
            throw new RuntimeException('امتداد الملف لا يطابق محتواه؛ ارفع ملف SQL أو SQL.GZ صالحًا.');
        }

        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }
        ignore_user_abort(true);

        Cache::lock('database-backup-operation', 7200)->block(5, function () use ($path, $isGzip): void {
            $safetyBackup = $this->createLocalSafetyBackup();
            $disk = Storage::disk('local');
            if (!$disk->exists($safetyBackup['path']) || $disk->size($safetyBackup['path']) <= 0) {
                throw new RuntimeException('لم يتم التحقق من نسخة الأمان المحلية السابقة للاستيراد؛ تم إلغاء العملية دون تغيير قاعدة البيانات.');
            }

            $this->restoreLocalFile($path, $isGzip);
        });
    }

    private function createLocalSafetyBackup(): array
    {
        $temporaryPath = tempnam(storage_path('app'), 'db-pre-import-');
        if ($temporaryPath === false) {
            throw new RuntimeException('تعذر إنشاء نسخة الأمان المحلية قبل الاستيراد.');
        }
        $gzipPath = $temporaryPath . '.sql.gz';

        try {
            $this->dumpDatabase($gzipPath);
            $filename = 'clickerp-pre-import-' . now()->format('Ymd-His') . '-' . Str::lower(Str::random(8)) . '.sql.gz';
            $path = self::LOCAL_DIRECTORY . '/' . $filename;
            $stream = fopen($gzipPath, 'rb');
            if (!is_resource($stream)) {
                throw new RuntimeException('تعذرت قراءة نسخة الأمان المحلية.');
            }

            try {
                if (!Storage::disk('local')->writeStream($path, $stream)) {
                    throw new RuntimeException('تعذر حفظ نسخة الأمان المحلية قبل الاستيراد.');
                }
            } finally {
                fclose($stream);
            }

            return ['path' => $path, 'filename' => $filename, 'size' => filesize($gzipPath) ?: 0];
        } finally {
            @unlink($gzipPath);
            @unlink($temporaryPath);
        }
    }

    private function storeUploadedBackup(string $sourcePath, string $originalName): array
    {
        $probe = fopen($sourcePath, 'rb');
        if (!is_resource($probe)) {
            throw new RuntimeException('تعذرت قراءة نسخة قاعدة البيانات المرفوعة.');
        }
        $isGzip = fread($probe, 2) === "\x1f\x8b";
        fclose($probe);
        if ($isGzip !== str_ends_with(strtolower($originalName), '.gz')) {
            throw new RuntimeException('امتداد الملف لا يطابق محتواه؛ ارفع ملف SQL أو SQL.GZ صالحًا.');
        }

        $temporaryPath = tempnam(storage_path('app'), 'db-uploaded-');
        if ($temporaryPath === false) {
            throw new RuntimeException('تعذر تجهيز النسخة المرفوعة للحفظ المحلي.');
        }
        $gzipPath = $temporaryPath . '.sql.gz';
        $source = null;
        $destination = null;

        try {
            $source = fopen($sourcePath, 'rb');
            if (!is_resource($source)) {
                throw new RuntimeException('تعذرت قراءة نسخة قاعدة البيانات المرفوعة.');
            }

            if ($isGzip) {
                $destination = fopen($gzipPath, 'wb');
                if (!is_resource($destination) || stream_copy_to_stream($source, $destination) === false) {
                    throw new RuntimeException('تعذر حفظ النسخة المرفوعة محليًا.');
                }
            } else {
                $destination = gzopen($gzipPath, 'wb9');
                if (!is_resource($destination)) {
                    throw new RuntimeException('تعذر ضغط نسخة SQL المرفوعة.');
                }

                while (!feof($source)) {
                    $chunk = fread($source, 1024 * 1024);
                    if ($chunk === false || ($chunk !== '' && gzwrite($destination, $chunk) === false)) {
                        throw new RuntimeException('تعذر ضغط نسخة SQL المرفوعة.');
                    }
                }
            }

            if ($isGzip) {
                fclose($destination);
            } else {
                gzclose($destination);
            }
            $destination = null;
            fclose($source);
            $source = null;

            $filename = 'clickerp-uploaded-' . now()->format('Ymd-His') . '-' . Str::lower(Str::random(8)) . '.sql.gz';
            $path = self::LOCAL_DIRECTORY . '/' . $filename;
            $stream = fopen($gzipPath, 'rb');
            if (!is_resource($stream)) {
                throw new RuntimeException('تعذرت قراءة النسخة المرفوعة بعد تجهيزها.');
            }

            try {
                if (!Storage::disk('local')->writeStream($path, $stream)) {
                    throw new RuntimeException('تعذر تسجيل النسخة المرفوعة ضمن النسخ المحلية.');
                }
            } finally {
                fclose($stream);
            }

            $size = Storage::disk('local')->size($path);
            if ($size <= 0) {
                Storage::disk('local')->delete($path);
                throw new RuntimeException('تم إنشاء ملف نسخة محلية فارغ؛ أُلغيت العملية.');
            }

            return [
                'path' => $path,
                'filename' => $filename,
                'size' => $size,
                'created_at' => now()->getTimestamp(),
            ];
        } finally {
            if (is_resource($source)) {
                fclose($source);
            }
            if (is_resource($destination)) {
                str_ends_with(strtolower($originalName), '.gz') ? fclose($destination) : gzclose($destination);
            }
            @unlink($gzipPath);
            @unlink($temporaryPath);
        }
    }

    private function restoreLocalFile(string $path, bool $gzip): void
    {
        $sql = $gzip ? gzopen($path, 'rb') : fopen($path, 'rb');
        if (!is_resource($sql)) {
            throw new RuntimeException($gzip
                ? 'ملف النسخة تالف أو ليس بصيغة SQL مضغوطة.'
                : 'تعذرت قراءة ملف SQL.');
        }

        $restoreInput = fopen('php://temp/maxmemory:5242880', 'w+b');
        if (!is_resource($restoreInput)) {
            fclose($sql);
            throw new RuntimeException('تعذر تجهيز محتوى النسخة للاستعادة.');
        }

        $connection = $this->connection();
        try {
            $this->prepareRestoreInput($sql, $restoreInput);
        } finally {
            $gzip ? gzclose($sql) : fclose($sql);
        }
        rewind($restoreInput);

        DB::disconnect();
        $process = new Process($this->mysqlCommand($connection), base_path(), $this->processEnvironment($connection));
        $process->setTimeout(7200);
        $process->setInput($restoreInput);
        $previousErrorHandler = null;
        $previousErrorHandler = set_error_handler(function (
            int $severity,
            string $message,
            string $file = '',
            int $line = 0
        ) use (&$previousErrorHandler): bool {
            if (str_contains($message, 'Broken pipe')) {
                return true;
            }

            return is_callable($previousErrorHandler)
                ? (bool) $previousErrorHandler($severity, $message, $file, $line)
                : false;
        });
        try {
            $process->run();
        } finally {
            restore_error_handler();
            fclose($restoreInput);
        }

        if (!$process->isSuccessful()) {
            throw new RuntimeException('فشلت استعادة قاعدة البيانات: ' . trim($process->getErrorOutput()));
        }
    }

    public function delete(string $key): void
    {
        $this->assertValidKey($key);
        $this->assertR2Ready();

        if (!$this->disk()->delete($key)) {
            throw new RuntimeException('تعذر حذف النسخة الاحتياطية من R2.');
        }
    }

    public function deleteLocalUploaded(string $path): void
    {
        $filename = basename($path);
        if ($path !== self::LOCAL_DIRECTORY . '/' . $filename
            || !str_starts_with($filename, 'clickerp-uploaded-')
            || !preg_match(self::FILE_PATTERN, $filename)) {
            throw new RuntimeException('يمكن حذف النسخ المرفوعة يدويًا فقط من هذا الإجراء.');
        }

        Cache::lock('database-backup-operation', 7200)->block(5, function () use ($path): void {
            $disk = Storage::disk('local');
            if (!$disk->exists($path)) {
                throw new RuntimeException('النسخة المحلية المطلوبة غير موجودة.');
            }

            if (!$disk->delete($path)) {
                throw new RuntimeException('تعذر حذف النسخة المحلية المرفوعة يدويًا.');
            }
        });
    }

    public function assertBackupExists(string $key): void
    {
        $this->assertValidKey($key);
        $this->assertR2Ready();

        if (!$this->disk()->exists($key)) {
            throw new RuntimeException('النسخة الاحتياطية المطلوبة غير موجودة في R2.');
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

            // Do not prune during the safety checkpoint: the selected restore
            // source may itself be older than the retention cutoff.

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
            '--hex-blob', '--no-tablespaces', '--default-character-set=utf8mb4', '--add-drop-table',
            (string) $connection['database'],
        ]);

        $version = new Process([$binary, '--version']);
        $version->run();
        if (preg_match('/Distrib\s+8\./i', $version->getOutput())) {
            array_splice($command, -2, 0, ['--column-statistics=0']);
        }

        return $command;
    }

    private function mysqlCommand(?array $connection = null): array
    {
        $connection ??= $this->connection();

        return array_merge([$this->binary('mysql')], $this->connectionArguments($connection), [
            '--database=' . $connection['database'],
        ]);
    }

    /**
     * Remove database-selection statements from old dumps created with
     * mysqldump --databases. The mysql client is always pinned to the database
     * configured for this application, so a production dump cannot create or
     * switch to its original database name during a restore.
     *
     * @param resource $source
     * @param resource $destination
     */
    private function prepareRestoreInput($source, $destination): void
    {
        while (($line = fgets($source)) !== false) {
            if (preg_match('/^\s*(?:CREATE|DROP|ALTER)\s+DATABASE\b.*;\s*$/i', $line)) {
                continue;
            }

            if (preg_match('/^\s*USE\s+(?:`(?:``|[^`])+`|[^;\s]+)\s*;\s*$/i', $line)) {
                continue;
            }

            if (fwrite($destination, $line) === false) {
                throw new RuntimeException('تعذر تجهيز محتوى النسخة للاستعادة.');
            }
        }

        if (!feof($source)) {
            throw new RuntimeException('تعذر فك ضغط النسخة الاحتياطية.');
        }
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

    private function processEnvironment(?array $connection = null): array
    {
        $connection ??= $this->connection();

        return ['MYSQL_PWD' => (string) ($connection['password'] ?? '')];
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
