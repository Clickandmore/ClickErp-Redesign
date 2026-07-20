<?php

namespace App\Models;

use App\Models\Concerns\AssignsBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class RentalContractExtension extends Model
{
    use AssignsBranch, HasFactory;

    protected $fillable = [
        'rental_contract_id',
        'branch_id',
        'extension_months',
        'extension_days',
        'extension_value',
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
        'extension_value' => 'decimal:2',
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

    public function getPaymentReceiptUrlAttribute(): ?string
    {
        if (!$this->payment_receipt_path) {
            return null;
        }

        return Storage::disk('public')->url($this->payment_receipt_path);
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromRentalContract($this->rental_contract_id)
            ?? $this->resolveBranchIdFromAdmin($this->created_by)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
