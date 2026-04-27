<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\PurchaseOrderItem;

class PurchaseOrderItemService extends MainService
{
    public function __construct(PurchaseOrderItem $model)
    {
        $this->model = $model;
    }

}
