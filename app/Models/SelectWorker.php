<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;

class SelectWorker extends Model implements TranslatableContract
{
    use \App\Models\Concerns\AuditsChanges;

    use Translatable;

    public $translatedAttributes = ['title'];
    protected $fillable = ['icon'];
}
