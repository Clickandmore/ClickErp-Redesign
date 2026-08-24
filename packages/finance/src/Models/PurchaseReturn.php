<?php

namespace Nami\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseReturn extends Finance
{
    use HasFactory;
    protected $table = 'purchase_returns';
    protected $fillable = ['purchase_id','return_purchase_id'];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class,'purchase_id');
    }

    public function return_purchase()
    {
        return $this->belongsTo(Purchase::class,'return_purchase_id');
    }
}
