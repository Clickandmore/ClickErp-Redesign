<?php

namespace App\Models\office;

use App\Models\ingaz\Nationality;
use Astrotomic\Translatable\Translatable;

use App\Models\ingaz\JobTitlesTranslation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Office extends Model
{
    use HasFactory, Translatable;

    protected $guarded = [];
    public $translatedAttributes = ['title'];
    protected $translationForeignKey = 'office_id';
    public $translationModel = OfficeTranslation::class;
    protected $with = ['translations'];

    public function nationality()
    {
        return $this->belongsTo(Nationality::class , 'nationality_id');
    }
}
