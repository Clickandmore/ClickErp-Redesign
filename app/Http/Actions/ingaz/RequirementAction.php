<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\Client;
use App\Models\Requirement;

class RequirementAction extends MainAction
{
    public function __construct(Requirement $model)
    {
        $this->model = $model;
        $this->files = ['image'];
        $this->fileFolder = 'images/requirments/';
    }
        public function store($data)
    {
        if ($data['image']) {
            $image = $data['image'];
            $data['image'] = uploadFile($image, $this->fileFolder);
        }
        return parent::store($data);
    }
}
