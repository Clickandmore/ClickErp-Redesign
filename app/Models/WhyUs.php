<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhyUs extends Model implements TranslatableContract
{
    use Translatable;

    public $translatedAttributes = ['title', 'description'];
    protected $fillable = ['icon'];
}
