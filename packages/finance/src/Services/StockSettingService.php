<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\StockSetting as Model;

class StockSettingService extends MainService
{

    function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function getSetting()
    {
        return $this->model->firstOrCreate([]);
    }




}
