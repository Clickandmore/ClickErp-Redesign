<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppOptOut extends Model
{
    protected $table = 'whatsapp_opt_outs';

    protected $guarded = [];

    protected $casts = ['opted_out_at' => 'datetime'];
}
