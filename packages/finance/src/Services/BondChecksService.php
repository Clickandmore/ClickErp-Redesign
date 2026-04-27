<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\BondChecks;

class BondChecksService extends MainService
{
    public function __construct(BondChecks $model)
    {
        $this->model = $model;
        $this->fileFolder = 'bond_checks/';
    }

    public function updateOrCreate($bond_id, $data)
    {
        return $this->model->updateOrCreate(["bond_id" => $bond_id], $data);
    }


    public function deleteByBondId($bond_id){
        return $this->model->where("bond_id",$bond_id)->delete();
    }


}
