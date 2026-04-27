<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\PrincipleHistory;

class PrincipleHistoryService extends MainService
{
    public function __construct(PrincipleHistory $model)
    {
        $this->model = $model;
    }

}
