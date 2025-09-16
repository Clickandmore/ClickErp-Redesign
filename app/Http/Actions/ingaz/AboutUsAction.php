<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ingaz\AboutUs;

class AboutUsAction extends MainAction
{
    public function __construct(AboutUs $model)
    {
        $this->model = $model;
        $this->files = [''];
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
