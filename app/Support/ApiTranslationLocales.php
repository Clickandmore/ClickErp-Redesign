<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * لغات إضافية يطلبها المستهلك الخارجي ليعود الرد محمّلاً بأكثر من لغة معًا.
 *
 * تُرسل عبر الهيدر `langs` أو الباراميتر `?langs=ar,en`، وهي منفصلة تمامًا عن
 * الهيدر `lang` الذي يحدد لغة العرض الأساسية. المشاريع الأخرى المرتبطة بنفس
 * الداشبورد لا ترسل `langs`، فيبقى الرد لها كما هو بلا أي تغيير.
 */
class ApiTranslationLocales
{
    /** اللغات المدعومة في الداشبورد. */
    public const SUPPORTED = ['ar', 'en'];

    /**
     * @return array<int, string> قائمة اللغات المطلوبة، أو مصفوفة فارغة إذا لم تُطلب.
     */
    public static function requested(Request $request): array
    {
        $raw = $request->header('langs') ?: $request->query('langs');

        if (is_array($raw)) {
            $raw = implode(',', $raw);
        }

        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        return collect(preg_split('/[,|\s]+/', strtolower(trim($raw))))
            ->map(fn ($locale) => substr(trim((string) $locale), 0, 2))
            ->filter(fn ($locale) => in_array($locale, self::SUPPORTED, true))
            ->unique()
            ->values()
            ->all();
    }
}
