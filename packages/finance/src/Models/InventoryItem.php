<?php

namespace Nami\Finance\Models;


class InventoryItem extends Finance
{
    protected $table = 'fn_inventory_items';

    // Fillable fields for mass assignment
    protected $fillable = [
        'inventory_id',
        'item_id',
        'current_amount',
        'purchase_price',
        'real_amount',
        'notes',
        'difference_amount',
        'difference_purchase_price',
    ];

    // Define relationship with Inventory model
    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    // Define relationship with Item model
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
