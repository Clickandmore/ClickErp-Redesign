<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShelterLivingExpenseAllocation extends Model
{
    protected $fillable = [
        'shelter_living_expense_id',
        'contract_id',
        'maid_id',
        'cost_center_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function expense()
    {
        return $this->belongsTo(ShelterLivingExpense::class, 'shelter_living_expense_id');
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function maid()
    {
        return $this->belongsTo(\App\Models\ingaz\Maid::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(\Nami\Finance\Models\CostCenter::class, 'cost_center_id');
    }
}
