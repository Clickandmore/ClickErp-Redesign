<?php

namespace Nami\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockStatus extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table ='fn_stock_statuses';

    public function stock()
    {
        return $this->belongsTo(Stock::class,'fn_stock_id');
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class,'fn_stock_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
