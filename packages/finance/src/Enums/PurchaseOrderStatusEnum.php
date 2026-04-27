<?php

namespace Nami\Finance\Enums;

enum PurchaseOrderStatusEnum: string
{
    case New = "new";
    case Transfer = "transfer";

    public function lang()
    {
        return match ($this) {
            PurchaseOrderStatusEnum::New => __("finance::finance.New"),
            PurchaseOrderStatusEnum::Transfer => __("finance::finance.Transfer"),
        };
    }
}
