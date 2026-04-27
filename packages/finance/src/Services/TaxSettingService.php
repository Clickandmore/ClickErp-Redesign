<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\TaxSetting;
use Nami\Finance\Services\AccountService;

class TaxSettingService extends MainService
{
    public function __construct(TaxSetting $model,protected AccountService $accountService)
    {
        $this->model = $model;
    }

    public function getAccounts()
    {
        return $this->accountService->getAccountWithNoChild();
    }
}
