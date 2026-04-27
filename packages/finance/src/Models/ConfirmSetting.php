<?php

namespace Nami\Finance\Models;

use App\Models\auth\Admin;

class ConfirmSetting extends Finance
{
    protected $table ='fn_confirm_settings';
    protected $fillable =['title','value','status','created_by_id'];

    public function tax_rate_revenue()
    {
        return $this->hasOne(TaxSetting::class, 'id', 'value')
                    ->where('title', 'tax_rate_revenue');
    }

    public function tax_rate_expenditure()
    {
        return $this->hasOne(TaxSetting::class, 'id', 'value')
                    ->where('title', 'tax_rate_expenditure');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class,'created_by_id');
    }
}
