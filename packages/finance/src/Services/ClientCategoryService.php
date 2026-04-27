<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\ClientCategory;

class ClientCategoryService extends MainService
{
    public function __construct(ClientCategory $model)
    {
        $this->model = $model;
    }


}
