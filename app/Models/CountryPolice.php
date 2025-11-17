<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class CountryPolice extends Model implements TranslatableContract
{
    use Translatable;
    protected $table = 'country_polices';

    public $translatedAttributes = ['name', 'text_file_1', 'text_file_2'];
    protected $fillable = ['flag', 'file_1', 'file_2'];
}
