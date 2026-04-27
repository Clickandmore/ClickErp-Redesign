<?php

namespace Nami\Hr\Enums;

enum AllowanceTypeis: int
{
    case Day = 1;
    case VALUE = 2;
    case PERCENTAGE = 3;

    public function lang()
    {
        return match ($this) {
            AllowanceTypeis::Day => __("hr::hr.day"),
            AllowanceTypeis::VALUE => __("hr::hr.static_value"),
            AllowanceTypeis::PERCENTAGE => __("hr::hr.percentage of employee salary"),
        };
    }
}
