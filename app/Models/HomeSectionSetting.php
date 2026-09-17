<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSectionSetting extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    protected $fillable = [
        'key',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];
}
