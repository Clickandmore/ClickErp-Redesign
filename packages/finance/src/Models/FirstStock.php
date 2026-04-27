<?php

namespace Nami\Finance\Models;

class FirstStock extends Finance
{
    protected $table ='fn_first_stocks';

    protected $fillable =
        [
            'date',
            'stock_id',
            'status',
        ];


    public function stock()
    {
        return $this->belongsTo(Stock::class,'stock_id');
    }

    public function firstStockItems()
    {
        return $this->hasMany(FirstStockItem::class,'first_stock_id');
    }


}
