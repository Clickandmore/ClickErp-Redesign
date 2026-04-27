<?php

namespace Nami\Hr\Services;


use Nami\Hr\MainService;
use Nami\Hr\Models\Complaint;
use Nami\Hr\Models\Employee;

class ComplaintService extends MainService
{


    public function __construct()
    {
        $this->model = new Complaint();
        $this->fileFolder = "images/admins/complaint/";
    }

    public function getMyDataTable()
    {
        return parent::getDataTable()->where('employee_id', @auth('admin')->user()->employee->id);
    }

    public function getDirectedMyDataTable()
    {
        return parent::getDataTable()->where('directed_to_id', @auth('admin')->user()->employee->id);
    }

    public function createData($mainRoute)
    {
        return [
            'employee_id' => auth('admin')->user()->employee->id,
            'employees' => Employee::get(),
            'storeRoute' => route($mainRoute . '.store')
        ];
    }

    public function editData($id, $mainRoute)
    {
        $data['employee_id'] = @auth('admin')->user()->employee->id;
        $data['obj'] = $this->find($id);
        $data['employees'] = Employee::get();
        $data['updateRoute'] = route($mainRoute . '.update', $id);

        return $data;
    }

    public function showData($id)
    {
        $data['employee_id'] = @auth('admin')->user()->employee->id;
        $data['obj'] = $this->find($id);
        $data['employees'] = Employee::get();

        return $data;
    }

}
