<?php

namespace Nami\Hr\Services;

use Nami\Hr\MainService;
use Nami\Hr\Models\Employee;
use Nami\Hr\Models\EmployeeVacation;
use Nami\Hr\Models\EmployeeAllowance;
use Illuminate\Support\Facades\Auth;
use Nami\Hr\Models\EmployeePermission;
use Nami\Hr\Models\EmployeeAttendances;
use Nami\Hr\Services\EmployeeService;
use Illuminate\Support\Facades\Storage;

use Nami\Hr\Services\EmployeeAttendancesService;
use Nami\Hr\Models\EmployeeSanction;

class EmployeeDashboardService extends MainService
{


    public function __construct(private EmployeeService $employeeService)
    {

    }
    public function getEmployee()
    {
        return $this->employeeService->getEmployee();
    }

    public function getCountEmployee()
    {
        return $this->employeeService->getCountEmployee();
    }

    public function dashboard() {
        $userType = Auth::guard('admin')->user()->admin_type;
        if ($userType == 'employee') {
            $employeeId = Auth::guard('admin')->user()->adminEmployees->employee_id;
            $employee = Employee::where('id', $employeeId)->first();
        }
        $allowanceSum = EmployeeAllowance::where('employee_id',$employee->id)->sum('value');
        $permissions = EmployeePermission::where('employee_id',$employee->id)->get();
        $vacations = EmployeeVacation::where('employee_id',$employee->id)->get();
        $sanctions = EmployeeSanction::where('employee_id',$employee->id)->get();
        $taxs_sum = $employee->tax()->sum('value');
        $salary = $employee->salary;
        if ($taxs_sum != 0) {
            $taxs = $salary * ($taxs_sum / 100);
        } else {
            $taxs = 0;
        }

        $employees = $this->getEmployee();
        $series = [];
        $attendance_data = [];
        for ($day = 1; $day <= date('t'); $day++) {
            $currentDate = date('Y-m-d', strtotime(date('Y-m') . '-' . $day));
            $attendances = EmployeeAttendances::where('type', 2)
                ->where('employee_id', $employee->id)
                ->where('date', $currentDate)
                ->get();
            $absences = EmployeeAttendances::where('type', 3)
                ->where('employee_id', $employee->id)
                ->where('date', $currentDate)
                ->get();

            if ($attendances->count() > 0) {
                $attendance_data[] = 1;
            } elseif ($absences->count() > 0) {
                $attendance_data[] = 0;
            } else {
                $attendance_data[] = 2;
            }
        }

        $series[] = [
            'name' => $employee->name,
            'data' => $attendance_data
        ];
        return ['employee'=>$employee,'allowanceSum' => $allowanceSum,'taxs' => $taxs,'series' => $series
        ,'permissions' => $permissions,'vacations' => $vacations,'sanctions' => $sanctions];
    }


}
