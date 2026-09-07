<?php

namespace Nami\Finance\Models;

use App\Models\Concerns\AssignsBranch;
use App\Models\Concerns\BranchScoped;


use App\Models\auth\Admin;

class SalesOrder extends Finance
{
    use AssignsBranch, BranchScoped;

    protected $table = 'fn_sales_orders';

    // Fillable fields for mass assignment
    protected $fillable = [
        'branch_id',
        'code',
        'date',
        'client_id',
        'created_by_id',
        'total_price',
        'total_discount_value',
        'total_tax_value',
        'final_total',
        'status',
        'notes',
        'file'
    ];

    public function salesOrderItems()
    {
        return $this->hasMany(SalesOrderItem::class, 'sales_order_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }

    public function additionItems()
    {
        return $this->hasMany(AdditionItem::class, 'sales_order_id');
    }
}


