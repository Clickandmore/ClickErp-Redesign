<?php

namespace App\Support;

/**
 * تطبيع جوالات العملاء إلى الصيغة التي يسجّل بها تطبيق العميل الدخول: 5XXXXXXXX (9 أرقام، السعودية).
 * يزيل رموز الاتجاه المخفية (⁦ ⁩ وغيرها) والمسافات والرموز، ويحوّل الأرقام العربية، ويحذف 00/966 المكررة و0 البادئة.
 * مثال: "+966 ⁦+966 55 298 7588⁩" و"+966 966538270564" و"0552987588" كلها تصبح 5XXXXXXXX.
 */
class PhoneNumber
{
    /** أرقام فقط بعد إزالة الرموز غير المرئية وتحويل الأرقام العربية والفارسية. */
    public static function digits(?string $raw): string
    {
        $text = preg_replace('/\p{Cf}/u', '', (string) $raw);
        $text = strtr($text, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9']);

        return preg_replace('/\D/', '', $text);
    }

    /** الجوال السعودي بصيغة 5XXXXXXXX، أو null إن لم يكن جوالًا سعوديًا صالحًا. */
    public static function saudiMobile(?string $raw): ?string
    {
        $digits = self::digits($raw);
        if (str_starts_with($digits, '00')) $digits = substr($digits, 2);
        // «966» قد تتكرر حين يُلصق الرقم مع رمز الدولة فوق حقل فيه الرمز أصلًا.
        while (str_starts_with($digits, '966') && strlen($digits) > 9) $digits = substr($digits, 3);
        if (strlen($digits) === 10 && str_starts_with($digits, '0')) $digits = substr($digits, 1);

        return preg_match('/^5\d{8}$/', $digits) ? $digits : null;
    }

    /** مفتاح المقارنة لكشف التكرار: الجوال السعودي المطبّع، وإلا الأرقام الخام إن كانت 7 أرقام فأكثر. */
    public static function key(?string $raw): ?string
    {
        $mobile = self::saudiMobile($raw);
        if ($mobile) return $mobile;
        $digits = self::digits($raw);

        return strlen($digits) >= 7 ? $digits : null;
    }

    /** كل الصيغ التي قد يكون الرقم مخزنًا بها، للبحث عن تطابق في بيانات لم تُنظَّف بعد. */
    public static function variants(string $mobile): array
    {
        return [$mobile, '0'.$mobile, '966'.$mobile, '+966'.$mobile, '00966'.$mobile];
    }
}
