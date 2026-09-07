<?php

namespace Nami\Finance\Models;

use App\Models\Concerns\AssignsBranch;
use App\Models\Concerns\BranchScoped;

class PurchaseOrder extends Finance
{
    use AssignsBranch, BranchScoped;

    protected $table ='fn_purchase_orders';

    protected $fillable =
        [
        'branch_id',
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
