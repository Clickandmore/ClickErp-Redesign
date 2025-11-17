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
        if (isset($data['image'])) {
            $mainData['image'] = uploadFile($data['image'], 'images/abouts/', 1);
        }
        if (isset($data['image_vision'])) {
            $mainData['image_vision'] = uploadFile($data['image_vision'], 'images/abouts/', 1);
        }
        if (isset($data['image_message'])) {
            $mainData['image_message'] = uploadFile($data['image_message'], 'images/abouts/', 1);
        }
        if (isset($data['image_value'])) {
            $mainData['image_value'] = uploadFile($data['image_value'], 'images/abouts/', 1);
        }
        // Update main data
        if (!empty($mainData)) {
            $about->update($mainData);
        }

        // Update translations
        foreach ($translationData as $locale => $fields) {
            $about->translateOrNew($locale)->fill($fields);
        }

        $about->save();

        return $about;
    }

}
