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
        'is_multi_branch' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(MusanedTransferItem::class);
    }

    public function scopeApplyBranchVisibility($query, array $branchIds, bool $includeUnassigned): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'is_multi_branch')) {
            $query->where(function ($query) use ($branchIds, $includeUnassigned) {
                $query->whereIn($this->getTable() . '.branch_id', $branchIds);
                if ($includeUnassigned) $query->orWhereNull($this->getTable() . '.branch_id');
            });
            return;
        }
        $query->where(function ($query) use ($branchIds, $includeUnassigned) {
            $query->whereIn($this->getTable() . '.branch_id', $branchIds)
                ->orWhere(function ($query) use ($branchIds) {
                    $query->where($this->getTable() . '.is_multi_branch', true)
                        ->whereHas('items', fn ($items) => $items->whereIn('branch_id', $branchIds));
                });
            if ($includeUnassigned) $query->orWhereNull($this->getTable() . '.branch_id');
        });
    }

    public function branchScopeBelongsToBranch(int $branchId): bool
    {
        return (bool) $this->is_multi_branch
            && $this->items()->where('branch_id', $branchId)->exists();
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
        if ($this->is_multi_branch) return null;
        return $this->resolveBranchIdFromAdmin($this->created_by_id)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
