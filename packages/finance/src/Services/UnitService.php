<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\Unit;

class UnitService extends MainService
{
    public function __construct(Unit $model)
    {
        $this->model = $model;
        $this->fileFolder = 'units/';
    }

    public function getUnitList()
    {
        return $this->model->where('parent_id', null)->get();
    }

    public function updateUnit($id, $data)
    {
        $unit = $this->model->findOrFail($id);
        return $unit->update($data);
    }
}
