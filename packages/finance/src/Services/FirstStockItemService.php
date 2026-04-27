<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\FirstStockItem;

class FirstStockItemService extends MainService
{
    public function __construct(FirstStockItem $model)
    {
        $this->model = $model;
    }

}
