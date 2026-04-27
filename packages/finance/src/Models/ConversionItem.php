<?php

namespace Nami\Finance\Models;



class ConversionItem extends Finance
{
    protected $table = 'fn_conversion_items';

    // Fillable fields for mass assignment
    protected $fillable = [
        'conversion_id',
        'item_id',
        'purchase_price',
        'available_amount',
        'transfer_amount',
        'notes',
    ];

    public function conversion()
    {
        return $this->belongsTo(Conversion::class, 'conversion_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}

