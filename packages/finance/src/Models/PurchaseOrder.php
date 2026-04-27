<?php

namespace Nami\Finance\Models;

class PurchaseOrder extends Finance
{
    protected $table ='fn_purchase_orders';

    protected $fillable =
        [
            'code',
            'date',
            'supplier_id',
            'status',
        ];


    public function suppiler()
    {
        return $this->belongsTo(Supplier::class,'supplier_id');
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class,'purchase_order_id');
    }

}
