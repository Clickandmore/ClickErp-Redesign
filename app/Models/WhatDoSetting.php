<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatDoSetting extends Model  implements TranslatableContract
{
    use \App\Models\Concerns\AuditsChanges;

    use \Astrotomic\Translatable\Translatable;

    public $translatedAttributes = ['title', 'description'];
    protected $fillable = [];
    protected $translationForeignKey = 'what_do_set_id';


}
