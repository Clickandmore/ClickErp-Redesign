<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\Account;

class ShelterAgentCostRecovery extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['amount' => 'decimal:2', 'date' => 'date', 'reversed_at' => 'datetime'];

    public function constraint() { return $this->belongsTo(Constraint::class); }
    public function reversalConstraint() { return $this->belongsTo(Constraint::class, 'reversal_constraint_id'); }
    public function officeAccount() { return $this->belongsTo(Account::class, 'office_account_id'); }
}
