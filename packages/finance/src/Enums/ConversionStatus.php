<?php

namespace Nami\Finance\Enums;

enum ConversionStatus: string
{
    case New = "new";
    case MakeSarf = "make_sarf";
    case Ended = "ended";

    public function lang()
    {
        return match ($this) {
            ConversionStatus::New => __("finance::finance.conversion_new"),
            ConversionStatus::MakeSarf => __("finance::finance.make_sarf"),
            ConversionStatus::Ended => __("finance::finance.ended"),
        };
    }
}
