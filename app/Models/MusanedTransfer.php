<?php

namespace App\Models;

use App\Models\auth\Admin;
use App\Models\Concerns\AssignsBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Constraint;

use App\Models\Concerns\BranchScoped;

class MusanedTransfer extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use AssignsBranch, HasFactory, BranchScoped;

    protected $guarded = [];

    protected $casts = [
        'transfer_date' => 'date',
        'customer_paid_total' => 'decimal:2',
        'transferred_total' => 'decimal:2',
        'commission_total' => 'decimal:2',
        'expense_tax_total' => 'decimal:2',
        'commission_net_total' => 'decimal:2',
        'expense_tax_rate' => 'decimal:4',
        'branch_id' => 'integer',
    ];

    public function items()
    {
        return $this->hasMany(MusanedTransferItem::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function intermediateAccount()
    {
        return $this->belongsTo(Account::class, 'intermediate_account_id');
    }

    public function commissionAccount()
    {
        return $this->belongsTo(Account::class, 'commission_account_id');
    }

    public function expenseTaxAccount()
    {
        return $this->belongsTo(Account::class, 'expense_tax_account_id');
    }

    public function settlementConstraint()
    {
        return $this->belongsTo(Constraint::class, 'settlement_constraint_id');
    }

    public function bankTransferConstraint()
    {
        return $this->belongsTo(Constraint::class, 'bank_transfer_constraint_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromAdmin($this->created_by_id)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
