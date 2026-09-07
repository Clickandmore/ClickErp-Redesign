<?php

namespace App\Models;

use App\Models\Concerns\AssignsBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Concerns\BranchScoped;

class ContractPayment extends Model
{
    use AssignsBranch, HasFactory, BranchScoped;

    protected $fillable = [
        'contract_id',
        'branch_id',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'payment_date',
        'notes'
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'branch_id' => 'integer',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromContract($this->contract_id)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
