<?php

namespace Nami\Hr\Services;

use Illuminate\Support\Facades\DB;
use Nami\Hr\MainService;
use Nami\Hr\Models\Country;

class CountriesService extends MainService
{

    public function __construct(Country $model)
    {
        $this->model = $model;
    }

    public function getCountries()
    {
        $locale = app()->getLocale();

        return DB::table('hr_countries')
            ->leftJoin('hr_countries_translations as locale_translations', function ($join) use ($locale) {
                $join->on('hr_countries.id', '=', 'locale_translations.country_id')
                    ->where('locale_translations.locale', '=', $locale);
            })
            ->leftJoin('hr_countries_translations as fallback_translations', function ($join) {
                $join->on('hr_countries.id', '=', 'fallback_translations.country_id')
                    ->where('fallback_translations.locale', '=', 'en');
            })
            ->select(
                'hr_countries.id',
                DB::raw("COALESCE(NULLIF(locale_translations.name, ''), NULLIF(fallback_translations.name, '')) as name"),
                DB::raw("COALESCE(NULLIF(locale_translations.nationality, ''), NULLIF(fallback_translations.nationality, '')) as nationality")
            )
            ->orderByRaw("
                CASE
                    WHEN COALESCE(
                        NULLIF(locale_translations.nationality, ''),
                        NULLIF(fallback_translations.nationality, ''),
                        NULLIF(locale_translations.name, ''),
                        NULLIF(fallback_translations.name, '')
                    ) IS NULL THEN 1
                    ELSE 0
                END
            ")
            ->orderByRaw("
                COALESCE(
                    NULLIF(locale_translations.nationality, ''),
                    NULLIF(fallback_translations.nationality, ''),
                    NULLIF(locale_translations.name, ''),
                    NULLIF(fallback_translations.name, '')
                )
            ")
            ->get();
    }

}
