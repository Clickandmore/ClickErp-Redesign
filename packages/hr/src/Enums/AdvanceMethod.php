<?php

namespace Nami\Hr\Enums;

enum AdvanceMethod : string
{
    case MonthlyInstallment = "monthly_installment";
    case DebtPayments = "debt_payments";

    public function lang()
    {
        return match ($this) {
            AdvanceMethod::MonthlyInstallment => __('hr::hr.monthly_installment'),
            AdvanceMethod::DebtPayments => __('hr::hr.debt_payments'),
        };
    }
}
