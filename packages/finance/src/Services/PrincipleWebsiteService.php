<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\PrincipleWebsite;

class PrincipleWebsiteService extends MainService
{
    public function __construct(PrincipleWebsite $model)
    {
        $this->model = $model;
    }

    public function getWebsiteDataTable()
    {
        return $this->model->query()->where('id', '!=', 1)->latest();
    }
}
