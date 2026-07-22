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
        $this->files = ['image'];
        $this->fileFolder = 'images/WhatDo/';
    }

    public function store($data)
    {
        if (isset($data['image'])) {
            $data['image'] = uploadFile($data['image'], $this->fileFolder);
        }

        return parent::store($data);
    }

    public function updateWhatDo($id, $data)
    {
        $row = $this->find($id);

        if (isset($data['image'])) {
            $oldImage = $row->image;
            $data['image'] = uploadFile($data['image'], $this->fileFolder);
        }

        $row->update($data);

        if (isset($oldImage) && $oldImage) {
            deleteFile($oldImage);
        }

        return $row->fresh();
    }

    public function deleteWithFile($id, $column)
    {
        $row = $this->find($id);

        if ($row && $row->{$column}) {
            deleteFile($row->{$column});
        }

        return $this->delete($id);
    }
}
