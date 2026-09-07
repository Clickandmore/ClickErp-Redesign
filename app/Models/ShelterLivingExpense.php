<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShelterLivingExpense extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    protected $fillable = [
        'shelter_id',
        'expense_account_id',
        'payment_account_id',
        'payment_source_type',
        'constraint_id',
        'expense_date',
        'title',
        'total_amount',
        'worker_count',
        'amount_per_worker',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'total_amount' => 'decimal:2',
        'amount_per_worker' => 'decimal:2',
    ];

    public function allocations()
    {
        return $this->hasMany(ShelterLivingExpenseAllocation::class);
    }

    public function shelter()
    {
        return $this->belongsTo(ShelterPlaces::class, 'shelter_id');
    }

    public function expenseAccount()
    {
        return $this->belongsTo(\Nami\Finance\Models\Account::class, 'expense_account_id');
    }

    public function paymentAccount()
    {
        return $this->belongsTo(\Nami\Finance\Models\Account::class, 'payment_account_id');
    }

    public function constraint()
    {
        return $this->belongsTo(\Nami\Finance\Models\Constraint::class, 'constraint_id');
    }
}
