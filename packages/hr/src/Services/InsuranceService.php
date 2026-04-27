<?php

namespace Nami\Hr\Services;

use Nami\Hr\Enums\AllowanceTypeis;
use Nami\Hr\MainService;
use Nami\Hr\Models\Insurance;
use Illuminate\Support\Facades\Storage;

class InsuranceService extends MainService
{
    public function __construct(Insurance $model)
    {
        $this->model = $model;
    }

    public function getInsurance()
    {
        return $this->get();
    }

    public function getActiveInsurance()
    {
        return $this->getWhere([['status',1]]);
    }

    public function storeInsurance($data,$request)
    {
        return $this->store( $data);
    }

    public function updateInsurance($id, $data)
    {
        $this->find($id)->update( $data);
    }

    public function deleteInsurance($id)
    {
        $obj = $this->find($id);
        $this->delete($id);
    }

}
