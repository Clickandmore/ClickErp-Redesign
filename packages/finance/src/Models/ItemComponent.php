<?php

namespace Nami\Finance\Models;


class ItemComponent extends Finance
{
    protected $table = 'fn_item_components';

    // Fillable fields for mass assignment
    protected $fillable = [
        'item_id',
        'component_item_id',
        'amount',
        'one_purchase_price',
        'total_purchase_price',
    ];

    // Relationships

    // The item that this component belongs to
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    // The component item (another item)
    public function componentItem()
    {
        return $this->belongsTo(Item::class, 'component_item_id');
    }
}
