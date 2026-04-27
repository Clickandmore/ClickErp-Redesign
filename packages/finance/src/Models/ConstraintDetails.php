<?php

namespace Nami\Finance\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class ConstraintDetails extends Finance
{
    use SoftDeletes;

    protected $table = 'fn_constraint_details';

    protected $with = ['constraint', 'account', 'costCenter'];

    protected $fillable = ['constraint_id', 'date', 'type', 'account_id', 'cost_center_id', 'debtor_value', 'creditor_value', 'statement', 'year_id'];

    public function constraint()
    {
        return $this->belongsTo(Constraint::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function counterAccount($ignore = null)
    {
        return $this->where('constraint_id', $this->constraint_id)
            ->where('account_id', '!=', $ignore)
            ->first()
            ->account
            ->name ?? "";
    }


}
