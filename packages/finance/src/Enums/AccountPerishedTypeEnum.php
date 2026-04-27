<?php

namespace Nami\Finance\Enums;

enum AccountPerishedTypeEnum: string
{
    case General = "general";
    case Private = "private";

    public function lang()
    {
        return match ($this) {
            AccountPerishedTypeEnum::General => __("finance::finance.general"),
            AccountPerishedTypeEnum::Private => __("finance::finance.private"),
        };
    }
}
