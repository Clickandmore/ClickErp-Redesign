<?php

namespace Nami\Hr\Services;



use Nami\Hr\MainService;
use Nami\Hr\Models\EmployeeSalary;
use Nami\Hr\Models\Job;
use Illuminate\Support\Facades\Storage;

class SalariesService extends MainService
{
    public function __construct(EmployeeSalary $model,private DepartmentService $departmentService)
    {
        $this->model = $model;
    }

    public function getJob()
    {
        return $this->get();
    }

    public function getdepartment()
    {
        return $this->departmentService->getDepartment();
    }

    public function storeSalary($data, $request)
    {
        $flag = 0;
        foreach ($request->employee_id as $key => $employee_id){
            if (isset($request->checkbox[$key])) {
                $salary = new EmployeeSalary();
                $salary->employee_id = $request->input('employee_id')[$key];
                $salary->month_year = $request->input('month_year');
                $salary->attandance_days = $request->input('attandance_days')[$key];
                $salary->allowances = $request->input('allowances')[$key];
                $salary->sanctions = $request->input('sanctions')[$key];
                $salary->Insurances = $request->input('Insurances')[$key];
                $tax = $request->input('tax')[$key];
                $salary->delays = $request->input('delays')[$key];
                $salary->final = $request->input('final')[$key];
                $salary->save();
                $flag = 1;
            }
        }
        if($flag==1) {
            return [
                'code' => 200,
                'data' => true,
                'message' =>'تم صرف الرواتب بنجاح'
            ];
        }
        return [
            'code' => 402,
            'data' => null,
            'message' => 'يرجى تحديد الموظفين التي تريد صرف الرواتب لهم'
        ];
    }

    public function updateSalary($id, $data)
    {
        $this->find($id)->update( $data);
    }

    public function deleteSalary($id)
    {
        $obj = $this->find($id);
        $this->delete($id);
    }

}
