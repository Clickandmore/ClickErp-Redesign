<?php

namespace Nami\Hr\Services;

use Nami\Hr\MainService;
use Nami\Hr\Models\EmployeeCustodyLocation;

class EmployeeCustodyLocationService extends MainService
{
    public function __construct()
    {
        $this->model = new EmployeeCustodyLocation();
        $this->files = [];
    }

    public function getDataTable()
    {
        return parent::getDataTable()->withCount('custodies');
    }
}
