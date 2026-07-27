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

    public function uploadMaidFile(UploadedFile $file, string $directory): string
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

    public function exists(?string $storedPath): bool
    {
        if (!$this->isR2Path($storedPath)) {
            return filled($storedPath) && Storage::disk('public')->exists($storedPath);
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
            return Storage::disk('public')->exists($storedPath)
                ? Storage::disk('public')->url($storedPath)
                : null;
        }

        try {
            $settings = R2StorageSettings::all();
            $path = $this->path($storedPath);

            if (filled($settings['public_url'])) {
                return rtrim($settings['public_url'], '/') . '/' . ltrim($path, '/');
            }

            return $this->r2Disk($settings)->temporaryUrl($path, now()->addMinutes(30));
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
            return $this->disk($storedPath)->delete($this->path($storedPath));
        } catch (\Throwable $exception) {
            report($exception);
            return false;
        }
    }

    public function inlineResponse(string $storedPath, string $filename, string $contentType): StreamedResponse
    {
        abort_unless($this->exists($storedPath), 404);
        $disk = $this->disk($storedPath);
        $path = $this->path($storedPath);

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

    private function disk(string $storedPath)
    {
        return $this->isR2Path($storedPath) ? $this->r2Disk() : Storage::disk('public');
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
