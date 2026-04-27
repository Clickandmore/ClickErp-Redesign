<?php

namespace Nami\Finance\Enums;

enum CostCenterTypeEnum: string
{
    case General = "fn_cost_centers";
    case Stocks = "stocks";
    case Emlpoyees = "employees";

    public function lang()
    {
        return match ($this) {
            CostCenterTypeEnum::General => __("finance::finance.not_associated"),
            CostCenterTypeEnum::Stocks => __("finance::finance.stocks"),
            CostCenterTypeEnum::Emlpoyees => __("finance::finance.employees"),
        };
    }
}
