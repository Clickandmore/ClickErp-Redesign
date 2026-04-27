<?php

namespace Nami\Finance\Models;


use App\Models\auth\Admin;

class Inventory extends Finance
{
    protected $table = 'fn_inventories';

    // Fillable fields for mass assignment
    protected $fillable = [
        'stock_id',
        'date',
        'responsible',
        'notes',
        'file',
        'code',
        'status',
        'total_purchase_price',
        'created_by_id',
    ];

    // Define relationship with Stock model
    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }

    // Define relationship with Admin model
    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }

    // Define relationship with InventoryItem model
    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class, 'inventory_id');
    }
}

