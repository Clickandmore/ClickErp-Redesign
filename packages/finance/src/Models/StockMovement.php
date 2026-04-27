<?php

namespace Nami\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table ='fn_stock_movements';


    public function stock()
    {
        return $this->belongsTo(Stock::class,'fn_stock_id');
    }

    public function details()
    {
        return $this->hasMany(StockMovementItem::class,'fn_stock_movement_id');
    }

    public function setNoteAttribute($value)
    {
        $this->attributes['note'] = $value ?: 'لا يوجد';
    }

}
