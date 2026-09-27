<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnReasonCode extends Model
{
    protected $guarded = [];

    protected $casts = [
        'suggest_client_deduction' => 'boolean',
        'suggest_office_charge' => 'boolean',
        'is_active' => 'boolean',
    ];
}
