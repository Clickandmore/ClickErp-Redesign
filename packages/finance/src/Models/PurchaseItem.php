<?php

namespace Nami\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseItem extends Finance
{
    use HasFactory;
    protected $table = 'fn_purchase_items';
    protected $guarded = [];

    public function stock()
    {
        return $this->belongsTo(Stock::class,'stock_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class,'purchase_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class,'item_id');
    }
}
