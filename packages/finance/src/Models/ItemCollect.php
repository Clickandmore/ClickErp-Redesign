<?php

namespace Nami\Finance\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemCollect extends Finance
{
    use HasFactory;

    protected $table ='fn_item_collects';
    protected $fillable = ['code','to_stock_id','item_id','date','amount','attachment','notes','status'];

    public function toStock(){
        return $this->belongsTo(Stock::class,"to_stock_id","id");
    }

    public function item(){
        return $this->belongsTo(Item::class,"item_id","id");
    }

    public function details()
    {
        return $this->hasMany(ItemCollectDetail::class, 'item_collect_id', 'id');
    }
}
