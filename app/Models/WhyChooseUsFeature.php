<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class WhyChooseUsFeature extends Model implements TranslatableContract
{
    use Translatable;

    public const TYPE_FEATURE = 'feature';
    public const TYPE_HIGHLIGHT = 'highlight';

    protected $fillable = [
        'why_choose_us_id',
        'type',
        'icon',
        'sort_order',
    ];

    public $translatedAttributes = ['title', 'description'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected $with = ['translations'];

    public static function types(): array
    {
        return [self::TYPE_FEATURE, self::TYPE_HIGHLIGHT];
    }
}
