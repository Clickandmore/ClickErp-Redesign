<?php

namespace App\Support;

use Illuminate\Support\Facades\{DB, Hash, Schema};
use Illuminate\Validation\ValidationException;

/**
 * رمز التحقق لدخول تطبيق الفريق الميداني (سائق/عاملة).
 *
 * الرمز يُخزَّن مُعمّى (hash) في hourly_staff_otps بصلاحية staff_app_otp_minutes ومحاولات محدودة،
 * ويُرسل بنفس قالب الرسائل المستخدم لرمز العميل. في البيئات التي الرسائل فيها معطّلة
 * يُرجَع الرمز في الاستجابة (debug_code) حتى يمكن الاختبار بلا بوابة رسائل.
 */
class HourlyStaffOtp
{
    public const DRIVER = 'driver';
    public const WORKER = 'worker';
    private const MAX_ATTEMPTS = 5;
    private const RESEND_SECONDS = 60;

    /** 05XXXXXXXX من أي صيغة يرسلها التطبيق. */
    public static function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        foreach (['00966', '966'] as $prefix) {
            if (str_starts_with($digits, $prefix)) { $digits = substr($digits, strlen($prefix)); break; }
        }
        if (strlen($digits) === 9 && str_starts_with($digits, '5')) $digits = '0'.$digits;
        return $digits;
    }

    /** كل الصيغ التي قد يكون الرقم مخزّنًا بها في قاعدة البيانات. */
    public static function variants(string $phone): array
    {
        $local = self::normalize($phone);
        $bare = ltrim($local, '0');
        return array_values(array_unique(array_filter([$local, $bare, '966'.$bare, '+966'.$bare, '00966'.$bare])));
    }

    /** @return array{sent:bool,debug_code:?string} */
    public static function issue(string $guard, string $phone, ?int $staffId): array
    {
        self::assertTable();
        $phone = self::normalize($phone);
        $row = DB::table('hourly_staff_otps')->where('guard', $guard)->where('phone', $phone)->first();
        if ($row && $row->consumed_at === null && strtotime((string) $row->created_at) > now()->subSeconds(self::RESEND_SECONDS)->timestamp) {
            self::reject('تم إرسال رمز قبل قليل. انتظر دقيقة ثم أعد المحاولة.');
        }

        // نفس سلوك رمز العميل: رمز ثابت من config('otp.fallback_code') لأرقام مسموح بها (أو للكل إن كانت القائمة فارغة)،
        // وإلا رمز عشوائي يُرسل بالرسائل. بلا بوابة رسائل مفعّلة يُستخدم الرمز الثابت ويُرجَع في الاستجابة.
        // «مفعّل» = توجد قناة جاهزة لرمز التحقق (SMS أو واتساب عبر المزود المربوط)، لا بوابة SMS وحدها.
        $smsOn = (\App\Support\CommunicationSettings::verificationChannelIsReady() && !\App\Support\CommunicationSettings::phoneVerificationIsDisabled());
        $configured = (string) (config('otp.fallback_code') ?: '');
        $allowed = (array) config('otp.fallback_phones', []);
        $useFallback = $configured !== '' && (!$allowed || in_array($phone, $allowed, true) || in_array(ltrim($phone, '0'), $allowed, true));
        $fallback = $configured !== '' ? $configured : '12345';
        $code = $smsOn && !$useFallback ? sprintf('%05d', random_int(0, 99999)) : $fallback;
        $reviewCode = HourlyAppReviewAccounts::code($guard, $phone, $staffId);
        if ($reviewCode !== null) {
            $code = $reviewCode;
            $useFallback = true;
        }
        DB::table('hourly_staff_otps')->updateOrInsert(
            ['guard' => $guard, 'phone' => $phone],
            ['staff_id' => $staffId, 'code_hash' => Hash::make($code), 'expires_at' => now()->addMinutes((int) HourlyRentalSettings::get('staff_app_otp_minutes', 10)),
                'attempts' => 0, 'consumed_at' => null, 'created_at' => now(), 'updated_at' => now()]
        );

        $sent = false;
        if ($smsOn && !$useFallback) {
            try {
                $delivery = app(\App\Services\VerificationCodeSender::class)->send($phone, $code, 'hourly_staff_login',
                    ['website_name' => config('app.url'), 'company_name' => config('app.name')]);
                $sent = (bool) ($delivery['success'] ?? false);
            } catch (\Throwable $e) {
                $sent = false;
            }
        }

        // الرمز يُرجَع فقط حين لا يُرسل بالرسائل (بيئة اختبار أو رمز ثابت مسموح به).
        return ['sent' => $sent, 'debug_code' => $smsOn && !$useFallback ? null : $code];
    }

    /** يستهلك الرمز عند صحته، ويرفض برسالة واضحة عند خطئه أو انتهائه. */
    public static function consume(string $guard, string $phone, string $code): void
    {
        self::assertTable();
        $phone = self::normalize($phone);
        $row = DB::table('hourly_staff_otps')->where('guard', $guard)->where('phone', $phone)->first();
        if (!$row || $row->consumed_at !== null) self::reject('اطلب رمز تحقق جديدًا أولًا.');
        if (now()->gt($row->expires_at)) self::reject('انتهت صلاحية الرمز. اطلب رمزًا جديدًا.');
        if ((int) $row->attempts >= self::MAX_ATTEMPTS) self::reject('تم تجاوز عدد المحاولات. اطلب رمزًا جديدًا.');
        if (!Hash::check($code, $row->code_hash)) {
            DB::table('hourly_staff_otps')->where('id', $row->id)->update(['attempts' => (int) $row->attempts + 1, 'updated_at' => now()]);
            self::reject('الرمز غير صحيح.');
        }
        DB::table('hourly_staff_otps')->where('id', $row->id)->update(['consumed_at' => now(), 'updated_at' => now()]);
    }

    private static function assertTable(): void
    {
        abort_unless(Schema::hasTable('hourly_staff_otps'), 503, 'دخول تطبيق الفريق غير مهيأ على الخادم.');
    }

    private static function reject(string $message): void
    {
        throw ValidationException::withMessages(['phone' => $message]);
    }
}
