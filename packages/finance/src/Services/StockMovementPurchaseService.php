<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\StockMovementPurchase as ObjModel;


class StockMovementPurchaseService extends MainService
{
    public function __construct(ObjModel $model)
    {
        $this->model = $model;
        $this->fileFolder = 'movement_purchase/';
    }


}
