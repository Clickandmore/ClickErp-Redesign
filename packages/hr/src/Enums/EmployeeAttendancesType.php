<?php

namespace Nami\Hr\Enums;

enum EmployeeAttendancesType: int
{
    case AttendanceDeparture = 1;
    case Attendance = 2;
    case Absence = 3;
    case Weekend = 4;
    case Late = 5;
    case Vacation = 6;
    case OfficialVacation = 7;

    public function lang()
    {
        return match ($this) {
            EmployeeAttendancesType::AttendanceDeparture => __("hr::hr.Attendance and departure"),
            EmployeeAttendancesType::Attendance => __("hr::hr.Attendance"),
            EmployeeAttendancesType::Absence => __("hr::hr.absence"),
            EmployeeAttendancesType::Weekend => __("hr::hr.Weekend"),
            EmployeeAttendancesType::Late => __("hr::hr.Late"),
            EmployeeAttendancesType::Vacation => __("hr::hr.Vacation"),
            EmployeeAttendancesType::OfficialVacation => __("hr::hr.Official Vacation"),
        };
    }
}
