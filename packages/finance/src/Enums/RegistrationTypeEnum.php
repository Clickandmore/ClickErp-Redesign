<?php

namespace Nami\Finance\Enums;

enum RegistrationTypeEnum: string
{
    case Purchase = "purchase";
    case FirstTerm = "first_term";

    public function lang()
    {
        return match ($this) {
            RegistrationTypeEnum::Purchase => __("finance::finance.purchase"),
            RegistrationTypeEnum::FirstTerm => __("finance::finance.first_term"),
        };
    }
}
