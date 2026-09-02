<?php

namespace App\Models\commissions;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Model;

class CommissionMarketerOverride extends Model
{
    protected $table = 'commission_marketer_overrides';

    protected $fillable = ['admin_id', 'service_type', 'method', 'value', 'created_by'];

    protected $attributes = ['method' => 'fixed'];

    protected $casts = ['value' => 'decimal:2'];

    public function marketer()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
