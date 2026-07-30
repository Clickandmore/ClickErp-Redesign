<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ingaz\JobType;

class JobTypeAction extends MainAction
{
    public function __construct(JobType $model)
    {
        $this->model = $model;
        $this->fileFolder = 'images/JobType/';
    }

    public function store($data)
    {
        if ($data['image']) {
            $image = $data['image'];
            $data['image'] = uploadFile($image, $this->fileFolder);
        }
        return parent::store($data);
    }

    public function update($id, $data)
    {
        $obj = $this->find($id);
        $oldImage = null;
        $newImage = null;
        if (isset($data['image'])) {
            $oldImage = $obj->image;
            $image = $data['image'];
            $data['image'] = uploadFile($image, $this->fileFolder);
            $newImage = $data['image'];
        }
        try {
            $updated = parent::update($id, $data);
        } catch (\Throwable $exception) {
            deleteFile($newImage);
            throw $exception;
        }
        if ($newImage) {
            deleteFile($oldImage);
        }
        return $updated;
    }

    public function getJobsTypes()
    {
        return $this->model->orderByTranslation('title', 'asc')->get();
    }
}
