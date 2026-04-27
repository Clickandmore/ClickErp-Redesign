<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\StockMovementSale as ObjModel;


class StockMovementSaleService extends MainService
{
    public function __construct(ObjModel $model)
    {
        $this->model = $model;
        $this->fileFolder = 'movement_sale/';
    }


}
