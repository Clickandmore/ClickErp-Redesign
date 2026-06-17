<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoyasarSetting extends Model
{
    protected $fillable = [
        'is_enabled',
        'base_url',
        'publishable_key',
        'secret_key',
        'webhook_secret',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];
}
