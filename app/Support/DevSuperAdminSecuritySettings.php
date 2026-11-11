<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class DevSuperAdminSecuritySettings
{
    private static ?array $cached = null;

    public static function all(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        $defaults = [
            'email' => trim((string) config('dev_superadmin.verification.email')),
            'phone' => trim((string) config('dev_superadmin.verification.phone')),
            'password_hash' => null,
            'updated_at' => null,
        ];
        $path = self::path();
        if (!File::isFile($path)) {
            return self::$cached = $defaults;
        }

        try {
            $decoded = json_decode(Crypt::decryptString(File::get($path)), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            report($exception);
            throw new RuntimeException('تعذر قراءة ملف أمان Super Admin المشفر. لا يمكن تنفيذ العمليات الخطرة حتى إصلاحه.');
        }

        if (!is_array($decoded)) {
            throw new RuntimeException('محتوى ملف أمان Super Admin غير صالح.');
        }

        return self::$cached = array_replace($defaults, array_intersect_key($decoded, $defaults));
    }

    public static function matchesPassword(string $password): bool
    {
        $hash = (string) (self::all()['password_hash'] ?? '');
        if ($hash !== '') {
            return Hash::check($password, $hash);
        }

        $configured = (string) config('dev_superadmin.password');

        return $configured !== '' && hash_equals($configured, $password);
    }

    public static function persist(array $payload): array
    {
        $current = self::all();
        $settings = [
            'email' => trim((string) ($payload['email'] ?? '')),
            'phone' => trim((string) ($payload['phone'] ?? '')),
            'password_hash' => $current['password_hash'],
            'updated_at' => now()->toIso8601String(),
        ];

        $newPassword = (string) ($payload['password'] ?? '');
        if ($newPassword !== '') {
            $settings['password_hash'] = Hash::make($newPassword);
        }

        $path = self::path();
        File::ensureDirectoryExists(dirname($path), 0700, true);
        $temporaryPath = $path . '.tmp-' . Str::lower(Str::random(12));

        try {
            $encrypted = Crypt::encryptString(json_encode(
                $settings,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            ));
            if (File::put($temporaryPath, $encrypted, true) === false) {
                throw new RuntimeException('تعذر كتابة ملف أمان Super Admin.');
            }
            @chmod($temporaryPath, 0600);
            if (!@rename($temporaryPath, $path)) {
                throw new RuntimeException('تعذر اعتماد ملف أمان Super Admin الجديد.');
            }
            @chmod($path, 0600);
        } finally {
            if (File::exists($temporaryPath)) {
                File::delete($temporaryPath);
            }
        }

        return self::$cached = $settings;
    }

    public static function resetCache(): void
    {
        self::$cached = null;
    }

    private static function path(): string
    {
        return (string) config(
            'dev_superadmin.security_file',
            storage_path('app/private/dev-superadmin-security.enc')
        );
    }
}
