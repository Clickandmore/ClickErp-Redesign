<?php

namespace Nami\Finance\Models;

use App\Models\auth\Admin;

class Supplier extends Finance
{
    protected $guarded =[];

    protected $table = 'fn_suppliers';

    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'email',
        'tax_number',
        'tax_image',
        'commercial_register_number',
        'commercial_register_image',
        'days_number', 'exceeding_minimum_cash_limit','exceeding_minimum_days_limit',
        'credit_limit',
        'account_id',
        'created_by_id',
        'national_address',
        'supplier_type_id'
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    public function files()
    {
        return $this->hasMany(SupplierFile::class, 'supplier_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }

    public function supplierType()
    {
        return $this->belongsTo(SupplierType::class, 'supplier_type_id');
    }
}
