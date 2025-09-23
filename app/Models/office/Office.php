<?php

namespace App\Models\office;

use App\Models\ingaz\JobTitle;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Nationality;
use App\Models\Maid;
use App\Models\RecruitmentOfficeTranslation;
use Astrotomic\Translatable\Translatable;

use App\Models\ingaz\JobTitlesTranslation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Office extends Model
{
    use HasFactory, Translatable;

    protected $guarded = [];
    public $translatedAttributes = ['title'];
    protected $translationForeignKey = 'rec_office_id';
    public $translationModel = RecruitmentOfficeTranslation::class;
    protected $with = ['translations'];
    protected $table = 'recruitment_offices';

    public function nationality()
    {
        return $this->belongsTo(Nationality::class, 'nationality_id');
    }

    public function jobTypes()
    {
        return $this->belongsToMany(JobType::class, 'jop_type_offices', 'office_id', 'jop_type_id');
    }
    public function officeManager()
    {
        return $this->hasOne(AdminOffice::class, 'office_id');
    }

    public function file()
    {
        return $this->hasOne(OfficeFile::class, 'recruitment_office_id');
    }

    public function maids()
    {
        return $this->hasMany(Maid::class, 'office_id');
    }

}
