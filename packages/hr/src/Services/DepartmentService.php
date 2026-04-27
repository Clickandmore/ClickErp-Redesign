<?php

namespace Nami\Hr\Services;

use Nami\Hr\MainService;
use Nami\Hr\Models\Employee;
use Nami\Hr\Models\Department;

class DepartmentService extends MainService
{
    public function __construct(Department $model)
    {
        $this->model = $model;
        $this->fileFolder = 'depatments/';
    }

    public function getDepartment()
    {
        return $this->get();
    }

    public function storeDepartment($data,$request)
    {
        return $this->store( $data);
    }

    public function updateDepartment($id, $data)
    {
        $this->find($id)->update( $data);
    }

    public function deleteDepartment($id)
    {
        $obj = $this->find($id);
        $checkDelete = Employee::where('deparment_id', $obj->id)->count();
        if ($checkDelete > 0) {
            return false;
        }
        $this->delete($id);
        return true;
    }

}
