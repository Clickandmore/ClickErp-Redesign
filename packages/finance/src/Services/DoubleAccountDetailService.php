<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\DoubleAccountDetail as Model;

class DoubleAccountDetailService extends MainService
{

    function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function createDoubleAccountDetail($data, $id): bool
    {
        foreach ($data['account_id'] as $value) {
            $data['account_id'] = $value;
            $data['double_account_id'] = $id;
            $this->store($data);
        }

        return true;
    }

}
