<?php

namespace Nami\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdditionItem extends Model
{
    use HasFactory;
    ## Initialization
    protected $table = 'addition_items';

    protected $fillable = ['name','code','unit','amount', 'price', 'discount_per', 'discount_value', 'tax_per', 'tax_value', 'sales_order_id', 'final_price'];

    ## Relations
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }
}
