<?php

namespace Nami\Finance\Models;


use App\Models\auth\Admin;

class PrincipleSale extends Finance
{
    protected $table = 'fn_principle_sales';
    protected $fillable = [
        'principle_id',
        'principle_value',
        'sales_value',
        'date',
        'tax_setting_id',
        'tax_setting_value',
        'total_tax_value',
        'tax_account_id',
        'debit_account_id',
        'constraint_id',
        'financial_value',
        'total_price',
        'notes',
        'created_by_id'
    ];

    public function principle()
    {
        return $this->belongsTo(Principle::class, 'principle_id');
    }

    public function tax_setting()
    {
        return $this->belongsTo(TaxSetting::class, 'tax_setting_id');
    }

    public function tax_account()
    {
        return $this->belongsTo(Account::class, 'tax_account_id');
    }

    public function debit_account()
    {
        return $this->belongsTo(Account::class, 'debit_account_id');
    }

    public function constraint()
    {
        return $this->belongsTo(Constraint::class, 'constraint_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class,'created_by_id');
    }
}
