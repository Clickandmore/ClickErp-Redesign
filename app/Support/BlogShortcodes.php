<?php

namespace App\Support;

use App\Models\ingaz\Setting;

/**
 * الأكواد المختصرة داخل محتوى المدونة — نفس فكرة متغيرات القوالب: المحرر يكتب
 * {{contact_info}} بين الفقرات فيُستبدل عند الإرسال للموقع الخارجي بزرّي
 * «تواصل معنا» و«واتساب» بالأرقام الحالية من إعدادات الموقع.
 *
 * الاستبدال يتم هنا لا في كل موقع خارجي: المواقع تعرض content كـ HTML كما هو،
 * فتعمل الأزرار في كل المواقع دون تعديلها. الأنماط inline حتى لا تعتمد على CSS
 * الموقع، والكلاسات blog-contact-cta* متاحة لمن أراد تخصيص الشكل.
 *
 * المحتوى المخزَّن لا يتغيّر — الكود يبقى كما هو في قاعدة البيانات، فتغيير الرقم
 * في الإعدادات ينعكس على كل المقالات فورًا.
 */
class BlogShortcodes
{
    public const CONTACT_INFO = 'contact_info';

    /** الأكواد المتاحة ووصفها — تُعرض للمحرر في نموذج المقال. */
    public static function available(): array
    {
        return [
            self::CONTACT_INFO => 'زرّا الاتصال والواتساب بأرقام إعدادات الموقع',
        ];
    }

    public static function render(?string $content): string
    {
        $content = (string) $content;

        if (! str_contains($content, '{{')) {
            return $content;
        }

        $pattern = self::pattern(self::CONTACT_INFO);
        if (! preg_match($pattern, $content)) {
            return $content;
        }

        $settings = Setting::query()->first();

        // الكود وحده داخل فقرة (الشكل الطبيعي من TinyMCE): تُستبدل الفقرة كاملة
        // بكتلة div، لأن div داخل <p> HTML غير صالح ويكسر تنسيق الفقرة.
        // الوسوم المسموح بها حول الكود داخل الفقرة: أي وسم عدا فتح/إغلاق فقرة أخرى.
        $filler = '(?:\s|&nbsp;|<(?!/?p\b)[^>]*>)*';
        $content = preg_replace_callback(
            '~<p\b[^>]*>' . $filler . self::inner(self::CONTACT_INFO) . $filler . '</p>~iu',
            fn () => self::contactButtons($settings, 'div'),
            $content
        );

        // الكود وسط سطر نصي: span بعرض كامل يبقى صالحًا داخل الفقرة.
        return preg_replace_callback($pattern, fn () => self::contactButtons($settings, 'span'), $content);
    }

    /** المحتوى بدون أي كود مختصر — للملخص التلقائي وحساب زمن القراءة. */
    public static function strip(?string $content): string
    {
        return (string) preg_replace('~\{\{\s*[a-z_]+\s*\}\}~iu', ' ', (string) $content);
    }

    private static function pattern(string $code): string
    {
        return '~' . self::inner($code) . '~iu';
    }

    /**
     * TinyMCE قد يلفّ جزءًا من الكود بوسم تنسيق (<strong>{{contact_info}}</strong>
     * أو يكسره بـ <span>) — نتسامح مع الوسوم والمسافات حول اسم الكود.
     */
    private static function inner(string $code): string
    {
        $gap = '(?:\s|&nbsp;|<[^>]+>)*';

        return '\{' . $gap . '\{' . $gap . preg_quote($code, '~') . $gap . '\}' . $gap . '\}';
    }

    private static function contactButtons(?Setting $settings, string $tag): string
    {
        $phone = self::telNumber($settings?->phone);
        $whatsapp = self::whatsappNumber($settings?->whatsapp ?: $settings?->phone);

        // لا أرقام في الإعدادات: يختفي الكود بدل ظهوره كنص خام للزائر.
        if ($phone === '' && $whatsapp === '') {
            return '';
        }

        $isEnglish = app()->getLocale() === 'en';
        $buttonStyle = 'display:inline-flex;align-items:center;justify-content:center;gap:8px;min-width:150px;'
            . 'padding:12px 26px;border-radius:12px;color:#fff;font-weight:700;font-size:16px;line-height:1.4;'
            . 'text-decoration:none;box-shadow:0 6px 16px rgba(15,23,42,.12);';

        $buttons = '';

        if ($phone !== '') {
            $buttons .= '<a class="blog-contact-cta__btn blog-contact-cta__btn--call" href="tel:' . e($phone) . '"'
                . ' style="' . $buttonStyle . 'background:#f97316;">'
                . self::phoneIcon() . '<span>' . ($isEnglish ? 'Contact us' : 'تواصل معنا') . '</span></a>';
        }

        if ($whatsapp !== '') {
            $buttons .= '<a class="blog-contact-cta__btn blog-contact-cta__btn--whatsapp" href="https://wa.me/' . e($whatsapp) . '"'
                . ' target="_blank" rel="noopener" style="' . $buttonStyle . 'background:#25d366;">'
                . self::whatsappIcon() . '<span>' . ($isEnglish ? 'WhatsApp' : 'واتساب') . '</span></a>';
        }

        return '<' . $tag . ' class="blog-contact-cta" data-shortcode="' . self::CONTACT_INFO . '"'
            . ' style="display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:16px;margin:24px 0;'
            . 'padding:20px;border:1px solid #e5e7eb;border-radius:16px;background:#fff;">'
            . $buttons . '</' . $tag . '>';
    }

    /** للاتصال: الأرقام مع + البادئة إن وُجدت، كما هي في الإعدادات. */
    private static function telNumber(?string $raw): string
    {
        $digits = PhoneNumber::digits($raw);
        if ($digits === '') {
            return '';
        }

        return str_starts_with(trim((string) $raw), '+') ? '+' . $digits : $digits;
    }

    /** wa.me يحتاج رمز الدولة بلا + ولا أصفار: 05XXXXXXXX ← 9665XXXXXXXX. */
    private static function whatsappNumber(?string $raw): string
    {
        $mobile = PhoneNumber::saudiMobile($raw);
        if ($mobile) {
            return '966' . $mobile;
        }

        return ltrim(PhoneNumber::digits($raw), '0');
    }

    private static function phoneIcon(): string
    {
        return '<svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="flex-shrink:0;">'
            . '<path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 11.4 11.4 0 0 0 3.6.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1z"/></svg>';
    }

    private static function whatsappIcon(): string
    {
        return '<svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="flex-shrink:0;">'
            . '<path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.15l-.3-.18-3 .78.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.25-.12-1.46-.72-1.69-.8-.23-.08-.39-.12-.55.12-.16.25-.63.8-.78.97-.14.16-.29.18-.53.06a6.7 6.7 0 0 1-3.34-2.92c-.25-.43.25-.4.72-1.33.08-.16.04-.3-.02-.43-.06-.12-.55-1.33-.76-1.82-.2-.48-.4-.41-.55-.42h-.47a.9.9 0 0 0-.65.3 2.7 2.7 0 0 0-.85 2.02 4.7 4.7 0 0 0 1 2.5 10.8 10.8 0 0 0 4.13 3.65c1.54.66 2.14.72 2.91.6.47-.07 1.46-.6 1.66-1.17.2-.57.2-1.06.14-1.17-.06-.1-.22-.16-.47-.28z"/></svg>';
    }
}
