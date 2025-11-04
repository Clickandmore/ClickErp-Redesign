<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\Client;
use App\Models\Feature;
use App\Models\Requirement;
use App\Models\WhatDo;

class WhatDoAction extends MainAction
{
    public function __construct(WhatDo $model)
    {
        $this->model = $model;
        $this->files = [''];
        $this->fileFolder = '';
    }

}
