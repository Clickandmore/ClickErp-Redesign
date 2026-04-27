<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\ItemTax;

class ItemTaxService extends MainService
{
    public function __construct(ItemTax $model)
    {
        $this->model = $model;
    }

}
