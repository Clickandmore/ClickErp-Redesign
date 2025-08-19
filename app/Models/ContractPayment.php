<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
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
        'remaining_amount' => 'decimal:2'
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
