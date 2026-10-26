<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoyasarSetting extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    protected $fillable = [
        'is_enabled',
        'base_url',
        'publishable_key',
        'secret_key',
        'webhook_secret',
        'company_name',
        'payment_description',
        'reports_enabled',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'reports_enabled' => 'boolean',
    ];
}
