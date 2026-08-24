<?php

namespace Nami\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierType extends Finance
{
    use HasFactory;
    protected $table = 'fn_supplier_types';
    public $guarded = [];
}
