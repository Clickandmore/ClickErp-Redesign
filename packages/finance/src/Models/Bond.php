<?php

namespace Nami\Finance\Models;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bond extends Finance
{
    use HasFactory;

    protected $table ='fn_bonds';
    protected $fillable = ['type','payment_type','bond_book_num','debit_account_id',
        'credit_account_id','value','date','statement','file','transfer_number','code',
        'transfer_file','is_confirm','constraint_id','created_by_id','year_id','cost_center_id',
        'is_tax','net','account_text','general_type'
    ];

    public function debitAccount(){
        return $this->belongsTo(Account::class,"debit_account_id","id");
    }

    public function creditAccount(){
        return $this->belongsTo(Account::class,"credit_account_id","id");
    }

    public function check(){
        return $this->hasOne(BondChecks::class,"bond_id","id");
    }

    public function costCenter(){
        return $this->belongsTo(CostCenter::class,"cost_center_id");
    }

    public function constraint()
    {
        return $this->belongsTo(Constraint::class,'constraint_id');
    }

    public function bondTax()
    {
        return $this->hasOne(BondTax::class,'bond_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }

}
