<?php

namespace Nami\Hr\Services;



use Nami\Hr\MainService;
use Nami\Hr\Models\Vacation;
use Illuminate\Support\Facades\Storage;

class VacationService extends MainService
{
    public function __construct(Vacation $model)
    {
        $this->model = $model;
        $this->fileFolder = "vacations/";
    }

    public function getVacation()
    {
        return $this->get();
    }

    public function storeVacation($data,$request)
    {
        $data['with_salary'] = ($request->has('with_salary') && $request->input('with_salary') == 1) ? 1 : 0;
        return $this->store( $data);
    }

    public function updateVacation($id, $data,$request)
    {
        $data['with_salary'] = ($request->has('with_salary') && $request->input('with_salary') == 1) ? 1 : 0;
        $this->find($id)->update( $data);
    }

    public function deleteVacation($id)
    {
        $obj = $this->find($id);
        $this->delete($id);
    }

}
