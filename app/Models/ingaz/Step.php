<?php

namespace App\Models\ingaz;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Step extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use Translatable, HasFactory;

    protected $guarded = [];

    public $translatedAttributes = ['title', 'description'];
    protected $translationForeignKey = 'step_id';
    public $translationModel = StepsTranslation::class;
    protected $with = ['translations'];

    protected $casts = [
        'sort_order' => 'integer',
    ];
}
