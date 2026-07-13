<?php

namespace App\Models\commissions;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Model;

class CommissionBatch extends Model
{
    protected $table = 'commission_batches';

    protected $fillable = [
        'marketer_id', 'total_requests', 'total_base_amount',
        'total_commission', 'status', 'notes',
        'approved_by', 'approved_at', 'created_by',
    ];

    protected $casts = [
        'total_base_amount' => 'decimal:2',
        'total_commission' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

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

    public function items()
    {
        return $this->hasMany(CommissionBatchItem::class, 'batch_id');
    }

    public function balanceTransactions()
    {
        return $this->hasMany(CommissionBalanceTransaction::class, 'reference_id')
            ->where('reference_type', 'commission_batch');
    }
}
