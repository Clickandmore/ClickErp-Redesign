<?php

namespace App\Services\Firebase;

/**
 * يقرأ ملف حساب الخدمة (Service Account JSON) ويتحقق من صلاحيته.
 */
class FirebaseCredentials
{
    private string $projectId;
    private string $clientEmail;
    private string $privateKey;

    public function __construct(string $projectId, string $clientEmail, string $privateKey)
    {
        $this->projectId = $projectId;
        $this->clientEmail = $clientEmail;
        $this->privateKey = $privateKey;
    }

    public static function fromConfig(): self
    {
        $path = self::resolvePath((string) config('firebase.credentials'));

        if (!is_readable($path)) {
            throw new FirebaseException("ملف حساب خدمة Firebase غير موجود أو غير قابل للقراءة: {$path}");
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (!is_array($decoded)) {
            throw new FirebaseException("ملف حساب خدمة Firebase ليس JSON صالحاً: {$path}");
        }

        $clientEmail = (string) ($decoded['client_email'] ?? '');
        $privateKey = (string) ($decoded['private_key'] ?? '');
        $projectId = (string) (config('firebase.project_id') ?: ($decoded['project_id'] ?? ''));

        foreach (['client_email' => $clientEmail, 'private_key' => $privateKey, 'project_id' => $projectId] as $key => $value) {
            if ($value === '') {
                throw new FirebaseException("مفتاح [{$key}] مفقود في إعدادات Firebase.");
            }
        }

        return new self($projectId, $clientEmail, $privateKey);
    }

    /**
     * يقبل مساراً مطلقاً أو نسبياً من جذر المشروع.
     */
    private static function resolvePath(string $path): string
    {
        if ($path === '') {
            return '';
        }

        $isAbsolute = str_starts_with($path, DIRECTORY_SEPARATOR)
            || str_starts_with($path, '/')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;

        return $isAbsolute ? $path : base_path($path);
    }

    public function projectId(): string
    {
        return $this->projectId;
    }

    public function clientEmail(): string
    {
        return $this->clientEmail;
    }

    public function privateKey(): string
    {
        return $this->privateKey;
    }

    /**
     * بصمة تُستخدم كجزء من مفتاح الكاش، حتى لا يُعاد استخدام توكن حساب خدمة
     * قديم بعد تدوير المفاتيح.
     */
    public function fingerprint(): string
    {
        return substr(hash('sha256', $this->clientEmail . '|' . $this->projectId), 0, 16);
    }
}
