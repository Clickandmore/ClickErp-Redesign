<?php

namespace Nami\Finance\Models;

use App\Models\auth\Admin;
use Nami\Finance\Enums\ConstraintTypiesEnum;

class Constraint extends Finance
{
    protected $table = 'fn_constraints';

    protected $fillable = ['type','value','date','statement','year_id','code','created_by_id'];

    protected $casts = [
        'type' => ConstraintTypiesEnum::class
    ];


    protected $with = ['admin'];

    public function constraintDetails()
    {
        return $this->hasMany(ConstraintDetails::class,'constraint_id','id');
    }

    public function created_by()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }

    public function accounts()
    {
        return $this->belongsToMany(Account::class, 'fn_constraint_details')
            ->select('fn_accounts.id', 'fn_accounts.name');
    }

    public function files()
    {
        return $this->hasMany(ConstraintFile::class, 'constraint_id', 'id');
    }

    public function calculateTotal($constraintDetails, $type): string
    {
        $accounts = '';
        $total = 0;
        foreach ($constraintDetails as $con) {
            if ($con->type === $type) {
                $accounts .= $con->account->name . " <br> ";
                $total += $con->type === 'debtor' ? $con->debtor_value : $con->creditor_value;
            }
        }
        return $accounts . " بأجمالي " . ($total ?? 0);
    }


    public function admin()
    {
        return $this->belongsTo(Admin::class,'created_by_id');
    }

    public function accountsOfConstraint()
    {
        return $this->belongsToMany(Account::class, 'fn_constraint_details')
            ->select('fn_accounts.id');
    }

    public function bond()
    {
        return $this->hasOne(Bond::class, 'constraint_id', 'id');
    }
    public function transfer_entry()
    {
        return $this->hasOne(AccountTransfer::class, 'constraint_id', 'id');
    }



    public function getShowIdAttribute()
    {
        switch ($this->type) {
            case ConstraintTypiesEnum::SarfBond:
                return $this->bond->id;
                break;
           case ConstraintTypiesEnum::QapdBond:
                return $this->bond->id;
                break;
           case ConstraintTypiesEnum::TransferEntry:
                return $this->transfer_entry->id;
                break;
            default:
                return $this->id;
                break;
        }
    }


    public function depreciationPrinciple()
    {
        return $this->hasOne(DepreciationPrinciple::class);
    }

}
