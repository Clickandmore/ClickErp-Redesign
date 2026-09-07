<?php

namespace App\Models\ingaz;

use App\Models\Concerns\AssignsBranch;
use App\Models\Contract;
use App\Models\IngazDelegation;
use App\Models\MusanedTransferItem;
use App\Models\RecruitmentInvoiceCreditNote;
use App\Models\SponsorshipTransfer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\Purchase;
use Nami\Finance\Models\Sale;

use App\Models\Concerns\BranchScoped;

class Invoice extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use AssignsBranch, HasFactory, BranchScoped;

    protected $guarded = [];
    protected $table = 'invoices';
    protected $casts = [
        'is_paid' => 'boolean',
        'send_to_zatca' => 'boolean',
        'legacy_zatca_shared' => 'boolean',
        'legacy_source_payload' => 'array',
        'line_items' => 'array',
        'paid_at' => 'datetime',
        'branch_id' => 'integer',
    ];

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromSponsorshipTransfer($this->sponsorship_transfer_id ?? null)
            ?: $this->resolveBranchIdFromContract($this->contract_id ?? null)
            ?: $this->resolveBranchIdFromCurrentAdmin();
    }

    private function resolveBranchIdFromSponsorshipTransfer($transferId): ?int
    {
        if (!$transferId) {
            return null;
        }

        return SponsorshipTransfer::query()->whereKey($transferId)->value('branch_id');
    }

    public function contract(){
        return $this->belongsTo(Contract::class);
    }

    public function ingazDelegation()
    {
        return $this->belongsTo(IngazDelegation::class, 'ingaz_delegation_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function sponsorshipTransfer()
    {
        return $this->belongsTo(\App\Models\SponsorshipTransfer::class);
    }

    public function invoiceConstraint()
    {
        return $this->belongsTo(Constraint::class, 'invoice_constraint_id');
    }

    public function musanedTransferItem()
    {
        return $this->hasOne(MusanedTransferItem::class, 'invoice_id');
    }

    public function arrivalRevenueConstraint()
    {
        return $this->belongsTo(Constraint::class, 'arrival_revenue_constraint_id');
    }

    public function externalOfficeCostConstraint()
    {
        return $this->belongsTo(Constraint::class, 'external_office_cost_constraint_id');
    }

    public function externalOfficePurchase()
    {
        return $this->belongsTo(Purchase::class, 'external_office_purchase_id');
    }

    public function ingazDelegationCostConstraint()
    {
        return $this->belongsTo(Constraint::class, 'ingaz_delegation_cost_constraint_id');
    }

    public function financeSale()
    {
        return $this->belongsTo(Sale::class, 'finance_sale_id');
    }

    public function recruitmentCreditNotes()
    {
        return $this->hasMany(RecruitmentInvoiceCreditNote::class, 'invoice_id');
    }
}
