<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ingaz\AboutUs;
use App\Models\SystemPlan;
use App\Models\SystemSubscription;

class SystemPlanAction extends MainAction
{
    public function __construct(SystemPlan $model)
    {
        $this->model = $model;
        $this->files = [''];
        $this->fileFolder = '';
    }



}
