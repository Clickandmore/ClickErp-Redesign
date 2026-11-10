<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppSendRetry extends Model
{
    protected $table = 'whatsapp_send_retries';

    protected $guarded = [];

    protected $casts = [
        'context' => 'array',
        'next_attempt_at' => 'datetime',
        'attempts' => 'integer',
    ];
}
