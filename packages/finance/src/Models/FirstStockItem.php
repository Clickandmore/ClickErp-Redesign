<?php

namespace Nami\Finance\Models;

class FirstStockItem extends Finance
{
    protected $table ='fn_first_stock_items';

    protected $fillable =
        [
            'first_stock_id',
            'item_id',
            'amount',
            'price',
            'total_price',
        ];


    public function first_stock()
    {
        return $this->belongsTo(FirstStock::class,'first_stock_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class,'item_id');
    }
}
