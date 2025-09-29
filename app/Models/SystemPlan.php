<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemPlan extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function system_subscription()
    {
        return $this->belongsTo(SystemSubscription::class, 'system_subscription_id');
    }
}
