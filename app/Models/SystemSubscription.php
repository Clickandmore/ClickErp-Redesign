<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSubscription extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function system_plans()
    {
        return $this->hasMany(SystemPlan::class, 'system_subscription_id');
    }
}
