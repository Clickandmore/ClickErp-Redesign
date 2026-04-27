<?php

namespace Nami\Hr\Services;

use Nami\Hr\MainService;
use Nami\Hr\Models\HrEmployeeTask;

class EmployeeTaskService extends MainService
{

    function __construct()
    {
        $this->model = new HrEmployeeTask();
        $this->fileFolder = "images/admins/employee_tasks/";
    }

}
