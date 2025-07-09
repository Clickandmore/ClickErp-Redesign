<?php

namespace App\Http\Actions\dashboard;


use App\Http\Actions\MainAction;
use App\Models\ingaz\Setting;
use Illuminate\Support\Facades\Storage;

class SettingAction extends MainAction
{
    public function __construct(Setting $model)
    {
        $this->model = $model;
        $this->fileFolder = "images/settings/";
    }

    public function getSetting() {
        $settings = $this->first();
        return $settings;

    }

    public function storeSetting($data,$request)
    {
        if ($request->hasFile('logo_header')) {
            $image = $data['logo_header'];
            $data['logo_header'] = uploadFile($image, $this->fileFolder,1);
        }
        if ($request->hasFile('about_image')) {
            $image = $data['about_image'];
            $data['about_image'] = uploadFile($image, $this->fileFolder,1);
        }
        if ($request->hasFile('logo_footer')) {
            $image = $data['logo_footer'];
            $data['logo_footer'] = uploadFile($image, $this->fileFolder,1);
        }
        if ($request->hasFile('favicon')) {
            $image = $data['favicon'];
            $data['favicon'] = uploadFile($image, $this->fileFolder,1);
        }
        return $this->store( $data);
    }

    public function updateSetting($id, $data)
    {
        $setting = $this->find($id);

        // Handle file uploads
        if (isset($data['logo_header']) && $data['logo_header'] instanceof \Illuminate\Http\UploadedFile) {
            deleteFile($setting->logo_header);
            $image = $data['logo_header'];
            $data['logo_header'] = uploadFile($image, $this->fileFolder,1);
        }
        if (isset($data['logo_footer']) && $data['logo_footer'] instanceof \Illuminate\Http\UploadedFile) {
            deleteFile($setting->logo_footer);
            $image = $data['logo_footer'];
            $data['logo_footer'] = uploadFile($image, $this->fileFolder,1);
        }
        if (isset($data['favicon']) && $data['favicon'] instanceof \Illuminate\Http\UploadedFile) {
            deleteFile($setting->favicon);
            $image = $data['favicon'];
            $data['favicon'] = uploadFile($image, $this->fileFolder,1);
        }
        if (isset($data['about_image']) && $data['about_image'] instanceof \Illuminate\Http\UploadedFile) {
            deleteFile($setting->about_image);
            $image = $data['about_image'];
            $data['about_image'] = uploadFile($image, $this->fileFolder,1);
        }

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
            $setting->update($mainData);
        }

        // Update translations
        foreach ($translationData as $locale => $fields) {
            $setting->translateOrNew($locale)->fill($fields);
        }

        $setting->save();

        return $setting;
    }

    public function deleteSetting($id)
    {
        $obj = $this->find($id);
        if ($obj->image != 'default.png') {
            Storage::delete('public/images/admins/settings/'.$obj->image);
        }
        $this->delete($id);
    }

}
