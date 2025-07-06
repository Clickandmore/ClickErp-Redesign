<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ingaz\Skill;

class SkillAction extends MainAction
{
    public function __construct(Skill $model)
    {
        $this->model = $model;
    }

    public function getSkills()
    {
        return $this->model->latest()->get();
    }

    public function get()
    {
        return $this->model->latest()->get();
    }
}
