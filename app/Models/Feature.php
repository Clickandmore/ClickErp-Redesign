<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use Translatable;
 
    protected $fillable = [
        'image'
    ];

    public $translatedAttributes = [
        'title',
    ];


     
}
