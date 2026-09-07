<?php

namespace Nami\Finance\Models;

use App\Models\Concerns\AssignsBranch;
use App\Models\Concerns\BranchScoped;

use Nami\Finance\Enums\StockStatusEnum;

class Stock extends Finance
{
    use AssignsBranch, BranchScoped;

    protected $guarded =[];

    protected $table = 'fn_stocks';
    protected $fillable =[
        'branch_id','name', 'phone', 'address','status','storekeeper', 'notes', 'code'];

    protected $casts = [
        'status'=> StockStatusEnum::class,
    ];



    public function firstStock()
    {
        return $this->hasMany(FirstStock::class,'stock_id');
    }


}
