<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ingaz\Question;
use App\Models\WhyUs;

class WhyUsAction extends MainAction
{
    public function __construct(WhyUs $model)
    {
        $this->model = $model;
    }
}
