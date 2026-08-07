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

        try {
            [$disk, $path] = $this->readLocation($storedPath);

            return $disk->exists($path);
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

        try {
            $settings = R2StorageSettings::all();

            if ($this->isR2Path($storedPath)) {
                return $this->r2Url($this->path($storedPath), $settings);
            }

            // Older database rows keep a local-looking path even after their file
            // has been copied to R2. Prefer that R2 object for browser-facing URLs,
            // just as readLocation() already does for streams and file contents.
            if (R2StorageSettings::isReady($settings)) {
                $disk = $this->r2Disk($settings);
                $path = $this->legacyR2Path($storedPath, $settings);

                if ($disk->exists($path)) {
                    return $this->r2Url($path, $settings, $disk);
                }
            }

            return asset('storage/' . ltrim($storedPath, '/'));
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

        if ($this->isR2Path($storedPath)) {
            try {
                return $this->r2Disk()->delete($this->path($storedPath));
            } catch (\Throwable $exception) {
                report($exception);

                return false;
            }
        }

        // Legacy paths can exist in both places during migration. Delete both so
        // the local fallback cannot make a deleted R2 file reappear.
        $settings = R2StorageSettings::all();
        $deletedFromR2 = true;

        if (R2StorageSettings::isReady($settings)) {
            $deletedFromR2 = $this->deleteIfExists(
                $this->r2Disk($settings),
                $this->legacyR2Path($storedPath, $settings)
            );
        }

        $deletedLocally = $this->deleteIfExists(Storage::disk('public'), $storedPath);

        return $deletedFromR2 && $deletedLocally;
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

    /**
     * يرجع مؤشر "r2://..." لمسار محلي قديم **إن كان الملف موجودًا فعلًا في الـ Bucket**.
     *
     * يفيد بعد رفع الملفات إلى R2 يدويًا: لا حاجة لإعادة رفعها، فقط لربط قيمة العمود بها.
     */
    public function existingR2Pointer(string $storedPath): ?string
    {
        if ($this->isR2Path($storedPath)) {
            return $storedPath;
        }

        $settings = R2StorageSettings::all();

        if (!R2StorageSettings::isReady($settings)) {
            return null;
        }

        try {
            $path = $this->legacyR2Path($storedPath, $settings);

            return $this->r2Disk($settings)->exists($path) ? self::R2_PREFIX . $path : null;
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * المفتاح الذي يتوقعه النظام داخل الـ Bucket لمسار محلي قديم — بصرف النظر عن وجوده.
     * يفيد في تشخيص الرفع اليدوي: قارن هذا بما رفعته فعلًا.
     */
    public function expectedR2Key(string $storedPath): string
    {
        return $this->isR2Path($storedPath)
            ? $this->path($storedPath)
            : $this->legacyR2Path($storedPath, R2StorageSettings::all());
    }

    /**
     * ينقل ملفًا محليًا قديمًا إلى R2 ويرجع المؤشر الجديد "r2://..." ليُحفظ في القاعدة.
     *
     * الملف المحلي يبقى مكانه (نسخ لا نقل) حتى يمكن التراجع، ولا يُعاد الرفع لو كان
     * الملف موجودًا في الـ Bucket أصلًا.
     *
     * @throws \RuntimeException إذا لم يكن R2 جاهزًا أو الملف غير موجود لا محليًا ولا على R2
     */
    public function migrateLocalFileToR2(string $storedPath): string
    {
        if ($this->isR2Path($storedPath)) {
            return $storedPath;
        }

        $settings = R2StorageSettings::all();

        if (!R2StorageSettings::isReady($settings)) {
            throw new \RuntimeException('تخزين R2 غير مفعّل أو لم يتم التحقق منه.');
        }

        $disk = $this->r2Disk($settings);
        $path = $this->legacyR2Path($storedPath, $settings);

        if ($disk->exists($path)) {
            return self::R2_PREFIX . $path;
        }

        $local = Storage::disk('public');

        if (!$local->exists($storedPath)) {
            throw new \RuntimeException('الملف غير موجود محليًا ولا على R2: ' . $storedPath);
        }

        $stream = $local->readStream($storedPath);

        if (!is_resource($stream)) {
            throw new \RuntimeException('تعذر قراءة الملف المحلي: ' . $storedPath);
        }

        try {
            $disk->writeStream($path, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return self::R2_PREFIX . $path;
    }

    private function path(string $storedPath): string
    {
        return $this->isR2Path($storedPath) ? substr($storedPath, strlen(self::R2_PREFIX)) : $storedPath;
    }

    /**
     * @return array{0: mixed, 1: string, 2: bool}
     */
    private function readLocation(string $storedPath): array
    {
        if ($this->isR2Path($storedPath)) {
            return [$this->r2Disk(), $this->path($storedPath), true];
        }

        $settings = R2StorageSettings::all();
        if (R2StorageSettings::isReady($settings)) {
            try {
                $disk = $this->r2Disk($settings);
                $path = $this->legacyR2Path($storedPath, $settings);

                if ($disk->exists($path)) {
                    return [$disk, $path, true];
                }
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return [Storage::disk('public'), $storedPath, false];
    }

    private function legacyR2Path(string $storedPath, array $settings): string
    {
        return trim($settings['root_prefix'] . '/' . ltrim($storedPath, '/'), '/');
    }

    private function r2Url(string $path, array $settings, $disk = null): string
    {
        if (filled($settings['public_url'])) {
            return rtrim($settings['public_url'], '/') . '/' . ltrim($path, '/');
        }

        return ($disk ?? $this->r2Disk($settings))->temporaryUrl($path, now()->addMinutes(30));
    }

    private function deleteIfExists($disk, string $path): bool
    {
        try {
            return !$disk->exists($path) || $disk->delete($path);
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
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
