<?php

namespace Nami\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierType extends Model
{
    use HasFactory;
    protected $table = 'fn_supplier_types';
    public $guarded = [];
}
