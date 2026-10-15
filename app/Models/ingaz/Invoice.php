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
        'musaned_commission_rate' => 'decimal:4',
        'musaned_commission_amount' => 'decimal:2',
        'musaned_commission_tax' => 'decimal:2',
    ];

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromTable('rental_contracts', $this->rental_transfer_contract_id ?? null)
            ?: $this->resolveBranchIdFromSponsorshipTransfer($this->sponsorship_transfer_id ?? null)
            ?: $this->resolveBranchIdFromContract($this->contract_id ?? null)
            ?: $this->resolveBranchIdFromCurrentAdmin();
    }

    public function rentalTransferContract()
    {
        return $this->belongsTo(\App\Models\RentalContract::class, 'rental_transfer_contract_id');
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
        return $this->belongsTo(User::class, 'client_id')->withTrashed();
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

    public function musanedCommissionConstraint()
    {
        return $this->belongsTo(Constraint::class, 'musaned_commission_constraint_id');
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

    /** Supplier closures and imported adjustments are history, not customer invoices. */
    public function isRecruitmentRevenue(): bool
    {
        return !$this->legacy_invoice_number || $this->legacy_invoice_type === null
            || in_array($this->legacy_invoice_type, self::recruitmentRevenueTypes(), true);
    }

    private static function recruitmentRevenueTypes(): array
    {
        return ['فاتورة ايراد / طلب استقدام', 'فاتورة إيراد / طلب استقدام'];
    }

    public function scopeRecruitmentRevenue($query)
    {
        return $query->where(function ($query) {
            $query->whereNull('invoices.legacy_invoice_number')
                ->orWhereNull('invoices.legacy_invoice_type')
                ->orWhereIn('invoices.legacy_invoice_type', self::recruitmentRevenueTypes());
        });
    }

    /** Only a fully credited invoice allows a replacement invoice for its contract. */
    public function scopeBlockingReplacement($query)
    {
        return $query->recruitmentRevenue()->where(function ($query) {
            $query->whereDoesntHave('recruitmentCreditNotes')
                ->orWhereRaw('ROUND(invoices.total_due * 100) > (SELECT COALESCE(SUM(ROUND(credit.total * 100)), 0) FROM recruitment_invoice_credit_notes AS credit WHERE credit.invoice_id = invoices.id)');
        });
    }
}
