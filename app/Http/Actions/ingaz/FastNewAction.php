<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ingaz\FastNew;

class FastNewAction extends MainAction
{
    public function __construct(FastNew $model)
    {
        $this->model = $model;
        $this->files = [''];
        $this->fileFolder = 'images/fastNew/';
    }
  
}
