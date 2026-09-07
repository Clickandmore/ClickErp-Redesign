<?php

namespace Nami\Finance\Models;

use App\Models\Concerns\AssignsBranch;
use App\Models\Concerns\BranchScoped;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockMovement extends Finance
{
    use AssignsBranch, BranchScoped;

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
