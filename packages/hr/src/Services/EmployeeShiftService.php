<?php

namespace Nami\Hr\Services;



use Nami\Hr\MainService;
use Nami\Hr\Models\EmployeeShift;
use Nami\Hr\Models\Job;
use Nami\Hr\Models\ShiftDay;
use Illuminate\Support\Facades\Storage;

class EmployeeShiftService extends MainService
{
    public function __construct(EmployeeShift $model)
    {
        $this->model = $model;
    }

    public function getEmployeeShift($request)
    {
        return EmployeeShift::where('employee_id', $request)->first();
    }
}
