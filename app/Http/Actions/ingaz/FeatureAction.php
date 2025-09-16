<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\Client;
use App\Models\Feature;
use App\Models\Requirement;

class FeatureAction extends MainAction
{
    public function __construct(Feature $model)
    {
        $this->model = $model;
        $this->files = [''];
        $this->fileFolder = 'images/feature/';
    }
   
}
