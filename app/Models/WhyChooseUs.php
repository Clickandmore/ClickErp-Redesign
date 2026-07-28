<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class WhyChooseUs extends Model implements TranslatableContract
{
    use Translatable;

    protected $table = 'why_choose_us';

    protected $fillable = ['is_active'];

    public $translatedAttributes = ['title', 'description'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function images()
    {
        return $this->hasMany(WhyChooseUsImage::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function features()
    {
        return $this->hasMany(WhyChooseUsFeature::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
