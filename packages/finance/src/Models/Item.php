<?php

namespace Nami\Finance\Models;

use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\CategoryTypes;

class Item extends Finance
{
    protected $table = 'fn_items';
    protected $with = ['itemStockStatus'];

    // Fillable fields for mass assignment
    protected $fillable = [
        'name',
        'main_category_id',
        'sub_category_id',
        'type',
        'code_string',
        'code',
        'min_order',
        'sale_unit_id',
        'sale_price',
        'purchase_unit_id',
        'purchase_price',
        'details',
        'sale_account_id',
        'stock_account_id',
        'stock_tracking',
        'is_tax',
        'image',
        'unit',
        'show_pos',
        'supplier_id',
        'show_website'
    ];


    protected $casts = [
        'type'=> CategoryTypes::class
    ];
    // Relationships

    // An item belongs to a main category
    public function mainCategory()
    {
        return $this->belongsTo(Category::class, 'main_category_id');
    }

    // An item may belong to a subcategory (nullable)
    public function subCategory()
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }

    // An item has a sale unit
    public function saleUnit()
    {
        return $this->belongsTo(Unit::class, 'sale_unit_id');
    }

    // An item has a purchase unit
    public function purchaseUnit()
    {
        return $this->belongsTo(Unit::class, 'purchase_unit_id');
    }

    // An item is linked to a sale account
    public function saleAccount()
    {
        return $this->belongsTo(Account::class, 'sale_account_id');
    }

    // An item is linked to a stock account
    public function stockAccount()
    {
        return $this->belongsTo(Account::class, 'stock_account_id');
    }

    // An ItemTax has one an item
    public function itemTax()
    {
        return $this->hasOne(ItemTax::class, 'item_id');
    }

    // The item that this component has many
    public function itemComponents()
    {
        return $this->hasMany(ItemComponent::class, 'item_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function itemStockStatus()
    {
        return $this->hasMany(StockStatus::class, 'item_id');
    }

    // Function to get the total amount for a specific stock and item
    public function getItemStockAmount($stockId, $itemId)
    {
        return $this->itemStockStatus()
            ->where('fn_stock_id', $stockId)   // Filter by stock ID
            ->where('item_id', $itemId)        // Filter by item ID
            ->sum('amount');                   // Sum the 'amount' field
    }

    public function firstStockItem()
    {
        return $this->hasMany(FirstStockItem::class, 'item_id');
    }

    public function orderPurchaseItems()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'item_id');
    }

    public function maxStockAmount()
    {
        if ($this->type === CategoryTypes::Service->value) {
            return null;
        }
        return $this->itemStockStatus()
            ->select('fn_stock_id', DB::raw('MAX(amount) as max_amount'))
            ->groupBy('fn_stock_id')
            ->orderBy('max_amount', 'DESC') // ترتيب تنازليًا للحصول على الأعلى
            ->first(); // جلب أول نتيجة
    }


}

