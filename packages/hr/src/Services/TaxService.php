<?php

namespace Nami\Hr\Services;

use Nami\Hr\Enums\AllowanceTypeis;
use Nami\Hr\MainService;
use Nami\Hr\Models\Tax;
use Illuminate\Support\Facades\Storage;

class TaxService extends MainService
{

    public function __construct(Tax $model)
    {
        $this->model = $model;
        $this->fileFolder = "tax/";
    }

    public function getTax()
    {
        return $this->get();
    }

    public function getActiveTax()
    {
        return $this->getWhere([['status',1]]);
    }

    public function storeTax($data,$request)
    {
        return $this->store( $data);
    }

    public function updateTax($id, $data)
    {
        $this->find($id)->update( $data);
    }

    public function deleteTax($id)
    {
        $obj = $this->find($id);
        $this->delete($id);
    }

}
