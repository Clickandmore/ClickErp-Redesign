<?php

namespace Nami\Hr\Services;

use Nami\Hr\MainService;
use Nami\Hr\Models\DailyWorkReport;
use Nami\Hr\Models\Employee;
use Nami\Hr\Models\EmployeeResponsible;

class DailyWorkReportService extends MainService
{

    public function __construct()
    {
        $this->model = new DailyWorkReport();
        $this->fileFolder = "images/daily_work/";
        $this->files = ['file'];
    }

    public function createData($mainRoute)
    {
         return [
             'employee_id' => @auth('admin')->user()->employee->id,
             'employees' => Employee::get(),
             'storeRoute' =>  route($mainRoute.'.store')
         ];
    }

    public function editData($id,$mainRoute)
    {
        $data['employee_id'] = @auth('admin')->user()->employee->id;
        $data['obj'] = $this->find($id);
        $data['employees'] = Employee::get();
        $data['updateRoute'] = route($mainRoute.'.update',$id);

        return $data;
    }

    public function showData($id)
    {
        $data['employee_id'] = @auth('admin')->user()->employee->id;
        $data['obj'] = $this->find($id);
        $data['employees'] = Employee::get();

        return $data;
    }

    public function getDataTable($user_id = null)
    {
//        if($user_id == null){
//            $user_id =  @auth('admin')->user()->employee->id;
//        }
//        return $this->model->query()->latest()->where('employee_id', $user_id);
        return parent::getDataTable();
    }

    public function getMyDataTable($user_id = null)
    {
        if($user_id == null){
            $user_id =  @auth('admin')->user()->employee->id;
        }

        $responsible_employees = EmployeeResponsible::where('responsible_employee_id', $user_id)->pluck('employee_id')->toArray();
        return $this->model->query()->latest()->whereIn('employee_id', $responsible_employees);
    }


}
