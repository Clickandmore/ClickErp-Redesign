<?php

namespace App\Models\ingaz;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\ingaz\Nationality;

class Package extends Model
{
    use Translatable, HasFactory, LogsActivity;

    protected $guarded = [];

    public $translatedAttributes = ['title'];
    protected $translationForeignKey = 'package_id';
    public $translationModel = PackagesTranslation::class;
    protected $with = ['translations'];

    public function nationality()
    {
        return $this->belongsTo(Nationality::class, 'nationality_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }
}
