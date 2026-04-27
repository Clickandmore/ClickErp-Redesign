<?php

namespace Nami\Finance\Models;

class SupplierFile extends Finance
{
    protected $table ='fn_supplier_files';
    protected $fillable =['file','supplier_id'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
