<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class Requirement extends Model
{
    use Translatable;
 
    protected $fillable = [
        'image','type'
    ];

    public $translatedAttributes = [
        'title',
    ];


     
}
