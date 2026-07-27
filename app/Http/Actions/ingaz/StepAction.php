<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ingaz\Step;
use Illuminate\Support\Facades\Storage;

class StepAction extends MainAction
{
    public function __construct(Step $model)
    {
        $this->model = $model;
        $this->fileFolder = 'images/Step/';
    }

    public function store($data)
    {

        return parent::store($data);
    }

    public function get()
    {
        return $this->model
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function updateStep($id, $data)
    {
        $row = $this->find($id);
        return $row->update($data);
    }
}
