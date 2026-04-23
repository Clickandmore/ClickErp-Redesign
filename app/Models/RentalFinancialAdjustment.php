<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentalFinancialAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_contract_id',
        'maid_id',
        'account_id',
        'constraint_id',
        'cost_center_id',
        'type',
        'label',
        'amount',
        'notes',
        'context',
        'created_by',
        'settled_at',
        'settled_by',
        'receipt_path',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'settled_at' => 'datetime',
    ];

    public function rentalContract()
    {
        return $this->belongsTo(RentalContract::class, 'rental_contract_id');
    }

    public function maid()
    {
        return $this->belongsTo(\App\Models\ingaz\Maid::class, 'maid_id');
    }

    public function account()
    {
        return $this->belongsTo(\Nami\Finance\Models\Account::class, 'account_id');
    }

    public function constraint()
    {
        return $this->belongsTo(\Nami\Finance\Models\Constraint::class, 'constraint_id');
    }

    public function costCenter()
    {
        return $this->belongsTo(\Nami\Finance\Models\CostCenter::class, 'cost_center_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'created_by');
    }

    public function settledBy()
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'settled_by');
    }
}
