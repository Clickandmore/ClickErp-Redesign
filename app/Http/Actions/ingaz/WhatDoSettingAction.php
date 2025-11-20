<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\Client;
use App\Models\Feature;
use App\Models\Requirement;
use App\Models\WhatDo;
use App\Models\WhatDoSetting;

class WhatDoSettingAction extends MainAction
{
    public function __construct(WhatDoSetting $model)
    {
        $this->model = $model;
        $this->files = [''];
        $this->fileFolder = '';
    }

}
