<?php

namespace Nami\Hr\Enums;

enum AdvanceDeliveryMethod : int
{
    case Cash = 1;
    case Transfer = 2;

    public function lang()
    {
        return match ($this) {
            AdvanceDeliveryMethod::Cash =>__('hr::hr.cash'),
            AdvanceDeliveryMethod::Transfer => __('hr::hr.transfer'),
        };
    }


}
