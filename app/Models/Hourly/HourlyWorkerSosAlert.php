<?php

namespace App\Models\Hourly;

use App\Models\auth\Admin;
use App\Models\ingaz\Maid;
use Illuminate\Database\Eloquent\Model;

class HourlyWorkerSosAlert extends Model
{
    protected $guarded = [];

    protected $casts = [
        'latitude' => 'float', 'longitude' => 'float', 'accuracy' => 'float',
        'repeat_count' => 'integer', 'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime', 'cancelled_at' => 'datetime',
    ];

    public function maid() { return $this->belongsTo(Maid::class); }
    public function visit() { return $this->belongsTo(HourlyVisit::class, 'hourly_visit_id'); }
    public function acknowledger() { return $this->belongsTo(Admin::class, 'acknowledged_by'); }
    public function resolver() { return $this->belongsTo(Admin::class, 'resolved_by'); }
}
