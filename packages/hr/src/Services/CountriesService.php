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
        return DB::table('countries')
            ->where('is_active', true)
            ->select(
                'id',
                'name_ar',
                'name_en',
                'code',
                DB::raw("TRIM(CONCAT(COALESCE(NULLIF(name_ar, ''), name_en), ' - ', COALESCE(NULLIF(name_en, ''), name_ar))) as name"),
                DB::raw('NULL as nationality')
            )
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->get();
    }

}
