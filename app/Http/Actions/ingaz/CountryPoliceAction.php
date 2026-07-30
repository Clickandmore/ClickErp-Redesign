<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\CountryPolice;

class CountryPoliceAction extends MainAction
{
    public function __construct(CountryPolice $model)
    {
        $this->model = $model;
        $this->files = ['flag', 'file_1', 'file_2'];
        $this->fileFolder = 'images/country_polices/';
    }

    public function storeCountryPolices($data)
    {
        if (isset($data['flag'])) {
            $data['flag'] = uploadFile($data['flag'], $this->fileFolder, 1);
        }
        if (isset($data['file_1'])) {
            $data['file_1'] = uploadFile($data['file_1'], $this->fileFolder, 1);
        }
        if (isset($data['file_2'])) {
            $data['file_2'] = uploadFile($data['file_2'], $this->fileFolder, 1);
        }
        return $this->store($data);
    }

    public function updateCountryPolices($id, $data)
    {
        $country = $this->find($id);
        $oldFiles = [];
        $newFiles = [];

        foreach ($this->files as $field) {
            if (!isset($data[$field])) {
                continue;
            }

            $oldFiles[] = $country->{$field};
            $data[$field] = uploadFile($data[$field], $this->fileFolder, 1);
            $newFiles[] = $data[$field];
        }

        try {
            $updated = $country->update($data);
        } catch (\Throwable $exception) {
            $this->deleteFiles($newFiles);
            throw $exception;
        }

        $this->deleteFiles($oldFiles);
        return $updated;
    }

    private function deleteFiles(array $paths): void
    {
        foreach (array_unique(array_filter($paths)) as $path) {
            deleteFile($path);
        }
    }

}
