<?php

namespace Nami\Finance\Models;


class SalesOrderItem extends Finance
{
    protected $table = 'fn_sales_order_items';

    // Fillable fields for mass assignment
    protected $fillable = [
        'sales_order_id',
        'item_id',
        'sales_price',
        'sales_amount',
        'discount_per',
        'discount_value',
        'tax_per',
        'tax_value',
        'total_sales_price',
        'final_total'
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}


