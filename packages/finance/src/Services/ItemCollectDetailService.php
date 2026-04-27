<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\ItemCollectDetail;

class ItemCollectDetailService extends MainService
{
    public function __construct(ItemCollectDetail $model)
    {
        $this->model = $model;
    }

}
