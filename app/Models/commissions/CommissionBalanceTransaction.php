<?php

namespace App\Models\commissions;

use App\Models\Concerns\AssignsBranch;
use App\Models\Concerns\BranchScoped;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Model;

class CommissionBalanceTransaction extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use AssignsBranch, BranchScoped;

    protected $table = 'commission_balance_transactions';

    protected $fillable = [
        'branch_id',
        'marketer_id', 'type', 'amount',
        'reference_type', 'reference_id',
        'balance_after', 'notes', 'created_by',
        'constraint_id',
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

    public function constraint()
    {
        return $this->belongsTo(\Nami\Finance\Models\Constraint::class, 'constraint_id');
    }
}
