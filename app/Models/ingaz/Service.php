<?php

namespace App\Models\ingaz;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Service extends Model
{
    use Translatable, HasFactory, LogsActivity;

    protected $guarded = [];

    public $translatedAttributes = ['title', 'description'];
    protected $translationForeignKey = 'service_id';
    public $translationModel = ServicesTranslation::class;
    protected $with = ['translations'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }


    protected $appends = [
        'image_url',
    ];

    public function getImageURLAttribute()
    {
        return asset('storage/' . $this->image) ?? null;
    }

}
