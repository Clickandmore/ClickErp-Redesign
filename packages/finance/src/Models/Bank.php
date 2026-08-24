<?php

namespace Nami\Finance\Models;

use App\Models\auth\Admin;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bank extends Finance
{
    use Translatable, HasFactory;
    ## Initialization
    protected $table = 'banks';
    protected $with = ['translations'];
    protected $translationForeignKey = 'bank_id';
    public $translationModel = BankTranslation::class;

    public $translatedAttributes = ['name'];
    protected $fillable = ['image', 'created_by_id'];

    ## Relations
    public function bank_accounts()
    {
        return $this->hasMany(BankAccount::class, 'bank_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }
    public function accounts()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

}
