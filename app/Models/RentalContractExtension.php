<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class RentalContractExtension extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_contract_id',
        'extension_months',
        'extension_days',
        'start_date',
        'end_date',
        'payment_status',
        'payment_receipt_path',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'extension_months' => 'integer',
        'extension_days' => 'integer',
    ];

    public function contract()
    {
        return $this->belongsTo(RentalContract::class, 'rental_contract_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'created_by');
    }

    public function getPaymentReceiptUrlAttribute(): ?string
    {
        if (!$this->payment_receipt_path) {
            return null;
        }

        return Storage::disk('public')->url($this->payment_receipt_path);
    }
}
