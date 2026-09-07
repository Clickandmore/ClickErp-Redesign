<?php

namespace App\Support;

use App\Models\NewSettings;
use App\Services\Firebase\FirebaseCredentials;
use App\Services\Firebase\FirebaseException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * إعدادات إشعارات Firebase القابلة للتعديل من لوحة التحكم.
 *
 * الطبقات: قيم config/firebase.php (المقروءة من .env) هي الافتراضي، وما يُحفظ
 * في جدول new_settings يعلوها. هكذا يعمل النظام بلا أي صف في القاعدة، ويظل
 * ملف .env مرجعاً للقيم الأولية عند نشر بيئة جديدة.
 */
class FirebaseSettings
{
    public const SETTINGS_KEY = 'firebase_settings';
    public const CACHE_KEY = 'firebase_settings.payload';

    /** المفاتيح القابلة للتعديل من الواجهة ونوع كل منها. */
    public const FIELDS = [
        'enabled' => 'bool',
        'project_id' => 'string',
        'timeout' => 'int',
        'concurrency' => 'int',
        'token_cache_ttl' => 'int',
        'prune_invalid_tokens' => 'bool',
        'android_priority' => 'string',
        'android_channel_id' => 'string',
        'android_sound' => 'string',
        'apns_sound' => 'string',
    ];

    /**
     * القيم الافتراضية من config/firebase.php أي من .env.
     */
    public static function defaults(): array
    {
        return [
            'enabled' => (bool) config('firebase.enabled', true),
            'project_id' => (string) config('firebase.project_id', ''),
            'timeout' => (int) config('firebase.timeout', 15),
            'concurrency' => (int) config('firebase.concurrency', 20),
            'token_cache_ttl' => (int) config('firebase.token_cache_ttl', 3300),
            'prune_invalid_tokens' => (bool) config('firebase.prune_invalid_tokens', true),
            'android_priority' => (string) config('firebase.android.priority', 'high'),
            'android_channel_id' => (string) config('firebase.android.channel_id', 'default_channel'),
            'android_sound' => (string) config('firebase.android.sound', 'default'),
            'apns_sound' => (string) config('firebase.apns.sound', 'default'),
        ];
    }

    /**
     * القيم الفعلية: الافتراضي مدموجاً فوقه ما هو محفوظ في القاعدة.
     */
    public static function all(): array
    {
        return array_merge(self::defaults(), self::stored());
    }

    public static function get(string $key, $default = null)
    {
        $values = self::all();

        return array_key_exists($key, $values) ? $values[$key] : $default;
    }

    /**
     * يدفع القيم المحفوظة إلى config('firebase.*') حتى تستفيد منها خدمات
     * الإرسال القائمة دون تعديل أي سطر فيها.
     */
    public static function applyToConfig(): void
    {
        $values = self::all();

        config([
            'firebase.enabled' => $values['enabled'],
            'firebase.project_id' => $values['project_id'],
            'firebase.timeout' => $values['timeout'],
            'firebase.concurrency' => $values['concurrency'],
            'firebase.token_cache_ttl' => $values['token_cache_ttl'],
            'firebase.prune_invalid_tokens' => $values['prune_invalid_tokens'],
            'firebase.android.priority' => $values['android_priority'],
            'firebase.android.channel_id' => $values['android_channel_id'],
            'firebase.android.sound' => $values['android_sound'],
            'firebase.apns.sound' => $values['apns_sound'],
        ]);
    }

