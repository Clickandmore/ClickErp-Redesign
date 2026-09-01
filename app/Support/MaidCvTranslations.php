<?php

namespace App\Support;

use App\Models\ingaz\Maid;
use Illuminate\Support\Collection;

/**
 * كتلة `i18n` المضافة إلى رد تفاصيل العاملة عند طلب أكثر من لغة.
 *
 * الهدف أن تعرض السيرة الذاتية القيم بالعربية والإنجليزية معًا (الجنسية،
 * الديانة، المسمى الوظيفي، المهارات، دول الخبرة) دون أن يضطر الموقع الخارجي
 * إلى قراءة مصفوفة `translations` الخام والاعتماد على ترتيبها.
 */
class MaidCvTranslations
{
    /**
     * @param  array<int, string>  $locales
     * @return array<string, mixed>
     */
    public static function build(Maid $maid, Collection $skills, array $locales): array
    {
        $payload = ['locales' => array_values($locales)];

        foreach ($locales as $locale) {
            $payload[$locale] = self::forLocale($maid, $skills, $locale);
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private static function forLocale(Maid $maid, Collection $skills, string $locale): array
    {
        $nationality = $maid->nationality;
        $nationalityTranslation = $nationality?->translate($locale);
        $religionTranslation = $maid->religion?->translate($locale);

        $jobTitles = $maid->jobTitles
            ->map(fn ($jobTitle) => trim((string) ($jobTitle->translate($locale)?->title ?? '')))
            ->filter()
            ->unique()
            ->values();

        return [
            'name' => $maid->name,
            'marital_status' => $maid->translate($locale)?->marital_status,
            'nationality' => trim((string) (
                $nationalityTranslation?->sub_title ?: $nationalityTranslation?->title
            )) ?: null,
            'nationality_title' => $nationalityTranslation?->title,
            'nationality_sub_title' => $nationalityTranslation?->sub_title,
            'religion' => $religionTranslation?->title,
            'job_titles' => $jobTitles->all(),
            'job_title' => $jobTitles->isNotEmpty() ? $jobTitles->join(' - ') : null,
            'skills' => $skills
                ->map(fn ($skill) => [
                    'id' => $skill->id,
                    'title' => $skill->translate($locale)?->title,
                ])
                ->filter(fn ($skill) => filled($skill['title']))
                ->values()
                ->all(),
            // نفس ترتيب `experience.countries` حتى يمكن ربط الصفين معًا.
            'experience_countries' => $maid->expertises
                ->map(fn ($expertise) => [
                    'country_id' => $expertise->country_id,
                    'country' => $locale === 'en'
                        ? ($expertise->country?->name_en ?: $expertise->country?->name_ar)
                        : ($expertise->country?->name_ar ?: $expertise->country?->name_en),
                    'work_city' => $expertise->work_city,
                    'job_title_id' => $expertise->job_title_id,
                    'job_title' => $expertise->jobTitle?->translate($locale)?->title,
                ])
                ->values()
                ->all(),
        ];
    }
}
