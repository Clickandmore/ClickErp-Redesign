<?php

namespace Nami\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovementItem extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table ='fn_stock_movement_items';

    public function item()
    {
        return $this->belongsTo(Item::class,'item_id');
    }

    public function movement()
    {
        return $this->belongsTo(StockMovement::class,'fn_stock_movement_id');
    }
}
