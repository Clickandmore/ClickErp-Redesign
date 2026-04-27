<?php

namespace Nami\Finance\Services;

use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\BondPaymentTypes;
use Nami\Finance\MainService;

class BondPaymentService extends MainService
{

    public $folderPath = "";//"admin.finance.bond.sarf.payment_types";

    function __construct(protected AccountService $accountService)
    {
    }

    public function getTypeMap($type){
        $typeMap = [
            BondPaymentTypes::ElectronicWallet->value => [
                "view" => "electronic_wallet",
                "accounts" => $this->accountService->getByType(AccountTypesEnum::ElectronicWallet->value),
            ],
            BondPaymentTypes::Network->value => [
                "view" => "network",
                "accounts" => $this->accountService->getByType(AccountTypesEnum::Bank->value),
            ],
            BondPaymentTypes::BankVisa->value => [
                "view" => "visa_bank",
                "accounts" => $this->accountService->getByType(AccountTypesEnum::Bank->value),
            ],
            BondPaymentTypes::BankTransfer->value => [
                "view" => "bank",
                "accounts" => $this->accountService->getByType(AccountTypesEnum::Bank->value),
            ],
            BondPaymentTypes::Check->value => [
                "view" => "check",
                "accounts" => $this->accountService->getByType(AccountTypesEnum::Bank->value),
            ],
            BondPaymentTypes::Cash->value => [
                "view" => "cash",
                "accounts" => $this->accountService->getByType(AccountTypesEnum::Box->value),
            ],
        ];
        $view = (isset($typeMap[$type])) ? $typeMap[$type]["view"] :$typeMap[BondPaymentTypes::Cash->value]["view"] ;
        $accounts = (isset($typeMap[$type])) ? $typeMap[$type]["accounts"] :$typeMap[BondPaymentTypes::Cash->value]["accounts"];
        return  ["view"=>$this->folderPath . '.'.$view,"accounts"=>$accounts];
    }


    public function getPaymentTypeView($type)
    {
        $typeMap = $this->getTypeMap($type);
        $view = $typeMap["view"];
        $accounts =   $typeMap["accounts"];
        return view($view, compact('accounts'))->render() ;

    }



}
