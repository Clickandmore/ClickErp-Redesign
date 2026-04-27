<?php

namespace Nami\Hr\Services;



use Nami\Hr\MainService;
use Nami\Hr\Models\Job;
use Nami\Hr\Models\ShiftDay;
use Nami\Hr\Models\ShiftRole;
use Illuminate\Support\Facades\Storage;

class ShiftRoleService extends MainService
{
    public function __construct(ShiftRole $model)
    {
        $this->model = $model;
    }

    public function getShiftRole()
    {
        return new ShiftRole();
    }
}
