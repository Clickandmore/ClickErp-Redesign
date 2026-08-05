<?php

namespace App\Services;

use App\Support\R2StorageSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManagedFileStorage
{
    public const R2_PREFIX = 'r2://';
    private static ?string $configuredDiskHash = null;

    public function uploadFile(UploadedFile $file, string $directory): string
    {
        $directory = trim($directory, '/');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $filename = Str::random(10) . time() . ($extension !== '' ? '.' . $extension : '');

        if (!R2StorageSettings::isReady()) {
            return $file->storeAs($directory, $filename, 'public');
        }

        $settings = R2StorageSettings::all();
        $path = trim($settings['root_prefix'] . '/' . $directory . '/' . $filename, '/');
        $this->r2Disk($settings)->putFileAs(dirname($path), $file, basename($path));

        return self::R2_PREFIX . $path;
    }

    public function uploadMaidFile(UploadedFile $file, string $directory): string
    {
        return $this->uploadFile($file, $directory);
    }

    public function storeFileContents(string $contents, string $directory, string $extension = ''): string
    {
        $directory = trim($directory, '/');
        $extension = strtolower(trim($extension, " \t\n\r\0\x0B."));
        $filename = Str::random(10) . time() . ($extension !== '' ? '.' . $extension : '');

        if (!R2StorageSettings::isReady()) {
            Storage::disk('public')->put($directory . '/' . $filename, $contents);

            return $directory . '/' . $filename;
        }

        $settings = R2StorageSettings::all();
        $path = trim($settings['root_prefix'] . '/' . $directory . '/' . $filename, '/');
        $this->r2Disk($settings)->put($path, $contents);

        return self::R2_PREFIX . $path;
    }

    public function storeMaidFileContents(string $contents, string $directory, string $extension = ''): string
    {
        return $this->storeFileContents($contents, $directory, $extension);
    }

    public function exists(?string $storedPath): bool
    {
        if (!filled($storedPath)) {
            return false;
        }

        if (!$this->isR2Path($storedPath)) {
            if (Storage::disk('public')->exists($storedPath)) {
                return true;
            }

            if (!R2StorageSettings::isReady()) {
                return false;
            }

            try {
                return $this->r2Disk()->exists($this->legacyR2Path($storedPath));
            } catch (\Throwable $exception) {
                report($exception);
                return false;
            }
        }

        try {
            return $this->r2Disk()->exists($this->path($storedPath));
        } catch (\Throwable $exception) {
            report($exception);
            return false;
        }
    }

    public function url(?string $storedPath): ?string
    {
        if (!filled($storedPath)) {
            return null;
        }

        if (!$this->isR2Path($storedPath)) {
            if (Storage::disk('public')->exists($storedPath)) {
                return asset('storage/' . ltrim($storedPath, '/'));
            }

            if (!R2StorageSettings::isReady()) {
                return null;
            }

            // Some pre-R2 records retain their original relative path even after
            // the file was copied below root_prefix in the R2 bucket.
            return $this->r2Url($this->legacyR2Path($storedPath));
        }

        try {
            return $this->r2Url($this->path($storedPath));
        } catch (\Throwable $exception) {
            report($exception);
            return null;
        }
    }

    public function delete(?string $storedPath): bool
    {
        if (!filled($storedPath)) {
            return false;
        }

        try {
            [$disk, $path] = $this->readLocation($storedPath);

            return $disk->delete($path);
        } catch (\Throwable $exception) {
            report($exception);
            return false;
        }
    }

    public function inlineResponse(string $storedPath, string $filename, string $contentType): StreamedResponse
    {
        abort_unless($this->exists($storedPath), 404);
        [$disk, $path] = $this->readLocation($storedPath);

        return response()->stream(function () use ($disk, $path): void {
            $stream = $disk->readStream($path);
            if (!is_resource($stream)) {
                return;
            }
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'inline; filename="' . str_replace(['"', "\r", "\n"], '', $filename) . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function downloadResponse(string $storedPath, string $filename, string $contentType): StreamedResponse
    {
        abort_unless($this->exists($storedPath), 404);
        [$disk, $path] = $this->readLocation($storedPath);

        return response()->stream(function () use ($disk, $path): void {
            $stream = $disk->readStream($path);
            if (!is_resource($stream)) {
                return;
            }
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . str_replace(['"', "\r", "\n"], '', $filename) . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * @return resource|false
     */
    public function readStream(string $storedPath)
    {
        [$disk, $path] = $this->readLocation($storedPath);

        return $disk->readStream($path);
    }

    public function mimeType(string $storedPath): string
    {
        [$disk, $path] = $this->readLocation($storedPath);

        return $disk->mimeType($path) ?: 'application/octet-stream';
    }

    public function contents(string $storedPath): string
    {
        if (!$this->exists($storedPath)) {
            throw new \RuntimeException('الملف المطلوب غير موجود.');
        }

        [$disk, $path] = $this->readLocation($storedPath);

        return $disk->get($path);
    }

    public function testConnection(?array $settings = null): void
    {
        $settings ??= R2StorageSettings::all();
        $disk = $this->r2Disk($settings);
        $path = trim(($settings['root_prefix'] ?: 'clickerp') . '/connection-tests/' . Str::uuid() . '.txt', '/');
        $contents = 'ClickERP R2 connection test ' . now()->toIso8601String();

        $disk->put($path, $contents);
        if (!$disk->exists($path) || $disk->get($path) !== $contents) {
            throw new \RuntimeException('تعذر التحقق من كتابة وقراءة الملف التجريبي.');
        }
        $disk->delete($path);
    }

    public function isR2Path(?string $storedPath): bool
    {
        return is_string($storedPath) && str_starts_with($storedPath, self::R2_PREFIX);
    }

    private function path(string $storedPath): string
    {
        return $this->isR2Path($storedPath) ? substr($storedPath, strlen(self::R2_PREFIX)) : $storedPath;
    }

    private function legacyR2Path(string $storedPath, ?array $settings = null): string
    {
        $settings ??= R2StorageSettings::all();
        $path = ltrim($storedPath, '/');
        $prefix = trim((string) ($settings['root_prefix'] ?? ''), '/');

        if ($prefix === '' || $path === $prefix || str_starts_with($path, $prefix . '/')) {
            return $path;
        }

        return $prefix . '/' . $path;
    }

    private function r2Url(string $path): ?string
    {
        try {
            $settings = R2StorageSettings::all();

            if (filled($settings['public_url'])) {
                return rtrim($settings['public_url'], '/') . '/' . ltrim($path, '/');
            }

            return $this->r2Disk($settings)->temporaryUrl($path, now()->addMinutes(30));
        } catch (\Throwable $exception) {
            report($exception);
            return null;
        }
    }

    private function readLocation(string $storedPath): array
    {
        if ($this->isR2Path($storedPath)) {
            return [$this->r2Disk(), $this->path($storedPath)];
        }

        if (Storage::disk('public')->exists($storedPath) || !R2StorageSettings::isReady()) {
            return [Storage::disk('public'), $storedPath];
        }

        return [$this->r2Disk(), $this->legacyR2Path($storedPath)];
    }

    private function r2Disk(?array $settings = null)
    {
        $config = R2StorageSettings::diskConfig($settings);
        $hash = hash('sha256', serialize($config));

        if (self::$configuredDiskHash !== $hash) {
            config(['filesystems.disks.r2_runtime' => $config]);
            Storage::forgetDisk('r2_runtime');
            self::$configuredDiskHash = $hash;
        }

        return Storage::disk('r2_runtime');
    }
}
