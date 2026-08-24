<?php

namespace Nami\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SaleItem extends Finance
{
    use HasFactory;
    protected $table = 'fn_sale_items';
    protected $guarded = [];

    public function stock()
    {
        return $this->belongsTo(Stock::class,'stock_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class,'sale_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class,'item_id');
    }
}
