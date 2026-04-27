<?php

namespace Nami\Finance\Enums;

enum PerishedTypeEnum: string
{
    case FixedInstallment = "fixed_installment";
    case ContradictoryInstallment = "contradictory_installment";
    case Without = "without";

    public function lang()
    {
        return match ($this) {
            PerishedTypeEnum::FixedInstallment => __("finance::finance.fixed_installment"),
            PerishedTypeEnum::ContradictoryInstallment => __("finance::finance.contradictory_installment"),
            PerishedTypeEnum::Without => __("finance::finance.without"),
        };
    }
}
