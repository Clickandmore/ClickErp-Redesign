<?php

namespace Nami\Finance\Enums;

enum StockStatusEnum: string
{
    case Active = "active";
    case DisActive = "desactive";



    public function lang()
    {
        return match ($this) {
            StockStatusEnum::Active => __("finance::finance.active"),
            StockStatusEnum::DisActive => __("finance::finance.disactive"),
        };

    }
}
