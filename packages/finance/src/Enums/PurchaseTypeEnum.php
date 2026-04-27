<?php

namespace Nami\Finance\Enums;

enum PurchaseTypeEnum: string
{
    case Purchase = "purchase";
    case BackPurchase = "back_purchase";

    public function lang()
    {
        return match ($this) {
            PurchaseTypeEnum::Purchase => __("finance::finance.purchase"),
            PurchaseTypeEnum::BackPurchase => __("finance::finance.back_purchase"),
        };
    }
}
