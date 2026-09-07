<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatDoSettingTranslation extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use HasFactory;

    public $timestamps = false;
    protected $fillable = ['title', 'description'];

}
