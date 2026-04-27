<?php

namespace Nami\Finance\Models;


use App\Models\auth\Admin;

class Conversion extends Finance
{
    protected $table = 'fn_conversions';

    // Fillable fields for mass assignment
    protected $fillable = [
        'code',
        'date',
        'status',
        'notes',
        'file',
        'from_stock_id',
        'to_stock_id',
        'created_by_id',
        'stock_movement_id',
        'add_movement_id',
    ];

    public function fromStock()
    {
        return $this->belongsTo(Stock::class, 'from_stock_id');
    }

    public function toStock()
    {
        return $this->belongsTo(Stock::class, 'to_stock_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }

    public function conversionItems()
    {
        return $this->hasMany(ConversionItem::class, 'conversion_id');
    }
}

