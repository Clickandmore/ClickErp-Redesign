<?php

namespace Nami\Finance\Models;

class PurchaseOrderItem extends Finance
{
    protected $table ='fn_purchase_order_items';

    protected $fillable =
        [
            'purchase_order_id',
            'item_id',
            'amount',
            'price',
            'total_price',
            'discount_per',
            'discount_value',
            'tax_per',
            'tax_value',
        ];


    public function purchase_order()
    {
        return $this->belongsTo(PurchaseOrder::class,'purchase_order_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class,'item_id');
    }

}
