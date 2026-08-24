<?php

namespace Nami\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockMovementPurchase extends Finance
{
    use HasFactory;
    protected $fillable = ['bill_id','stock_movement_id'];
    protected $table = 'fn_stock_movement_purchases';


    public function bill()
    {
        return $this->belongsTo(Purchase::class,'bill_id');
    }

    public function movement()
    {
        return $this->belongsTo(StockMovement::class,'stock_movement_id');
    }
}
