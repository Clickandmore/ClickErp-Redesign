<?php

namespace App\Models\commissions;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Model;

class CommissionPayment extends Model
{
    protected $table = 'commission_payments';

    protected $fillable = [
        'marketer_id', 'bond_id', 'amount', 'payment_date',
        'notes', 'status', 'approved_by', 'approved_at', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';

    public function marketer()
    {
        return $this->belongsTo(Admin::class, 'marketer_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
