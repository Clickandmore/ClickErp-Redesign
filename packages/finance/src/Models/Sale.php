<?php

namespace Nami\Finance\Models;

use App\Models\ingaz\Invoice;
use App\Models\RentalContractInvoice;
use App\Models\auth\Admin;
use Nami\Finance\Models\ZatcaInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;
    protected $table = 'fn_sales';
    protected $guarded = [];


    public function client()
    {
        return $this->belongsTo(Client::class,'client_id');
    }

    public function saleAttachments()
    {
        return $this->hasMany(SaleAttachment::class, 'sale_id');
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class,'sale_id');
    }

    // دالة لحساب إجمالي المشتريات
    public function totalPurchaseAmount()
    {
        return $this->items()->sum(DB::raw('amount * purchase_price'));
    }

    // دالة لحساب إجمالي المبيعات
    public function totalSalesAmount()
    {
        return $this->items()->sum(DB::raw('final_total'));
    }

    // Accessor لحساب الربح
    public function getProfitAttribute()
    {
        return number_format($this->totalSalesAmount() - $this->totalPurchaseAmount() - $this->items()->sum(DB::raw('tax_value')), 2);
    }

    // Accessor لحساب هامش الربح كنسبة مئوية
    public function getProfitMarginAttribute()
    {
        $totalSalesAmount = $this->totalSalesAmount();
        $profit = $this->totalSalesAmount() - $this->totalPurchaseAmount() - $this->items()->sum(DB::raw('tax_value'));

        // إذا كان إجمالي المبيعات غير صفر، حساب هامش الربح
        if ($totalSalesAmount > 0) {
            return number_format(($profit / $totalSalesAmount) * 100, 2) . '%';
        } else {
            return '0%'; // إذا كان إجمالي المبيعات صفر أو غير موجود
        }
    }

    public function getProfitWithMarginAttribute()
    {
        $profit = $this->getProfitAttribute(); // الحصول على الربح
        $profitMargin = $this->getProfitMarginAttribute(); // الحصول على هامش الربح

        // إرجاع الربح مع النسبة
        return "{$profit} ({$profitMargin})";
    }

    public function created_by()
    {
        return $this->belongsTo(Admin::class,'created_by_id');
    }

    public function stocks()
    {
        return $this->belongsToMany(Stock::class, 'fn_sale_items', 'sale_id', 'stock_id');
    }

    public function stocksNames()
    {
        // Retrieve the stock names and join them with <br>
        return $this->stocks()->distinct()->pluck('name')->implode('<br>');
    }

    public function movements()
    {
        return $this->hasMany(StockMovementSale::class, 'bill_id');
    }

    public function zatca()
    {
        return $this->belongsTo(ZatcaInvoice::class, 'zatca_invoice_id');
    }

    /**
     * The operational invoice that created this financial sale.
     *
     * The foreign key intentionally lives on invoices so manually-created
     * financial sales can continue to exist without an operational invoice.
     */
    public function sourceInvoice()
    {
        return $this->hasOne(Invoice::class, 'finance_sale_id');
    }

    public function rentalSourceInvoice()
    {
        return $this->hasOne(RentalContractInvoice::class, 'finance_sale_id');
    }

    public function originalSale()
    {
        return $this->belongsTo(self::class, 'original_sale_id');
    }

    public function recruitmentCreditNote()
    {
        return $this->hasOne(\App\Models\RecruitmentInvoiceCreditNote::class, 'finance_sale_id');
    }
}
