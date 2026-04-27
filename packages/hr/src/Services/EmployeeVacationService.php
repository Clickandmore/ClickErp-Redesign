<?php

namespace Nami\Hr\Services;

use Nami\Hr\Enums\EmployeeVacationStatus;
use Nami\Hr\MainService;
use Nami\Hr\Models\EmployeeVacation;
use Illuminate\Support\Facades\Storage;

class EmployeeVacationService extends MainService
{
    public function __construct(
        EmployeeVacation $model,
        private EmployeeService $employeeService,
        private VacationService $vacationService,
        private VacationYearService $vacationYearService
    )
    {
        $this->model = $model;
        $this->fileFolder = "images/admins/employee_vacation/";
        $this->files = ["vacation_file","return_file"];
    }

    public function getDataTable($user_id = null)
    {
        if($user_id == null){
            $user_id = @auth('admin')->user()->employee->id;
        }
        return parent::getDataTable()->where('employee_id', $user_id);
    }

    public function getEmployee()
    {
        return $this->employeeService->getEmployee();
    }

    public function employeeHasVacationOrder($dataInsert)
    {
        $check = $this->getWhereFirst([['employee_id',$dataInsert['employee_id']],['status',0]]);
        return $check;
    }

    public function getVacation()
    {
        return $this->vacationService->getVacation();
    }

    public function getEmployeeVacationStatus()
    {
        return EmployeeVacationStatus::cases();
    }

    public function storeEmployeeVacation($data,$request)
    {
        $data['vacation_year_id'] = $this->vacationYearService->getYearByDate($data['from_date'])->id;
        return $this->store($data);
    }

    public function updateEmployeeVacation($id, $data)
    {
        if (isset($data['vacation_file'])){
            $data['vacation_file'] = uploadFile($data['vacation_file'], $this->fileFolder);
        }
       return $this->find($id)->update($data);
    }

    public function deleteEmployeeVacation($id)
    {
        $obj = $this->find($id);
        $this->delete($id);
    }

}
