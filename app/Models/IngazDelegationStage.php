<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IngazDelegationStage extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    protected $guarded = [];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function displayName(?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'en' && trim((string) $this->name_en) !== ''
            ? $this->name_en
            : $this->name_ar;
    }
}
