<?php

namespace Nami\Hr\Enums;

enum AdvanceValueStatus : string
{
    case New = "new";
    case Paid = "paid";

    public function lang()
    {
        return match ($this) {
            AdvanceValueStatus::New => __('hr::hr.new'),
            AdvanceValueStatus::Paid => __('hr::hr.paid'),
        };
    }
}
