<?php

namespace Nami\Finance\Enums;

enum SalesOrderStatusEnum: string
{
    case New = "new";
    case Transfer = "transfer";

    public function lang()
    {
        return match ($this) {
            SalesOrderStatusEnum::New => __("finance::finance.New"),
            SalesOrderStatusEnum::Transfer => __("finance::finance.Transfer"),
        };
    }
}
