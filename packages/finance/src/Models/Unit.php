<?php

namespace Nami\Finance\Models;

class Unit extends Finance
{
    protected $table ='fn_units';
    protected $fillable =['title','parent_id','value'];

    public function parent() {
        return $this->belongsTo(Unit::class,'parent_id');
    }

}
