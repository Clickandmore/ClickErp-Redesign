<?php

namespace Nami\Finance\Models;

use App\Models\auth\Admin;

class Client extends Finance
{
    protected $table ='fn_clients';

    protected $fillable =
        [
            'name',
            'code',
            'address',
            'phone',
            'email',
            'city',
            'postal_zone',
            'is_company',
            'tax_number',
            'created_by_id',
            'days_credit_period',
            'client_category_id',
            'account_id',
            'days_number', 'exceeding_minimum_cash_limit','exceeding_minimum_days_limit',
            'status',
            'credit_limit',
            'image',
        ];



    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(ClientCategory::class, 'client_category_id', 'id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class,'created_by_id');
    }

    public function images()
    {
        return $this->hasMany(ClientImage::class, 'client_id');
    }

}
