<?php

namespace Nami\Hr\Services;

use Nami\Hr\Enums\AllowanceTypeis;
use Nami\Hr\MainService;
use Nami\Hr\Models\Allowance;

class AllowanceService extends MainService
{
    public function __construct(Allowance $model)
    {
        $this->model = $model;
        $this->fileFolder = 'allowances/';
    }

    public function getAllowance()
    {
        return $this->get();
    }

    public function getAllowanceTypies()
    {
        return AllowanceTypeis::cases();
    }

    public function storeAllowance($data,$request)
    {
        return $this->store( $data);
    }

    public function updateAllowance($id, $data)
    {
        $this->find($id)->update( $data);
    }

    public function deleteAllowance($id)
    {
        $obj = $this->find($id);
        $this->delete($id);
    }

}
