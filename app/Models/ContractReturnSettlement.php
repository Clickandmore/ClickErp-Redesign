<?php

namespace App\Models;

use App\Models\auth\Admin;
use App\Models\Concerns\AssignsBranch;
use App\Models\Concerns\BranchScoped;
use App\Models\ingaz\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractReturnSettlement extends Model
{
    use AssignsBranch, BranchScoped, SoftDeletes;
    use \App\Models\Concerns\AuditsChanges;

    protected $guarded = [];

    protected $casts = [
        'settlement_date' => 'date',
        'proposed_total' => 'decimal:2',
        'approved_total' => 'decimal:2',
        'client_due_amount' => 'decimal:2',
        'client_paid_amount' => 'decimal:2',
        'settings_snapshot' => 'array',
        'approved_at' => 'datetime',
        'branch_id' => 'integer',
    ];

    public function contract() { return $this->belongsTo(Contract::class); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function reasonCode() { return $this->belongsTo(ReturnReasonCode::class, 'return_reason_code_id'); }
    public function lines() { return $this->hasMany(ContractReturnSettlementLine::class, 'settlement_id'); }
    public function createdBy() { return $this->belongsTo(Admin::class, 'created_by_id'); }
    public function approvedBy() { return $this->belongsTo(Admin::class, 'approved_by_id'); }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromContract($this->contract_id)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
