<?php

namespace App\Models;

use App\Models\auth\Admin;
use App\Models\Concerns\AssignsBranch;
use App\Models\ingaz\Invoice;
use Illuminate\Database\Eloquent\Model;
use Nami\Finance\Models\Constraint;

use App\Models\Concerns\BranchScoped;

class RecruitmentContractRefund extends Model
{
    use AssignsBranch, BranchScoped;

    protected $guarded = [];

    protected $casts = [
        'refund_date' => 'date',
        'contract_amount' => 'decimal:2',
        'penalty_percent' => 'decimal:4',
        'penalty_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'branch_id' => 'integer',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function musanedTransferItem()
    {
        return $this->belongsTo(MusanedTransferItem::class, 'musaned_transfer_item_id');
    }

    public function refundConstraint()
    {
        return $this->belongsTo(Constraint::class, 'refund_constraint_id');
    }

    public function creditNote()
    {
        return $this->belongsTo(RecruitmentInvoiceCreditNote::class, 'credit_note_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromContract($this->contract_id)
            ?? $this->resolveBranchIdFromInvoice($this->invoice_id)
            ?? $this->resolveBranchIdFromAdmin($this->created_by_id)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
