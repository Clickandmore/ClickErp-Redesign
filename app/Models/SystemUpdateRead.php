<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemUpdateRead extends Model
{
    protected $guarded = [];

    protected $casts = [
        'seen_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function systemUpdate()
    {
        return $this->belongsTo(SystemUpdate::class);
    }

    public function admin()
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'admin_id');
    }
}
