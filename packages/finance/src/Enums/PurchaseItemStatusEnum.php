<?php

namespace Nami\Finance\Enums;

enum PurchaseItemStatusEnum: string
{
    case New = "new";
    case Confirmed = "confirmed";

    public function lang()
    {
        return match ($this) {
            PurchaseItemStatusEnum::New => __("finance::finance.new"),
            PurchaseItemStatusEnum::Confirmed => __("finance::finance.confirmed"),
        };
    }
}
