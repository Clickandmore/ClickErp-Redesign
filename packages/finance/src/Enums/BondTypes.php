<?php

namespace Nami\Finance\Enums;

enum BondTypes: string
{
    case Sarf = "sarf";
    case Qabd = "qabd";
    case BillQabd = "bill-qabd";

    public function lang()
    {
        return match ($this) {
            BondTypes::Sarf => __("finance::finance.sarf"),
            BondTypes::Qabd => __("finance::finance.qabd"),
            BondTypes::BillQabd => __("finance::finance.bill-qabd"),
        };
    }
}
