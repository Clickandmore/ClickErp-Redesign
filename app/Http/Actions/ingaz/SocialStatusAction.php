<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ingaz\SocialStatus;

class SocialStatusAction extends MainAction
{
    public function __construct(SocialStatus $model)
    {
        $this->model = $model;
    }

    public function getSocialStatuses()
    {
        return $this->model->latest()->get();
    }

    public function get()
    {
        return $this->model->latest()->get();
    }
}
