<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationAlertDelivery extends Model
{
    protected $guarded = [];

    protected $casts = [
        'event_at' => 'datetime',
        'sent_at' => 'datetime',
        'occurrence' => 'integer',
    ];
}
