<?php

namespace Nami\Finance\Models;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankAccount extends Model
{
    use HasFactory;
    ## Initialization
    protected $table = 'bank_accounts';


    protected $fillable = ['name','bank_id','account_id','bank_number','iban', 'created_by_id'];

    ## Relations
    public function bank()
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

}
