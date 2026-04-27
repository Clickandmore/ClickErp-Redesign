<?php

namespace Nami\Hr\Services;



use Nami\Hr\MainService;
use Nami\Hr\Models\OfficialVacation;
use Illuminate\Support\Facades\Storage;

class OfficialVacationService extends MainService
{
    public function __construct(OfficialVacation $model)
    {
        $this->model = $model;
    }

    public function getOfficialVacation()
    {
        return $this->get();
    }

    public function storeOfficialVacation($data,$request)
    {
        return $this->store( $data);
    }

    public function updateOfficialVacation($id, $data,$request)
    {
        $this->find($id)->update( $data);
    }

    public function deleteOfficialVacation($id)
    {
        $obj = $this->find($id);
        $this->delete($id);
    }

}
