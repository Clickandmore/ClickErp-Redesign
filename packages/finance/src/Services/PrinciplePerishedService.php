<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\PrinciplePerished;

class PrinciplePerishedService extends MainService
{
    public function __construct(PrinciplePerished $model)
    {
        $this->model = $model;
    }

}
