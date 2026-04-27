<?php

namespace Nami\Finance\Enums;

enum SalesTypeEnum: string
{
    case Sales = "sales";
    case BackSales = "back_sales";

    public function lang()
    {
        return match ($this) {
            SalesTypeEnum::Sales => __("finance::finance.sales"),
            SalesTypeEnum::BackSales => __("finance::finance.back_sales"),
        };
    }
}
