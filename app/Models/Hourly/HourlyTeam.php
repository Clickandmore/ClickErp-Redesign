<?php

namespace App\Models\Hourly;

use App\Models\auth\Admin;
use App\Models\City;
use App\Models\ShelterPlaces;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * وحدة التشغيل الميدانية: موقع انطلاق، عضويات سائقين، قادة ومناطق خدمة.
 * يحتفظ بارتباط الإيواء والمضلعات القديمة للتوافق مع البيانات السابقة.
 */
class HourlyTeam extends Model
{
    use SoftDeletes;

    protected $table = 'hourly_teams';

    protected $fillable = [
        'shelter_place_id', 'city_id', 'name', 'code', 'color', 'description',
        'base_location_type', 'base_shelter_place_id', 'base_lat', 'base_lng',
        'working_start', 'working_end', 'is_active', 'sort_order', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'base_lat' => 'float',
        'base_lng' => 'float',
    ];

    public function shelterPlace()
    {
        return $this->belongsTo(ShelterPlaces::class, 'shelter_place_id');
    }

    public function baseShelterPlace()
    {
        return $this->belongsTo(ShelterPlaces::class, 'base_shelter_place_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function areas()
    {
        return $this->hasMany(HourlyTeamArea::class, 'hourly_team_id')->orderBy('sort_order')->orderBy('id');
    }

    public function members()
    {
        return $this->hasMany(HourlyTeamMember::class, 'hourly_team_id');
    }

    public function activeMembers()
    {
        return $this->members()->whereNull('left_at');
    }

    public function maidMembers()
    {
        return $this->activeMembers()->where('member_type', HourlyTeamMember::TYPE_MAID);
    }

    public function driverMembers()
    {
        return $this->activeMembers()->where('member_type', HourlyTeamMember::TYPE_DRIVER);
    }

    public function districts()
    {
        return $this->belongsToMany(ServiceDistrict::class, 'hourly_team_district', 'hourly_team_id', 'district_id')->withPivot('is_active')->withTimestamps();
    }

    public function leaders()
    {
        return $this->belongsToMany(HourlyDriver::class, 'hourly_team_driver_leaders', 'hourly_team_id', 'driver_id')->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForShelter(Builder $query, int $shelterPlaceId): Builder
    {
        return $query->where('shelter_place_id', $shelterPlaceId);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->code ? "{$this->name} ({$this->code})" : $this->name;
    }
}
