<?php

namespace App\Models;

use App\Models\ingaz\Nationality;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RecruitmentOffices extends Model
{
    use Translatable, HasFactory, LogsActivity;

    protected $guarded = [];
    public $translatedAttributes = ['title'];
    protected $translationForeignKey = 'rec_office_id';
    public $translationModel = RecruitmentOfficeTranslation::class;
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
