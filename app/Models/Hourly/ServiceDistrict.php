<?php

namespace App\Models\Hourly;

use App\Models\City;
use Illuminate\Database\Eloquent\Model;

class ServiceDistrict extends Model
{
    protected $table = 'hourly_service_districts';
    protected $fillable = ['city_id', 'name', 'name_en', 'slug', 'latitude', 'longitude', 'is_active', 'polygon'];
    protected $casts = ['is_active'=>'boolean', 'polygon'=>'array'];
    public function city() { return $this->belongsTo(City::class); }
    public function teams()
    {
        return $this->belongsToMany(HourlyTeam::class, 'hourly_team_district', 'district_id', 'hourly_team_id')->withPivot('is_active')->withTimestamps();
    }
}
