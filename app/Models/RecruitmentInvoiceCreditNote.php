<?php

namespace App\Models;

use App\Models\Concerns\AssignsBranch;
use App\Models\ingaz\Invoice;
use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Model;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\Sale;

class RecruitmentInvoiceCreditNote extends Model
{
    use AssignsBranch;

    protected $guarded = [];

    protected $casts = [
        'issue_date' => 'date',
        'taxable_amount' => 'decimal:2',
        'non_taxable_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'line_items' => 'array',
        'send_to_zatca' => 'boolean',
        'branch_id' => 'integer',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function ingazDelegation()
    {
        return $this->belongsTo(IngazDelegation::class, 'ingaz_delegation_id');
    }

    public function financeSale()
    {
        return $this->belongsTo(Sale::class, 'finance_sale_id');
    }

    public function constraint()
    {
        return $this->belongsTo(Constraint::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromContract($this->contract_id)
            ?? $this->resolveBranchIdFromInvoice($this->invoice_id)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
