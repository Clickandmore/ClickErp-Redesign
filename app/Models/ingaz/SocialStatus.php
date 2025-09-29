<?php

namespace App\Models\ingaz;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SocialStatus extends Model
{
    use Translatable, HasFactory, LogsActivity;

    protected $table = 'social_status';
    protected $guarded = [];

    public $translatedAttributes = ['title'];
    protected $translationForeignKey = 'social_status_id';
    public $translationModel = SocialStatusTranslation::class;
    protected $with = ['translations'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }
}
