<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ingaz\JobTitle;

class JobTitleAction extends MainAction
{
    public function __construct(JobTitle $model)
    {
        $this->model = $model;
        $this->fileFolder = 'images/JobTitle/';
    }

    public function store($data)
    {
        if ($data['image']) {
            $image = $data['image'];
            $data['image'] = uploadFile($image, $this->fileFolder, 1);
        }
        return parent::store($data);
    }


    public function updateJobTitle($id, $data)
    {
        $row = $this->find($id);
        $oldImage = null;
        $newImage = null;

        if (request()->hasFile('image')) {
            $oldImage = $row->image;
            $data['image'] = uploadFile($data['image'], $this->fileFolder, 1);
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

    public function getJobsTitles()
    {
        return $this->model->orderByTranslation('title', 'asc')->get();
    }
}
