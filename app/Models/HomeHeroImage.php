<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeHeroImage extends Model
{
    protected $fillable = ['path'];

    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        return showFile($this->path);
    }
}
