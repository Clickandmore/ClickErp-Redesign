<?php

namespace Nami\Finance\Models;
use App\Models\Nami\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BondTax extends Finance
{
    use HasFactory;
    protected $fillable = ['bond_id','tax_rate','tax_amount','tax_setting_id','tax_account_id'];
    protected $table = 'fn_bonds_taxs';

    public function bond()
    {
        return $this->belongsTo(Bond::class,'bond_id');
    }

    public function taxSetting(){
        return $this->belongsTo(TaxSetting::class,"tax_setting_id");
    }

    public function account()
    {
        return $this->belongsTo(Account::class,'tax_account_id');
    }
}
