<?php

namespace Nami\Finance\Models;


use App\Models\auth\Admin;

class Box extends Finance
{
    protected $table ='fn_boxes';
    protected $fillable =['title','account_id', 'created_by_id'];


    public function admin()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }
    public function account(){
        return $this->belongsTo(Account::class, 'account_id');
    }
}
