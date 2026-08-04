<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\Client;
use App\Models\Feature;
use App\Models\Requirement;

class FeatureAction extends MainAction
{
    public function __construct(Feature $model)
    {
        $this->model = $model;
        $this->files = ['image_file'];
        $this->fileFolder = 'images/feature/';
    }

    public function store($data)
    {
        $newImage = null;

        if (!empty($data['image_file'])) {
            $data['image_file'] = uploadFile($data['image_file'], $this->fileFolder);
            $newImage = $data['image_file'];
        }

        try {
            return parent::store($data);
        } catch (\Throwable $exception) {
            deleteFile($newImage);
            throw $exception;
        }
    }

    public function update($id, $data)
    {
        $feature = $this->find($id);
        $oldImage = null;
        $newImage = null;

        if (!empty($data['image_file'])) {
            $oldImage = $feature?->image_file;
            $data['image_file'] = uploadFile($data['image_file'], $this->fileFolder);
            $newImage = $data['image_file'];
        }

        try {
            $updated = $feature->update($data);
        } catch (\Throwable $exception) {
            deleteFile($newImage);
            throw $exception;
        }

        if ($oldImage) {
            deleteFile($oldImage);
        }

        return $updated;
    }

    public function deleteWithFile($id, $column)
    {
        $feature = $this->find($id);
        $path = $feature?->{$column};

        $this->delete($id);

        if ($path) {
            deleteFile($path);
        }
    }
}
