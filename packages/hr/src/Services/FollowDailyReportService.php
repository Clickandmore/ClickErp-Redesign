<?php

namespace Nami\Hr\Services;

use Nami\Hr\MainService;
use Nami\Hr\Models\DailyWorkReport;
use Nami\Hr\Models\Employee;
use Nami\Hr\Models\EmployeeResponsible;

class FollowDailyReportService extends MainService
{

    public function __construct()
    {
        $this->model = new DailyWorkReport();
        $this->fileFolder = "images/daily_work/";
        $this->files = ['file'];
    }

    public function createData($mainRoute)
    {
        $responsible_employees = EmployeeResponsible::where('responsible_employee_id', @auth('admin')->user()->employee->id)->pluck('employee_id')->toArray();
         return [
             'employee_id' => @auth('admin')->user()->employee->id,
             'employees' => Employee::whereIn('id', $responsible_employees)->get(),
             'storeRoute' =>  route($mainRoute.'.store')
         ];
    }

    public function editData($id,$mainRoute)
    {
        $responsible_employees = EmployeeResponsible::where('responsible_employee_id', @auth('admin')->user()->employee->id)->pluck('employee_id')->toArray();

        $data['employee_id'] = @auth('admin')->user()->employee->id;
        $data['obj'] = $this->find($id);
        $data['employees'] = Employee::whereIn('id', $responsible_employees)->get();
        $data['updateRoute'] = route($mainRoute.'.update',$id);

        return $data;
    }

    public function showData($id)
    {
        $responsible_employees = EmployeeResponsible::where('responsible_employee_id', @auth('admin')->user()->employee->id)->pluck('employee_id')->toArray();
        $data['employee_id'] = @auth('admin')->user()->employee->id;
        $data['obj'] = $this->find($id);
        $data['employees'] = Employee::whereIn('id', $responsible_employees)->get();
        return $data;
    }


    public function getDataTable()
    {
        $responsible_employees = EmployeeResponsible::where('responsible_employee_id', @auth('admin')->user()->employee->id)->pluck('employee_id')->toArray();
        $query = $this->model->query()->latest()->whereIn('employee_id', $responsible_employees)
            ->when(!request()->input('from_date') || !request()->input('to_date'), function ($q) {

                $q->whereDate('date', date('Y-m-d'));
            })
            ->when(request()->input('from_date') && request()->input('to_date'), function ($q) {
                $q->whereBetween('date', [request()->from_date, request()->to_date]);
            })
            ->when(request()->input('employee_id'), function ($q) {
                $q->where('employee_id', request()->employee_id);
            });


        return $query;
    }


}
