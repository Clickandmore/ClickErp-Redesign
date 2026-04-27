<?php

namespace Nami\Hr\Enums;

enum ExchangeTimingEnum : string
{
    case WithSalary = "with_salary";
    case SpecificDay = "specific_day";

    public function lang()
    {
        return match ($this) {
            self::WithSalary => __('hr::hr.WithSalary'),
            self::SpecificDay => __('hr::hr.SpecificDay'),
        };
    }

}
