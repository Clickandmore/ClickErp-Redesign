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
use DateTime;
use Illuminate\Support\Facades\Storage;
use Nami\Hr\Enums\EmployeeAttendancesType;

class EmployeeExecelFileService extends MainService
{
    public function __construct(EmployeeAttendances $model)
    {
        $this->model = $model;
    }

    public function storeEmployeeAttendances($data, $request)
    {
        // $attendance = $this->store($data);
        $excel_rows_length = $request->excel_rows_length;
        $excelRows = collect(json_decode($request->excel_rows))->toArray();
        foreach ($excelRows as $key => $oneRow) {
            $employee = Employee::where('code', $oneRow->employee_code)->first();
            $employeeShift = EmployeeShift::where('employee_id', $employee->id)->first();
            $shift_id = $employeeShift->shift_id;
            $date = $this->convertExcelDate($oneRow->date);
            // Convert Excel decimal time to H:i:s format
            $attendance_time = $this->convertExcelTime($oneRow->attendance_time) ?? '00:00:00';
            $leave_time = $this->convertExcelTime($oneRow->leave_time) ?? '00:00:00';
            $attendance = $this->model->create([
                'employee_id' => $employee->id,
                'date' => $date,
                'attendance_time' => $attendance_time,
                'leave_time' => $leave_time,
                'shift_id' => $shift_id,
                'type' => 2,
                'month_year' => date('m-Y', strtotime($date)),
            ]);

            // $attendanceTime = date('H:i', strtotime($oneRow->attendance_time)); // عشان لما نظبط التاريخ والوقت في الشيت
            $attendanceTime = date('H:i', strtotime($attendance->attendance_time));
            $shift = Shift::find($employeeShift->shift_id);
            $shiftFromTime = date('H:i', strtotime($shift->from_hour));

            if ($attendanceTime > $shiftFromTime) {
                $employee = Employee::find($employee->id);
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
                $delay->value = number_format($value, 2);
                $delay->month_year = $attendance->month_year;
                $delay->employee_id = $attendance->employee_id;
                $delay->employee_attendances_id = $attendance->id;
                $delay->sanction_id = $shift->absence_sanctions_id;
                $delay->save();
            }
        }

        return $attendance;
    }

    // Helper function to convert Excel serial date to Y-m-d format
    private function convertExcelDate($excelDate)
    {
        // Excel dates start from 1899-12-30 in PHP (Excel counts from 1900-01-01, so we adjust by 1 day)
        $startDate = new DateTime('1899-12-30');
        $days = intval($excelDate);
        // Extract the integer part of the date (days since 1899-12-30)
        $startDate->modify("+{$days} days");

        return $startDate->format('Y-m-d'); // Return the date in Y-m-d format
    }

    // Helper function to convert Excel decimal time to H:i:s format
    private function convertExcelTime($decimalTime)
    {
        $seconds = $decimalTime * 86400; // 1 day = 86400 seconds
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        $seconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds); // Return in H:i:s format
    }

}