    public static function save(array $values): void
    {
        $clean = [];

        foreach (self::FIELDS as $key => $type) {
            if (!array_key_exists($key, $values)) {
                continue;
            }

            $clean[$key] = match ($type) {
                'bool' => (bool) filter_var($values[$key], FILTER_VALIDATE_BOOLEAN),
                'int' => (int) $values[$key],
                default => trim((string) $values[$key]),
            };
        }

        NewSettings::query()->updateOrCreate(
            ['key' => self::SETTINGS_KEY],
            ['value' => json_encode($clean, JSON_UNESCAPED_UNICODE)]
        );

        self::clearCache();
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * مسار ملف حساب الخدمة كما هو في الإعدادات، محوّلاً إلى مسار مطلق.
     */
    public static function credentialsPath(): string
    {
        $path = (string) config('firebase.credentials', 'storage/app/firebase/service-account.json');

        $isAbsolute = str_starts_with($path, DIRECTORY_SEPARATOR)
            || str_starts_with($path, '/')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;

        return $isAbsolute ? $path : base_path($path);
    }

    public static function credentialsExist(): bool
    {
        $path = self::credentialsPath();

        return $path !== '' && is_readable($path);
    }

    /**
     * حالة ملف حساب الخدمة لعرضها في الواجهة: هل هو موجود وصالح، ولأي مشروع.
     *
     * @return array{exists: bool, valid: bool, path: string, project_id: ?string, client_email: ?string, error: ?string, updated_at: ?string}
     */
    public static function credentialsStatus(): array
    {
        $path = self::credentialsPath();
        $status = [
            'exists' => false,
            'valid' => false,
            'path' => $path,
            'project_id' => null,
            'client_email' => null,
            'error' => null,
            'updated_at' => null,
        ];

        if (!self::credentialsExist()) {
            $status['error'] = 'لم يتم رفع ملف حساب الخدمة بعد.';

            return $status;
        }

        $status['exists'] = true;
        $status['updated_at'] = date('Y-m-d H:i', (int) filemtime($path));

        try {
            $credentials = FirebaseCredentials::fromConfig();
            $status['valid'] = true;
            $status['project_id'] = $credentials->projectId();
            $status['client_email'] = $credentials->clientEmail();
        } catch (FirebaseException $e) {
            $status['error'] = $e->getMessage();
        } catch (Throwable $e) {
            $status['error'] = 'تعذر قراءة ملف حساب الخدمة: ' . $e->getMessage();
        }

        return $status;
    }

    /**
     * يتحقق من أن المحتوى المرفوع ملف حساب خدمة صالح قبل حفظه.
     *
     * @return array{ok: bool, error: ?string, project_id: ?string, client_email: ?string}
     */
    public static function validateCredentialsJson(string $contents): array
    {
        $decoded = json_decode($contents, true);

        if (!is_array($decoded)) {
            return ['ok' => false, 'error' => 'الملف ليس بصيغة JSON صالحة.', 'project_id' => null, 'client_email' => null];
        }

        foreach (['client_email', 'private_key', 'project_id'] as $key) {
            if (blank($decoded[$key] ?? null)) {
                return ['ok' => false, 'error' => "الملف ينقصه المفتاح [{$key}] — تأكد أنه ملف Service Account وليس ملف إعدادات التطبيق.", 'project_id' => null, 'client_email' => null];
            }
        }

        if (openssl_pkey_get_private((string) $decoded['private_key']) === false) {
            return ['ok' => false, 'error' => 'المفتاح الخاص داخل الملف غير صالح.', 'project_id' => null, 'client_email' => null];
        }

        return [
            'ok' => true,
            'error' => null,
            'project_id' => (string) $decoded['project_id'],
            'client_email' => (string) $decoded['client_email'],
        ];
    }

    /**
     * القيم المحفوظة في القاعدة فقط (بلا الافتراضي).
     */
    protected static function stored(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            // أثناء migrate على قاعدة فارغة لا يوجد الجدول بعد.
            if (!Schema::hasTable('new_settings')) {
                return [];
            }

            $raw = NewSettings::query()->where('key', self::SETTINGS_KEY)->value('value');
            $decoded = json_decode((string) $raw, true);

            if (!is_array($decoded)) {
                return [];
            }

            $clean = [];

            foreach (self::FIELDS as $key => $type) {
                if (!array_key_exists($key, $decoded)) {
                    continue;
                }

                $clean[$key] = match ($type) {
                    'bool' => (bool) $decoded[$key],
                    'int' => (int) $decoded[$key],
                    default => (string) $decoded[$key],
                };
            }

            return $clean;
        });
    }
}
