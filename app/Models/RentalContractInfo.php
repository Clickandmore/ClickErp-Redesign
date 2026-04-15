<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class RentalContractInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_contract_id',
        'package_id',
        'payment_type',
        'payment_status',
        'payment_receipt_path',
        'payment_confirmed_at',
        'payment_confirmed_by',
        'closure_client_iban',
        'closure_client_iban_attachment_path',
        'working_hours_per_day',
        'hourly_rate',
        'monthly_salary',
        'sponsorship_transfer_amount',
        'sponsorship_transfer_total_amount',
        'sponsorship_transfer_paid_amount',
        'sponsorship_transfer_remaining_amount',
        'salary_after_sponsorship',
        'sponsorship_transfer',
        'rental_type',
        'monthly_cost',
        'contract_duration_months',
        'contract_duration_days',
        'contract_value',
        'paid_amount',
        'remaining_amount',
        'ingaz_from',
        'ingaz_to',
        'notes',
        'additional_terms',
    ];

    protected $casts = [
        'package_id' => 'integer',
        'hourly_rate' => 'decimal:2',
        'monthly_salary' => 'decimal:2',
        'sponsorship_transfer_amount' => 'decimal:2',
        'sponsorship_transfer_total_amount' => 'decimal:2',
        'sponsorship_transfer_paid_amount' => 'decimal:2',
        'sponsorship_transfer_remaining_amount' => 'decimal:2',
        'salary_after_sponsorship' => 'decimal:2',
        'monthly_cost' => 'decimal:2',
        'contract_value' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'working_hours_per_day' => 'integer',
        'contract_duration_months' => 'integer',
        'contract_duration_days' => 'integer',
        'ingaz_from' => 'date',
        'ingaz_to' => 'date',
        'payment_confirmed_at' => 'datetime',
        'additional_terms' => 'array',
    ];

    // العلاقات
    public function rentalContract()
    {
        return $this->belongsTo(RentalContract::class, 'rental_contract_id');
    }

    public function paymentConfirmedBy()
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'payment_confirmed_by');
    }

    public function getPaymentReceiptUrlAttribute(): ?string
    {
        if (!$this->payment_receipt_path) {
            return null;
        }

        return Storage::disk('public')->url($this->payment_receipt_path);
    }

    public function getClosureClientIbanAttachmentUrlAttribute(): ?string
    {
        if (!$this->closure_client_iban_attachment_path) {
            return null;
        }

        return Storage::disk('public')->url($this->closure_client_iban_attachment_path);
    }

    // Helper Methods
    public function calculateContractValue()
    {
        if ($this->monthly_cost && $this->contract_duration_months) {
            return $this->monthly_cost * $this->contract_duration_months;
        }
        return 0;
    }

    public function calculateIngazTo()
    {
        if ($this->ingaz_from && $this->contract_duration_days) {
            return $this->ingaz_from->addDays($this->contract_duration_days);
        }
        return null;
    }

    public function calculateDailyRate()
    {
        if ($this->monthly_salary && $this->working_hours_per_day) {
            // افتراض 30 يوم في الشهر
            return ($this->monthly_salary / 30) / $this->working_hours_per_day;
        }
        return 0;
    }

    // Mutators
    public function setContractValueAttribute($value)
    {
        // حساب قيمة العقد تلقائياً إذا لم تكن محددة
        if (!$value && $this->monthly_cost && $this->contract_duration_months) {
            $this->attributes['contract_value'] = $this->monthly_cost * $this->contract_duration_months;
        } else {
            $this->attributes['contract_value'] = $value;
        }
    }

    public function setIngazToAttribute($value)
    {
        // حساب تاريخ انتهاء الإنجاز تلقائياً إذا لم يكن محدداً
        if (!$value && $this->ingaz_from && $this->contract_duration_days) {
            $this->attributes['ingaz_to'] = $this->ingaz_from->addDays($this->contract_duration_days);
        } else {
            $this->attributes['ingaz_to'] = $value;
        }
    }
}
