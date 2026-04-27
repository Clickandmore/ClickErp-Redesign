<?php

namespace Nami\Finance\Models;

use Nami\Finance\Enums\StockStatusEnum;

class Stock extends Finance
{
    protected $guarded =[];

    protected $table = 'fn_stocks';
    protected $fillable =['name', 'phone', 'address','status','storekeeper', 'notes', 'code'];

    protected $casts = [
        'status'=> StockStatusEnum::class,
    ];



    public function firstStock()
    {
        return $this->hasMany(FirstStock::class,'stock_id');
    }


}
