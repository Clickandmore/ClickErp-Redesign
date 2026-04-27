<?php

namespace Nami\Finance\Enums;

enum SalesItemStatusEnum: string
{
    case New = "new";
    case Confirmed = "confirmed";

    public function lang()
    {
        return match ($this) {
            SalesItemStatusEnum::New => __("finance::finance.new"),
            SalesItemStatusEnum::Confirmed => __("finance::finance.confirmed"),
        };
    }
}
