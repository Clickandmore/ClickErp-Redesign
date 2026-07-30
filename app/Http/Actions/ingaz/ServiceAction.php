<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ingaz\Service;

class ServiceAction extends MainAction
{
    public function __construct(Service $model)
    {
        $this->model = $model;
        $this->fileFolder = 'images/Service/';
    }

    public function store($data)
    {
        if ($data['image']) {
            $image = $data['image'];
            $data['image'] = uploadFile($image, $this->fileFolder);
        }
        return parent::store($data);
    }

    public function updateService($id, $data)
    {
        $row = $this->find($id);
        $oldImage = null;
        $newImage = null;
        if (isset($data['image'])) {
            $oldImage = $row->image;
            $image = $data['image'];
            $data['image'] = uploadFile($image, $this->fileFolder);
            $newImage = $data['image'];
        }
        try {
            $updated = $row->update($data);
        } catch (\Throwable $exception) {
            deleteFile($newImage);
            throw $exception;
        }
        if ($newImage) {
            deleteFile($oldImage);
        }
        return $updated;
    }
}
