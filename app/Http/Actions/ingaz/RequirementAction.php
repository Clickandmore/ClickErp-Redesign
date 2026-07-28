<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\Requirement;

class RequirementAction extends MainAction
{
    public function __construct(Requirement $model)
    {
        $this->model = $model;
        $this->files = ['image'];
        $this->fileFolder = 'images/requirments/';
    }

    public function get()
    {
        return $this->model
            ->with('items')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function getVisible()
    {
        return $this->model
            ->visible()
            ->whereHas('items')
            ->with('items')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
