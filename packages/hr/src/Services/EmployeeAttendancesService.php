<?php

namespace Nami\Hr\Services;

use Nami\Hr\MainService;
use Nami\Hr\Models\Shift;
use Nami\Hr\Models\Employee;
use Nami\Hr\Models\Sanction;
use Nami\Hr\Models\EmployeeDelay;
use Nami\Hr\Models\EmployeeShift;
use Nami\Hr\Models\EmployeeAttendances;
use Nami\Hr\Enums\EmployeeAttendancesType;

class EmployeeAttendancesService extends MainService
{
    public function __construct(EmployeeAttendances $model,private EmployeeService $employeeService)
    {
        $this->model = $model;
    }

    public function getEmployeeAttendances()
    {
        return $this->get();
    }

    public function getAttendancesDataTable()
    {
        return parent::getDataTable()->whereMonth('date', now()->month);
    }

    public function getAttendanceEmployee()
    {
        $today = date('Y-m-d');
        return $this->model->where('type', 2)->where('date', '=', $today)->get();
    }
    public function getAbsenceEmployee()
    {
        $today = date('Y-m-d');
        return $this->model->where('type',3)->where('date', '=', $today)->get();
    }

    public function getVacationEmployee()
    {
        $today = date('Y-m-d');
        return $this->model->where('type',6)->where('date', '=', $today)->get();
    }

    public function getEmployee()
    {
        return $this->employeeService->getEmployee();
    }

    public function getEmployeeAttendancesTypiesFull()
    {
        return EmployeeAttendancesType::cases();
    }

    public function getEmployeeAttendancesTypies()
    {
        $attendances = EmployeeAttendancesType::cases();
        return array_slice($attendances, 0, 3);
    }

    public function storeEmployeeAttendances($data, $request)
    {
        $employeeShift = EmployeeShift::where('employee_id', $request->employee_id)->first();

        // لازم يكون في شيفت للموظف علشان اقدر اعمله حضور
        if ($employeeShift){
            $data['shift_id'] = $employeeShift->shift_id;
            $data['created_by_id'] = auth('admin')->user()->id;
            $attendance = $this->store($data);

            $attendanceTime = date('H:i', strtotime($request->attendance_time));
            $shift = Shift::find($employeeShift->shift_id);
            $shiftFromTime = date('H:i', strtotime($shift->from_hour));

            if ($attendanceTime > $shiftFromTime) {
                $employee = Employee::find($request->employee_id);
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
                $delay = new EmployeeDelay();
                $delay->month_year = $attendance->month_year;
                $delay->value = number_format($value, 2);
                $delay->employee_id = $attendance->employee_id;
                $delay->employee_attendances_id = $attendance->id;
                $delay->sanction_id = $shift->absence_sanctions_id;
                $delay->save();
            }
            return jsonSuccess($attendance);
        }
        return jsonValid(['errors' => __('hr.this employee does not have a shift')]);
    }

    public function updateEmployeeAttendances($id, $data,$request)
    {
        $employeeShift = EmployeeShift::where('employee_id', $request->employee_id)->first();
        if ($employeeShift){
            $data['shift_id'] = $employeeShift->shift_id;
            $attendance = $this->find($id);
            $attendance->update($data);

            $attendanceTime = date('H:i:s', strtotime($request->attendance_time));
            $shift = Shift::find($employeeShift->shift_id);
            $shiftFromTime = date('H:i:s', strtotime($shift->from_hour));

            if ($attendanceTime > $shiftFromTime) {
                $employee = Employee::find($request->employee_id);
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
                $delay = EmployeeDelay::where('employee_attendances_id', $attendance->id)->first();
                if (!$delay) {
                    $delay = new EmployeeDelay();
                }
                $delay->month_year = $attendance->month_year;
                $delay->value = number_format($value, 2);
                $delay->employee_id = $attendance->employee_id;
                $delay->employee_attendances_id = $attendance->id;
                $delay->sanction_id = $shift->absence_sanctions_id;
                $delay->save();
            }
            return jsonSuccess();
        }
        return jsonValid(['errors' => __('hr.this employee does not have a shift')]);

    }

    public function deleteEmployeeAttendances($id)
    {
        $obj = $this->find($id);
        $this->delete($id);
    }

}
