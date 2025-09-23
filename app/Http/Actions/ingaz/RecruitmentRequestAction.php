<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ingaz\RecruitmentRequest;
use App\Models\ingaz\RecruitmentRequestMaid;

class RecruitmentRequestAction extends MainAction
{
    public function __construct(RecruitmentRequestMaid $model)
    {
        $this->model = $model;
        $this->files = ['image'];
        $this->fileFolder = 'images/RecruitmentRequest/';
    }
   
}
