<?php

namespace Nami\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovementSale extends Model
{
    use HasFactory;
    protected $fillable = ['bill_id','stock_movement_id'];
    protected $table = 'fn_stock_movement_sales';


    public function bill()
    {
        return $this->belongsTo(Sale::class,'bill_id');
    }

    public function movement()
    {
        return $this->belongsTo(StockMovement::class,'stock_movement_id');
    }
}
