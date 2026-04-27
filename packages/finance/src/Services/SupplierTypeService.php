<?php

namespace Nami\Finance\Services;


use Nami\Finance\MainService;

use Nami\Finance\Models\SupplierType;


class SupplierTypeService extends MainService
{
    public function __construct(SupplierType $model,protected AccountService $accountService)
    {
        $this->model = $model;
        $this->fileFolder = 'supplier_type/';
    }
}
