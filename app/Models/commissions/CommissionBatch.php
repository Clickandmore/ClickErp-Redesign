<?php

namespace App\Models\commissions;

use App\Models\Concerns\AssignsBranch;
use App\Models\Concerns\BranchScoped;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Model;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\CostCenter;

class CommissionBatch extends Model
{
    use AssignsBranch, BranchScoped;

    protected $table = 'commission_batches';

    protected $fillable = [
        'branch_id',
        'marketer_id', 'total_requests', 'total_base_amount',
        'total_commission', 'accrual_constraint_id', 'expense_account_id',
        'payable_account_id', 'cost_center_id', 'status', 'notes',
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

    public function accrualConstraint()
    {
        return $this->belongsTo(Constraint::class, 'accrual_constraint_id');
    }

    public function expenseAccount()
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function payableAccount()
    {
        return $this->belongsTo(Account::class, 'payable_account_id');
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }
}
