<?php

namespace Nami\Finance\Services;

use Nami\Finance\Enums\StockStatusEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\Stock as Model;

class StockService extends MainService
{

    function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function updateStock($id, $data)
    {
        $stock = $this->model->findOrFail($id);
        return $stock->update($data);
    }

    public function for_first_stock()
    {
        return $this->model->where('status',StockStatusEnum::Active->value)->get();
    }

    public function getActive()
    {
        return $this->model->where('status',StockStatusEnum::Active->value)->get();
    }

    public function lastCode()
    {
        $row = $this->model->orderBy('code', 'DESC')->first();
        return (isset($row->id)) ? ($row->code + 1) : 1;
    }

}
