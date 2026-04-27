<?php

namespace Nami\Finance\Models;


use App\Models\auth\Admin;

class ClientCategory extends Finance
{
    protected $table ='fn_client_categories';

    protected $fillable =[ 'name' ,'created_by_id', 'code'];


    public function admin()
    {
       return $this->belongsTo(Admin::class,'created_by_id');
    }
    public function clients() {
        return $this->hasMany(Client::class,'client_category_id');
    }
}
