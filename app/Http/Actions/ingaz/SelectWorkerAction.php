<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\Client;
use App\Models\Feature;
use App\Models\Requirement;
use App\Models\SelectWorker;

class SelectWorkerAction extends MainAction
{
    public function __construct(SelectWorker $model)
    {
        $this->model = $model;
        $this->files = [''];
    }

}
