<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * صيغ وحدود حقول السائق (إضافة/تعديل).
 *
 * مصدر واحد يستخدمه DriverRequest للتحقق في الخادم، ونموذج السائق
 * (admin.hourly_drivers.form) للتحقق الفوري أثناء الكتابة.
 */
class HourlyDriverFieldRules
{
    /** عمر استخراج رخصة القيادة في المملكة، وأكبر عمر مقبول. */
    public const MIN_AGE = 18;
    public const MAX_AGE = 65;

    /** أبعد تاريخ انتهاء مقبول للوثائق (يمنع أخطاء الكتابة مثل 2099). */
    public const EXPIRY_MAX_YEARS = 10;

    public const NAME_MIN = 3;
    public const NAME_MAX = 100;
    /** حروف عربية أو إنجليزية، وكلمات تفصلها مسافة واحدة. */
    public const NAME_REGEX = '/^[\x{0621}-\x{063A}\x{0641}-\x{064A}A-Za-z]+( [\x{0621}-\x{063A}\x{0641}-\x{064A}A-Za-z]+)*$/u';

    public const EMPLOYEE_NUMBER_MAX = 15;
    public const EMPLOYEE_NUMBER_REGEX = '/^[0-9]{1,15}$/';

    /** جوال سعودي: 05 ثم 8 أرقام. */
    public const PHONE_REGEX = '/^05[0-9]{8}$/';

    public const EMAIL_MAX = 100;
    /** بريد بحروف إنجليزية فقط. */
    public const EMAIL_REGEX = '/^[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}$/';

    /** رقم الإقامة: 10 أرقام يبدأ بـ 2. */
    public const RESIDENCY_REGEX = '/^2[0-9]{9}$/';
    /** رقم الجواز: 6 إلى 12 حرفًا إنجليزيًا كبيرًا أو رقمًا. */
    public const PASSPORT_REGEX = '/^[A-Z0-9]{6,12}$/';
    /** رقم الحدود: 10 أرقام يبدأ بـ 3 أو 4. */
    public const BORDER_REGEX = '/^[34][0-9]{9}$/';

    public const PASSWORD_MIN = 8;
    public const PASSWORD_MAX = 72;

    public const ADDRESS_MAX = 500;

    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::today();
    }

    /** أحدث تاريخ ميلاد: أكمل MIN_AGE سنة. */
    public static function birthDateMax(): string
    {
        return self::today()->subYears(self::MIN_AGE)->toDateString();
    }

    /** أقدم تاريخ ميلاد: لم يتجاوز MAX_AGE سنة. */
    public static function birthDateMin(): string
    {
        return self::today()->subYears(self::MAX_AGE + 1)->addDay()->toDateString();
    }

    public static function expiryMax(): string
    {
        return self::today()->addYears(self::EXPIRY_MAX_YEARS)->toDateString();
    }

    /** يحوّل الأرقام العربية-الهندية إلى لاتينية. */
    public static function latinDigits(?string $value): ?string
    {
        return $value === null ? null : strtr($value, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
    }

    /** +9665xxxxxxxx أو 9665xxxxxxxx أو 5xxxxxxxx → 05xxxxxxxx */
    public static function normalizePhone(?string $value): ?string
    {
        if ($value === null) return null;
        $digits = preg_replace('/[\s()-]/', '', self::latinDigits(trim($value)));
        if (preg_match('/^(?:\+|00)?966(5[0-9]{8})$/', $digits, $m)) return '0'.$m[1];
        if (preg_match('/^5[0-9]{8}$/', $digits)) return '0'.$digits;
        return $digits;
    }

    /** القيم التي تحتاجها الواجهة. الصيغ هنا بصيغة JavaScript. */
    public static function clientConfig(): array
    {
        return [
            'minAge' => self::MIN_AGE,
            'maxAge' => self::MAX_AGE,
            'today' => self::today()->toDateString(),
            'birthDateMin' => self::birthDateMin(),
            'birthDateMax' => self::birthDateMax(),
            'expiryMax' => self::expiryMax(),
            'nameMin' => self::NAME_MIN,
            'nameMax' => self::NAME_MAX,
            'employeeNumberMax' => self::EMPLOYEE_NUMBER_MAX,
            'emailMax' => self::EMAIL_MAX,
            'passwordMin' => self::PASSWORD_MIN,
            'passwordMax' => self::PASSWORD_MAX,
            'addressMax' => self::ADDRESS_MAX,
        ];
    }
}
