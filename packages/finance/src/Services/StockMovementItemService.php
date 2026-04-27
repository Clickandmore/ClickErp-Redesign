<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\StockMovementItem;

class StockMovementItemService extends MainService
{
    public function __construct(
        StockMovementItem $model,

    )
    {
        $this->model = $model;

    }

}
