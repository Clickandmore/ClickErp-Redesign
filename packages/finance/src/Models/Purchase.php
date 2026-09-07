<?php

namespace Nami\Finance\Models;

use App\Models\Concerns\AssignsBranch;
use App\Models\Concerns\BranchScoped;

use App\Models\Nami\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purchase extends Finance
{
    // عزل الفروع: المستند يخص فرع منشئه، والسجلات القديمة للفرع الرئيسي.
    use AssignsBranch, BranchScoped;

    use HasFactory;
    protected $table = 'fn_purchases';
    protected $fillable = [
        'branch_id',
        'created_by_id', 'code', 'supplier_id', 'credit_supplier_id', 'supplier_bill_number', 'date', 'notes', 'total_item', 'tax_per', 'tax_value', 'tax_id', 'discount', 'final_total', 'paid_amount', 'is_confirmed', 'constraint_id', 'type', 'payment_method', 'file', 'created_at', 'updated_at',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class,'supplier_id');
    }

    public function creditSupplier()
    {
        return $this->belongsTo(Account::class,'credit_supplier_id');
    }

    public function constraint()
    {
        return $this->belongsTo(Constraint::class,'constraint_id');
    }

//    public function tax()
//    {
//        return $this->belongsTo(Tax::class,'tax_id');
//    }

    public function purchaseItem()
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }

    public function stocks()
    {
        return $this->belongsToMany(Stock::class, 'fn_purchase_items', 'purchase_id', 'stock_id');
    }

    public function stocksNames()
    {
        // Retrieve the stock names and join them with <br>
        return $this->stocks()->distinct()->pluck('name')->implode('<br>');
    }

    public function movement()
    {
        return $this->hasOne(StockMovementPurchase::class, 'bill_id');
    }

    public function movements()
    {
        return $this->hasMany(StockMovementPurchase::class, 'bill_id');
    }
}
