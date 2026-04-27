<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\Bank;
use Nami\Finance\Models\PurchaseReturn;

class PurchaseReturnService extends MainService
{
    public function __construct(PurchaseReturn $model)
    {
        $this->model = $model;
    }
}
