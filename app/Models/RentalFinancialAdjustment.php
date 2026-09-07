<?php

namespace App\Models;

use App\Models\Concerns\AssignsBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Concerns\BranchScoped;

class RentalFinancialAdjustment extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use AssignsBranch, HasFactory, BranchScoped;

    protected $fillable = [
        'rental_contract_id',
        'branch_id',
        'maid_id',
        'account_id',
        'constraint_id',
        'finance_sale_id',
        'cost_center_id',
        'type',
        'label',
        'amount',
        'calculated_amount',
        'manual_override_reason',
        'manual_override_attachment_path',
        'manual_override_by',
        'manual_override_approved_by',
        'manual_override_approved_at',
        'taxable_amount',
        'non_taxable_amount',
        'vat_amount',
        'line_items',
        'send_to_zatca',
        'notes',
        'context',
        'issue_date',
        'created_by',
        'settled_at',
        'settled_by',
        'receipt_path',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'calculated_amount' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'non_taxable_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'line_items' => 'array',
        'send_to_zatca' => 'boolean',
        'issue_date' => 'date',
        'settled_at' => 'datetime',
        'manual_override_approved_at' => 'datetime',
        'branch_id' => 'integer',
    ];

    public function rentalContract()
    {
        return $this->belongsTo(RentalContract::class, 'rental_contract_id');
    }

    public function maid()
    {
        return $this->belongsTo(\App\Models\ingaz\Maid::class, 'maid_id');
    }

    public function account()
    {
        return $this->belongsTo(\Nami\Finance\Models\Account::class, 'account_id');
    }

    public function constraint()
    {
        return $this->belongsTo(\Nami\Finance\Models\Constraint::class, 'constraint_id');
    }

    public function financeSale()
    {
        return $this->belongsTo(\Nami\Finance\Models\Sale::class, 'finance_sale_id');
    }

    public function costCenter()
    {
        return $this->belongsTo(\Nami\Finance\Models\CostCenter::class, 'cost_center_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'created_by');
    }

    public function settledBy()
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'settled_by');
    }

    public function manualOverrideBy()
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'manual_override_by');
    }

    public function manualOverrideApprovedBy()
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'manual_override_approved_by');
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromRentalContract($this->rental_contract_id)
            ?? $this->resolveBranchIdFromMaid($this->maid_id)
            ?? $this->resolveBranchIdFromAdmin($this->created_by)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
