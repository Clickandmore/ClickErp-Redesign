<?php

namespace Nami\Finance\Enums;

enum SecondarySequenceEnum: int
{
    case max99 = 1;

    case max999 = 2;
    case max9999 = 3;
    case max99999 = 4;

    public function lang()
    {
        return match ($this) {
            SecondarySequenceEnum::max99 => __("finance::finance.max99"),
            SecondarySequenceEnum::max999 => __("finance::finance.max999"),
            SecondarySequenceEnum::max9999 => __("finance::finance.max9999"),
            SecondarySequenceEnum::max99999 => __("finance::finance.max99999"),
        };

    }
}
