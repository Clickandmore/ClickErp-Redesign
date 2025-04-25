<?php

namespace App\Models\ingaz;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Nationality extends Model
{
    use Translatable, HasFactory, LogsActivity;

    protected $guarded = [];

    public $translatedAttributes = ['title', 'sub_title'];
    protected $translationForeignKey = 'nationality_id';
    public $translationModel = NationalitiesTranslation::class;
    protected $with = ['translations'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }

}
