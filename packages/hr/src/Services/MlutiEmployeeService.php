<?php

namespace Nami\Hr\Services;

use Carbon\Carbon;
use Nami\Hr\MainService;
use Nami\Hr\Models\Job;
use Nami\Hr\Models\Shift;
use Nami\Hr\Models\Employee;
use Nami\Hr\Models\Sanction;
use Nami\Hr\Models\EmployeeDelay;
use Nami\Hr\Models\EmployeeShift;
use Nami\Hr\Models\EmployeeAttendances;
use Illuminate\Support\Facades\Storage;
use Nami\Hr\Enums\EmployeeAttendancesType;

class MlutiEmployeeService extends MainService
{
    public function __construct(EmployeeAttendances $model, private EmployeeService $employeeService)
    {
        $this->model = $model;
    }

    public function getEmployee()
    {
        return $this->employeeService->getEmployee();
    }

    public function storeEmployeeAttendances($data, $request)
    {
        $employees = $request->employee_id;
        foreach ($employees as $key => $employee) {
            $employeeShift = EmployeeShift::where('employee_id', $request->employee_id[$key])->first();
            if ($employeeShift) {
                $shift_id = $employeeShift->shift_id;

                $attendance = $this->model->updateOrCreate(
                    [
                        'employee_id' => $employee,
                        'date' => $request->date[$key],
                    ],
                    [
                        'attendance_time' => $request->attendance_time[$key],
                        'leave_time' => $request->leave_time[$key],
                        'shift_id' => $shift_id,
                        'created_by_id' => auth('admin')->user()->id,
                        'type' => 1,
                        'month_year' => date('m-Y', strtotime($request->date[$key])),
                    ]
                );

                $attendanceTime = date('H:i', strtotime($request->attendance_time[$key]));
                $shift = Shift::find($employeeShift->shift_id);
                $shiftFromTime = date('H:i', strtotime($shift->from_hour));

                if ($attendanceTime > $shiftFromTime) {
                    $employee = Employee::find($request->employee_id[$key]);
                    $salary = $employee->salary;

                    $sanction = Sanction::find($shift->absence_sanctions_id);
                    $value = 0;

                    if ($sanction->type == 1) {
                        $value = ($salary / $shift->month_days) * $sanction->value;
                    } elseif ($sanction->type == 2) {
                        $value = $sanction->value;
                    } elseif ($sanction->type == 3) {
                        $value = ($salary * $sanction->value) / 100;
                    }
                    EmployeeDelay::updateOrCreate(
                        [
                            'employee_id' => $attendance->employee_id,
                            'employee_attendances_id' => $attendance->id,
                            'month_year' => $attendance->month_year,
                            'sanction_id' => $shift->absence_sanctions_id,
                        ],
                        [
                            'value' => number_format($value, 2),
                        ]
                    );
                }
            }
            else{
                $emp = Employee::find($request->employee_id[$key]);
                return jsonValid(['errors' => __('hr.Employee')." ".$emp->name." ".__('hr.does not have a shift')]);
            }
        }
        return jsonSuccess();
    }

}
