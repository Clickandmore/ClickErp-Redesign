<?php

namespace Nami\Finance\Enums;

enum BondPaymentTypes: int
{
    case Cash = 1;
    case Check = 2;
    case BankVisa = 3;
    case BankTransfer = 4;
    case ElectronicWallet = 5;
    case Network = 6;
    case Online = 7;

    public function lang()
    {
        return match ($this) {
            BondPaymentTypes::Cash => __("finance::finance.Cash"),
            BondPaymentTypes::Check => __("finance::finance.Check"),
            BondPaymentTypes::BankVisa => __("finance::finance.BankVisa"),
            BondPaymentTypes::BankTransfer => __("finance::finance.BankTransfer"),
            BondPaymentTypes::ElectronicWallet => __("finance::finance.ElectronicWallet"),
            BondPaymentTypes::Network => __("finance::finance.Network"),
            BondPaymentTypes::Online => __("finance::finance.Online"),
        };
    }

    public static function langFromValue(int $value): string
    {
        return match ($value) {
            BondPaymentTypes::Cash->value => __("finance::finance.Cash"),
            BondPaymentTypes::Check->value => __("finance::finance.Check"),
            BondPaymentTypes::BankVisa->value => __("finance::finance.BankVisa"),
            BondPaymentTypes::BankTransfer->value => __("finance::finance.BankTransfer"),
            BondPaymentTypes::ElectronicWallet->value => __("finance::finance.ElectronicWallet"),
            BondPaymentTypes::Network->value => __("finance::finance.Network"),
            BondPaymentTypes::Online->value => __("finance::finance.Online"),
            default => __("finance::finance.unknown"),
        };
    }
}
