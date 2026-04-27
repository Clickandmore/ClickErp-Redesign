<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\BondChecks as Model;

class RefusedAcceptedCheckService extends MainService
{

    function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function getRefusedAcceptedCheckDataTable($bondType,$status)
    {
        return $this->model->query()
            ->whereHas('bond', function ($q) use ($bondType) {
                $q->where('type', $bondType)->where('is_confirm',1);
            })
            ->where('status',$status)
            ->latest();
    }
}
