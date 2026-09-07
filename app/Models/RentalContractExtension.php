<?php

namespace App\Models;

use App\Models\Concerns\AssignsBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Concerns\BranchScoped;

class RentalContractExtension extends Model
{
    use AssignsBranch, HasFactory, BranchScoped;

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
     * Split the extension value into 30-day commission periods. The recorded
     * start date is authoritative, not the date the extension was entered.
     */
    public function commissionPeriods(): array
    {
        $totalDays = (int) ($this->extension_days ?? 0);
        if ($totalDays <= 0) {
            $totalDays = max(0, (int) ($this->extension_months ?? 0) * 30);
        }

        $totalCents = (int) round((float) ($this->extension_value ?? 0) * 100);
        if ($totalDays <= 0 || $totalCents <= 0) {
            return [];
        }

        $periodCount = (int) ceil($totalDays / 30);
        $startDate = $this->start_date
            ? $this->start_date->copy()->startOfDay()
            : optional($this->created_at)?->copy()->startOfDay();
        $allocatedCents = 0;
        $periods = [];

        for ($index = 0; $index < $periodCount; $index++) {
            $offsetDays = $index * 30;
            $periodDays = min(30, $totalDays - $offsetDays);
            $periodCents = $index === $periodCount - 1
                ? $totalCents - $allocatedCents
                : (int) round($totalCents * ($periodDays / $totalDays));
            $allocatedCents += $periodCents;
            $periodStart = $startDate?->copy()->addDays($offsetDays);

            $periods[] = [
                'sequence' => $index + 1,
                'count' => $periodCount,
                'days' => $periodDays,
                'start_date' => $periodStart,
                'end_date' => $periodStart?->copy()->addDays($periodDays - 1),
                'amount' => round($periodCents / 100, 2),
            ];
        }

        return $periods;
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
