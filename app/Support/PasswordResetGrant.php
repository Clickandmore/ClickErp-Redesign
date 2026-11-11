<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * إذن تغيير كلمة مرور العميل.
 *
 * كانت شاشات/واجهات "نسيت كلمة المرور" تغيّر كلمة المرور لأي رقم جوال يُرسل،
 * بلا أي دليل على أن المرسل استلم كود التحقق. الآن يُسجَّل الرقم هنا فقط بعد
 * نجاح كود التحقق، ويُستهلك مرة واحدة عند تغيير كلمة المرور.
 */
class PasswordResetGrant
{
    private const TTL_MINUTES = 15;

    public static function grant(?string $phone): void
    {
        $key = self::key($phone);

        if ($key) {
            self::store()->put($key, true, now()->addMinutes(self::TTL_MINUTES));
        }
    }

    public static function has(?string $phone): bool
    {
        $key = self::key($phone);

        return $key !== null && self::store()->has($key);
    }

    /** يتحقق من الإذن ويحذفه حتى لا يُستخدم مرتين. */
    public static function consume(?string $phone): bool
    {
        $key = self::key($phone);

        return $key !== null && self::store()->pull($key) === true;
    }

    // الإذن يُسجَّل في طلب (تأكيد الكود) ويُقرأ في طلب آخر، وكاش array لا يحفظ شيئاً بين الطلبات
    private static function store(): \Illuminate\Contracts\Cache\Repository
    {
        return config('cache.default') === 'array' && !app()->runningUnitTests()
            ? Cache::store('file')
            : Cache::store();
    }

    private static function key(?string $phone): ?string
    {
        // نفس الرقم قد يصل بصيغ مختلفة (مسافات، 0 أو 966 في البداية)
        $digits = ltrim(preg_replace('/\D+/', '', (string) $phone), '0');
        $digits = preg_replace('/^966(?=\d{9}$)/', '', $digits);

        return $digits === '' ? null : 'password-reset-grant:' . hash('sha256', $digits);
    }
}
