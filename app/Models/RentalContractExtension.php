<?php

namespace App\Models;

use App\Models\Concerns\AssignsBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentalContractExtension extends Model
{
    use AssignsBranch, HasFactory;

    protected $fillable = [
        'rental_contract_id',
        'branch_id',
        'extension_months',
        'extension_days',
        'extension_value',
        'gross_value',
        'discount_type',
        'discount_value',
        'discount_amount',
        'discount_notes',
        'start_date',
        'end_date',
        'payment_status',
        'payment_type',
        'payment_amount',
        'payment_date',
        'receipt_account_id',
        'invoice_constraint_id',
        'receipt_bond_id',
        'receipt_constraint_id',
        'payment_receipt_path',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'extension_months' => 'integer',
        'extension_days' => 'integer',
        'extension_value' => 'decimal:2',
        'gross_value' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'payment_date' => 'date',
        'branch_id' => 'integer',
    ];

    public function contract()
    {
        return $this->belongsTo(RentalContract::class, 'rental_contract_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'created_by');
    }

    public function receiptAccount()
    {
        return $this->belongsTo(\Nami\Finance\Models\Account::class, 'receipt_account_id');
    }

    public function invoiceConstraint()
    {
        return $this->belongsTo(\Nami\Finance\Models\Constraint::class, 'invoice_constraint_id');
    }

    public function receiptBond()
    {
        return $this->belongsTo(\Nami\Finance\Models\Bond::class, 'receipt_bond_id');
    }

    public function receiptConstraint()
    {
        return $this->belongsTo(\Nami\Finance\Models\Constraint::class, 'receipt_constraint_id');
    }

    /**
     * وصف الخصم كما يظهر في الشاشات: "10% (250.00 ريال)" أو "250.00 ريال".
     */
    public function getDiscountLabelAttribute(): ?string
    {
        $discountAmount = (float) ($this->discount_amount ?? 0);
        if ($discountAmount <= 0) {
            return null;
        }

        if ($this->discount_type === 'percentage') {
            return rtrim(rtrim(number_format((float) $this->discount_value, 2), '0'), '.')
                . '% (' . number_format($discountAmount, 2) . ' ريال)';
        }

        return number_format($discountAmount, 2) . ' ريال';
    }

    public function getPaymentReceiptUrlAttribute(): ?string
    {
        if (!$this->payment_receipt_path) {
            return null;
        }

        return showFile($this->payment_receipt_path);
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromRentalContract($this->rental_contract_id)
            ?? $this->resolveBranchIdFromAdmin($this->created_by)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
