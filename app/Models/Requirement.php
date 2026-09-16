<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Requirement extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use Translatable;

    protected $fillable = [
        'image',
        'type',
        'icon',
        'sort_order',
        'is_active',
    ];

    public $translatedAttributes = ['title'];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(RequirementItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
