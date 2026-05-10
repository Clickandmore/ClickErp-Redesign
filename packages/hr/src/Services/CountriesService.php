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
        $nameColumn = $locale === 'ar' ? 'name_ar' : 'name_en';
        $fallbackColumn = $locale === 'ar' ? 'name_en' : 'name_ar';

        return DB::table('countries')
            ->where('is_active', true)
            ->select(
                'id',
                DB::raw("COALESCE(NULLIF({$nameColumn}, ''), NULLIF({$fallbackColumn}, '')) as name"),
                DB::raw('NULL as nationality')
            )
            ->orderBy('sort_order')
            ->orderBy($nameColumn)
            ->get();
    }

}
