<?php

namespace Nami\Hr\Services;



use Nami\Hr\MainService;
use Nami\Hr\Models\Job;
use Nami\Hr\Models\ShiftDay;
use Illuminate\Support\Facades\Storage;

class ShiftDayService extends MainService
{

    public function __construct(ShiftDay $model)
    {
        $this->model = $model;
    }


    public function getShiftDay()
    {
        return new ShiftDay();
    }
}
