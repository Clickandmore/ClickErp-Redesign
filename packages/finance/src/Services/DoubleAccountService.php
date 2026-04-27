<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\DoubleAccount as Model;

class DoubleAccountService extends MainService
{
    protected DoubleAccountDetailService $doubleAccountDetailService;

    function __construct(Model $model, DoubleAccountDetailService $doubleAccountDetailService)
    {
        $this->model = $model;
        $this->doubleAccountDetailService = $doubleAccountDetailService;
    }

    public function storeDoubleAccount($data)
    {
        $obj = $this->store($data);
        $obj->accounts()->attach($data['account_id']);
        return $obj;
    }

    public function updateDoubleAccount($id, $data)
    {
        $obj = $this->update($id,$data);
        $row = $this->find($id);
        $row->accounts()->sync($data['account_id']);
        return $obj;
    }

    public function getAccountIds($id)
    {
        $modelInstance = $this->model->find($id);

        if (is_null($modelInstance)) {
            return [];
        }

        return $modelInstance->details()->pluck('account_id')->toArray();
//        return $this->model->find($id)->details()->pluck('account_id')->toArray();
    }

}
