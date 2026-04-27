<?php

namespace Nami\Hr\Services;

use Nami\Hr\MainService;
use Nami\Hr\Models\EmployeeSanction;
use Illuminate\Support\Facades\Storage;
use Nami\Hr\Enums\EmployeeVacationStatus;

class EmployeeSanctionService extends MainService
{
    public function __construct(EmployeeSanction $model,private EmployeeService $employeeService
    ,private SanctionService $sanctionService)
    {
        $this->fileFolder = "images/admins/employee_sanctions/";
        $this->files =['file'];
        $this->model = $model;
    }

    public function getEmployee()
    {
        return $this->employeeService->getEmployeeWithShift();
    }

    public function getSanction()
    {
        return $this->sanctionService->getSanction();
    }

    public function getEmployeeSanctionStatus()
    {
        return EmployeeVacationStatus::cases();
    }

    public function storeEmployeeSanction($data,$request)
    {
        $data['with_salary'] = ($request->has('with_salary') && $request->input('with_salary') == 1) ? 1 : 0;
        return $this->store($data);
    }

    public function updateEmployeeSanction($id, $data,$request)
    {
        $data['with_salary'] = ($request->has('with_salary') && $request->input('with_salary') == 1) ? 1 : 0;
        $this->find($id)->update($data);
    }

    public function deleteEmployeeSanction($id)
    {
        $obj = $this->find($id);
        $this->delete($id);
    }

}
