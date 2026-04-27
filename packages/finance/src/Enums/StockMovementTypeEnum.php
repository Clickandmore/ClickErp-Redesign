<?php
namespace Nami\Finance\Enums;

enum StockMovementTypeEnum: int{


    case NormalAddition = 1; // اذن اضافة عادي
    case PurchaseAdditional = 2;  // اذن اضافة فاتورة مشتريات
    case BackSaleAddition = 3;

    case TransferTo = 4; // اذن تحويل الى

    case SaleNormal = 5; // اذن صرف عادي
    case SalePurchase = 6; // اذن صرف فاتورة مشتريات
    case TransferFrom = 7; // اذن تحويل من

    public function lang()
    {
        return match ($this) {
            self::NormalAddition => __('finance::finance.Normal Additional Permit'),
            self::PurchaseAdditional => __('finance::finance.Purchase Additional Permit'),
            self::BackSaleAddition => __('finance::finance.Back Sale Additional Permit'),
            self::TransferTo => __('finance::finance.Transfer To'),
            self::SaleNormal => __('finance::finance.Sale normal addition'),
            self::SalePurchase => __('finance::finance.Sale additional'),
            self::TransferFrom => __('finance::finance.Transfer From'),
        };
    }
}
