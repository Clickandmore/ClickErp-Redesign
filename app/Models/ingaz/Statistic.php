<?php

namespace App\Models\ingaz;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Statistic extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use Translatable, HasFactory;

    // ملاحظة: عمود image هنا كلاس أيقونة (FontAwesome) وليس ملفًا مرفوعًا،
    // ولذلك لا يحتاج ResolvesMediaUrls.

    protected $guarded = [];

    public $translatedAttributes = ['title'];
    protected $translationForeignKey = 'statistic_id';
    public $translationModel = StatisticsTranslation::class;
    protected $with = ['translations'];


}
