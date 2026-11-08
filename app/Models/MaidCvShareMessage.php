<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaidCvShareMessage extends Model
{
    protected $guarded = [];
    protected $casts = ['snapshot' => 'array', 'accepted_at' => 'datetime', 'delivered_at' => 'datetime', 'read_at' => 'datetime'];

    public function share()
    {
        return $this->belongsTo(MaidCvShare::class, 'share_id');
    }
}
