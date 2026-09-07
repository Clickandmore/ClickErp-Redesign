<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhyChooseUsImage extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    protected $fillable = [
        'why_choose_us_id',
        'path',
        'is_public_asset',
        'sort_order',
    ];

    protected $casts = [
        'is_public_asset' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = ['url'];

    public function getUrlAttribute(): ?string
    {
        return $this->is_public_asset
            ? asset(ltrim($this->path, '/'))
            : showFile($this->path);
    }
}
