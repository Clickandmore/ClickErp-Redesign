<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Country extends HrModel
{
    use HasFactory;
    protected $table = 'hr_countries';
    protected $guarded = [];

    public function getNameAttribute()
    {
        return \DB::table('hr_countries_translations')->where(['country_id'=> $this->id, 'locale' => app()->getLocale()])->first()->name;
    }
}
