<?php

namespace App\Http\Actions\site;

use App\Http\Actions\MainAction;
use App\Models\ingaz\User;
use App\Models\ProfessionalApplication;
use Illuminate\Support\Facades\Auth;

class DailyLabotDemandAction extends MainAction
{
    public function __construct(ProfessionalApplication $model)
    {
        $this->model = $model;
    }

}
