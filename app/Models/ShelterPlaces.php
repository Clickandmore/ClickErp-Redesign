<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ShelterPlaces extends Model
{
    use Translatable, HasFactory, LogsActivity;

    protected $guarded = [];
    public $translatedAttributes = ['title'];
    protected $translationForeignKey = 'shelter_place_id';
    public $translationModel = ShelterPlaceTranslation::class;
    protected $with = ['translations'];
    protected $casts = [
        'female_capacity' => 'integer',
        'male_capacity' => 'integer',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function supervisor()
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'supervisor_admin_id');
    }

    public function responsible()
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'responsible_admin_id');
    }

    public function contractInfos()
    {
        return $this->hasMany(\App\Models\ContractInfo::class, 'shelter_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }
}
