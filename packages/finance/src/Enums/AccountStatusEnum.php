<?php

namespace Nami\Finance\Enums;

enum AccountStatusEnum: string
{
    case Debtor = "debtor";
    case Creditor = "creditor";



    public function lang()
    {
        return match ($this) {
            AccountStatusEnum::Debtor => __("finance::finance.debtor"),
            AccountStatusEnum::Creditor => __("finance::finance.creditor"),
        };

    }
}
