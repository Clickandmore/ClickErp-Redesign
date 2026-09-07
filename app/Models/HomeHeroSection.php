<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class HomeHeroSection extends Model implements TranslatableContract
{
    use \App\Models\Concerns\AuditsChanges;

    use Translatable;

    protected $fillable = ['is_active', 'video_type', 'video_path', 'video_url'];

    public $translatedAttributes = ['title', 'subtitle', 'video_title'];

    protected $casts = ['is_active' => 'boolean'];

    public function images()
    {
        return $this->hasMany(HomeHeroImage::class)->orderBy('id');
    }
}
