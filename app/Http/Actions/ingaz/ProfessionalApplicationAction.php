<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ProfessionalApplication;
use App\Models\RenewContract;

class ProfessionalApplicationAction extends MainAction
{
    public function __construct(ProfessionalApplication $model)
    {
        $this->model = $model;
        $this->files = ['image'];
        $this->fileFolder = 'images/ProfessionalApplication/';
    }
   
}
