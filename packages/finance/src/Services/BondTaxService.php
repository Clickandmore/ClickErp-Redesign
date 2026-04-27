<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\BondTax;
use Nami\Finance\Services\AccountService;

class BondTaxService extends MainService
{
    public function __construct(BondTax $model)
    {
        $this->model = $model;
    }

    public function storeBondTaxs($data) {
        return $this->store($data);
    }
}
