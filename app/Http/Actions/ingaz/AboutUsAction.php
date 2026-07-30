<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ingaz\AboutUs;

class AboutUsAction extends MainAction
{
    public function __construct(AboutUs $model)
    {
        $this->model = $model;
        $this->files = ['image', 'image_vision', 'image_message', 'image_value'];
        $this->fileFolder = 'images/AboutU/';
    }


    public function updateSetting($id, $data)
    {
        $about = $this->find($id);
        $oldFiles = [];
        $newFiles = [];

        // Separate translation data from main data
        $translationData = [];
        $mainData = [];

        foreach ($data as $key => $value) {
            if (strpos($key, ':') !== false) {
                // This is a translation field
                list($field, $locale) = explode(':', $key, 2);
                if (!isset($translationData[$locale])) {
                    $translationData[$locale] = [];
                }
                $translationData[$locale][$field] = $value;
            } else {
                // This is a main field
                $mainData[$key] = $value;
            }
        }
        foreach ($this->files as $field) {
            if (!isset($data[$field])) {
                continue;
            }

            $oldFiles[] = $about->{$field};
            $mainData[$field] = uploadFile($data[$field], 'images/abouts/', 1);
            $newFiles[] = $mainData[$field];
        }

        try {
            if (!empty($mainData)) {
                $about->update($mainData);
            }

            foreach ($translationData as $locale => $fields) {
                $about->translateOrNew($locale)->fill($fields);
            }

            $about->save();
        } catch (\Throwable $exception) {
            $this->deleteFiles($newFiles);
            throw $exception;
        }

        $this->deleteFiles($oldFiles);
        return $about;
    }

    private function deleteFiles(array $paths): void
    {
        foreach (array_unique(array_filter($paths)) as $path) {
            deleteFile($path);
        }
    }

}
