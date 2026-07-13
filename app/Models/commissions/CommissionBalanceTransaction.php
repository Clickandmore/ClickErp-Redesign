<?php

namespace App\Models\commissions;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Model;

class CommissionBalanceTransaction extends Model
{
    protected $table = 'commission_balance_transactions';

    protected $fillable = [
        'marketer_id', 'type', 'amount',
        'reference_type', 'reference_id',
        'balance_after', 'notes', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public const TYPE_CREDIT = 'credit';
    public const TYPE_DEBIT = 'debit';

    public function marketer()
    {
        return $this->belongsTo(Admin::class, 'marketer_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
