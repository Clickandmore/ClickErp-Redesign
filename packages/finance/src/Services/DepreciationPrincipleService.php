<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\DepreciationPrinciple;

class DepreciationPrincipleService extends MainService
{
    public function __construct(DepreciationPrinciple $model)
    {
        $this->model = $model;
    }


}
