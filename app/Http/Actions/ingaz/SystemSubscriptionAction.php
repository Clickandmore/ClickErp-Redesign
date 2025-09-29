<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ingaz\AboutUs;
use App\Models\SystemSubscription;

class SystemSubscriptionAction extends MainAction
{
    public function __construct(SystemSubscription $model)
    {
        $this->model = $model;
        $this->files = [''];
        $this->fileFolder = '';
    }



}
