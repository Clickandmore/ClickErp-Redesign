<?php

namespace Nami\Finance\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemCollectDetail extends Finance
{
    use HasFactory;

    protected $table ='fn_item_collect_details';
    protected $fillable = ['item_collect_id','item_id','stock_id','order_amount'];

    public function itemCollect(){
        return $this->belongsTo(ItemCollect::class,"item_collect_id","id");
    }
    public function item(){
        return $this->belongsTo(Item::class,"item_id","id");
    }

    public function stock(){
        return $this->belongsTo(Stock::class,"stock_id","id");
    }

}
