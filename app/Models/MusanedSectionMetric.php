<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class MusanedSectionMetric extends Model implements TranslatableContract
{
    use \App\Models\Concerns\AuditsChanges;

    use Translatable;

    public const TYPE_RATIO = 'ratio';
    public const TYPE_RATING = 'rating';

    protected $fillable = [
        'type',
        'value',
        'icon',
        'is_visible',
        'sort_order',
    ];

    public $translatedAttributes = ['title'];

    protected $casts = [
        'value' => 'float',
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function section()
    {
        return $this->belongsTo(MusanedSection::class, 'musaned_section_id');
    }
}
