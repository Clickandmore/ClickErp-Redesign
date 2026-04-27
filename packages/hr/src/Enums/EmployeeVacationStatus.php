<?php

namespace Nami\Hr\Enums;

enum EmployeeVacationStatus: int
{
    case NEW = 0;
    case ACCEPTED = 1;
    case REFUSED = 2;

    public function lang()
    {
        return match ($this) {
            EmployeeVacationStatus::NEW => __("hr::hr.new"),
            EmployeeVacationStatus::ACCEPTED => __("hr::hr.accepted"),
            EmployeeVacationStatus::REFUSED => __("hr::hr.refused"),
        };
    }
}
