<?php
namespace Nami\Finance\Models;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaxSetting extends Model
{
    use HasFactory;

    protected $table = 'fn_tax_settings';
    protected $fillable = ['account_id', 'name','value', 'created_by_id'];

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function bondTax()
    {
        return $this->hasMany(BondTax::class, 'tax_setting_id');
    }

    public function confirmSetting()
    {
        return $this->hasMany(ConfirmSetting::class, 'value', 'id');
    }

    public function itemTax()
    {
        return $this->hasMany(ItemTax::class, 'tax_setting_id');
    }

    public function principle()
    {
        return $this->hasMany(Principle::class, 'first_tax');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }

    public function hasChildrens()
    {
        return  $this->bondTax()->count() > 0 ||
                $this->confirmSetting()->count() > 0 ||
                $this->itemTax()->count() > 0 ||
                $this->principle()->count() > 0;
    }
}
