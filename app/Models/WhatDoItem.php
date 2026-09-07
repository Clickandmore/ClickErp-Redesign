<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class  WhatDoItem extends Model implements TranslatableContract
{
    use Translatable, \App\Models\Concerns\AuditsChanges;

    public $translatedAttributes = ['title'];
    protected $fillable = ['what_do_id'];
}
