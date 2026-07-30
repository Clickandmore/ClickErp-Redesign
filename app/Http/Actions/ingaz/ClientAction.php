<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\Client;

class ClientAction extends MainAction
{
    public function __construct(Client $model)
    {
        $this->model = $model;
        $this->files = ['image'];
        $this->fileFolder = 'images/clients/';
    }

    public function store($data)
    {
        $newImage = null;

        if (!empty($data['image'])) {
            $image = $data['image'];
            $data['image'] = uploadFile($image, $this->fileFolder);
            $newImage = $data['image'];
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
        $client = $this->find($id);
        $oldImage = null;
        $newImage = null;

        if (!empty($data['image'])) {
            $oldImage = $client?->image;
            $image = $data['image'];
            $data['image'] = uploadFile($image, $this->fileFolder);
            $newImage = $data['image'];
        }

        if (array_key_exists('title', $data)) {
            $data['title'] = filled($data['title']) ? trim($data['title']) : null;
        }

        try {
            $updated = $this->model->where('id', $id)->update($data);
        } catch (\Throwable $exception) {
            deleteFile($newImage);
            throw $exception;
        }

        if ($newImage) {
            deleteFile($oldImage);
        }

        return $updated;
    }

    public function deleteWithFile($id, $column)
    {
        $client = $this->find($id);
        $path = $client?->{$column};

        $this->delete($id);
        deleteFile($path);
    }

}
