<?php

namespace App\Models\ingaz;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FastNew extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use Translatable,HasFactory;

    protected $guarded = [];
    protected $casts = ['is_visible' => 'boolean'];

    public $translatedAttributes = ['title'];
    protected $translationForeignKey = 'fast_new_id';
    public $translationModel = FastNewTranslations::class;
    protected $with = ['translations'];
}
