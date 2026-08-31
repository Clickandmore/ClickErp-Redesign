<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class WhyChooseUs extends Model implements TranslatableContract
{
    use Translatable;

    protected $table = 'why_choose_us';

    protected $fillable = ['is_active', 'cta_url'];

    public $translatedAttributes = ['title', 'description', 'cta_label'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function images()
    {
        return $this->hasMany(WhyChooseUsImage::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * Every row regardless of type — used when syncing from the dashboard.
     */
    public function allFeatures()
    {
        return $this->hasMany(WhyChooseUsFeature::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * The cards rendered beside the section copy.
     */
    public function features()
    {
        return $this->allFeatures()->where('type', WhyChooseUsFeature::TYPE_FEATURE);
    }

    /**
     * The short items in the strip over the section image.
     */
    public function highlights()
    {
        return $this->allFeatures()->where('type', WhyChooseUsFeature::TYPE_HIGHLIGHT);
    }
}
