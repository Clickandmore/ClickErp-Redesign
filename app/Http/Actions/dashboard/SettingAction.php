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

    public function getSetting()
    {
        $settings = $this->first();
        return $settings;

    }

    public function storeSetting($data, $request)
    {
        if ($request->hasFile('qr_image')) {
            $image = $data['qr_image'];
            $data['qr_image'] = uploadFile($image, $this->fileFolder, 1);
        }
        if ($request->hasFile('zatch_logo')) {
            $image = $data['zatch_logo'];
            $data['zatch_logo'] = uploadFile($image, $this->fileFolder, 1);
        }
        if ($request->hasFile('cover_image')) {
            $image = $data['cover_image'];
            $data['cover_image'] = uploadFile($image, $this->fileFolder, 1);
        }
        if ($request->hasFile('light_image')) {
            $image = $data['light_image'];
            $data['light_image'] = uploadFile($image, $this->fileFolder, 1);
        }

        if ($request->hasFile('experience_image')) {
            $image = $data['experience_image'];
            $data['experience_image'] = uploadFile($image, $this->fileFolder, 1);
        }

        if ($request->hasFile('second_qr_image')) {
            $image = $data['second_qr_image'];
            $data['second_qr_image'] = uploadFile($image, $this->fileFolder, 1);
        }


        if ($request->hasFile('')) {
            $image = $data['logo_header'];
            $data['logo_header'] = uploadFile($image, $this->fileFolder, 1);
        }
        if ($request->hasFile('about_image')) {
            $image = $data['about_image'];
            $data['about_image'] = uploadFile($image, $this->fileFolder, 1);
        }
        if ($request->hasFile('logo_footer')) {
            $image = $data['logo_footer'];
            $data['logo_footer'] = uploadFile($image, $this->fileFolder, 1);
        }
        if ($request->hasFile('favicon')) {
            $image = $data['favicon'];
            $data['favicon'] = uploadFile($image, $this->fileFolder, 1);
        }
        return $this->store($data);
    }

    public function updateSetting($id, $data)
    {
        $setting = $this->find($id);

        if (isset($data['qr_image']) && $data['qr_image'] instanceof \Illuminate\Http\UploadedFile) {
            deleteFile($setting->qr_image);
            $image = $data['qr_image'];
            $data['qr_image'] = uploadFile($image, $this->fileFolder, 1);
        }
        if (isset($data['zatch_logo']) && $data['zatch_logo'] instanceof \Illuminate\Http\UploadedFile) {
            deleteFile($setting->zatch_logo);
            $image = $data['zatch_logo'];
            $data['zatch_logo'] = uploadFile($image, $this->fileFolder, 1);
        }
        if (isset($data['cover_image']) && $data['cover_image'] instanceof \Illuminate\Http\UploadedFile) {
            deleteFile($setting->cover_image);
            $image = $data['cover_image'];
            $data['cover_image'] = uploadFile($image, $this->fileFolder, 1);
        }
        if (isset($data['light_image']) && $data['light_image'] instanceof \Illuminate\Http\UploadedFile) {
            deleteFile($setting->light_image);
            $image = $data['light_image'];
            $data['light_image'] = uploadFile($image, $this->fileFolder, 1);
        }

        if (isset($data['experience_image']) && $data['experience_image'] instanceof \Illuminate\Http\UploadedFile) {
            deleteFile($setting->experience_image);
            $image = $data['experience_image'];
            $data['experience_image'] = uploadFile($image, $this->fileFolder, 1);
        }

        if (isset($data['second_qr_image']) && $data['second_qr_image'] instanceof \Illuminate\Http\UploadedFile) {
            deleteFile($setting->second_qr_image);
            $image = $data['second_qr_image'];
            $data['second_qr_image'] = uploadFile($image, $this->fileFolder, 1);
        }


        // Handle file uploads
        if (isset($data['logo_header']) && $data['logo_header'] instanceof \Illuminate\Http\UploadedFile) {
            deleteFile($setting->logo_header);
            $image = $data['logo_header'];
            $data['logo_header'] = uploadFile($image, $this->fileFolder, 1);
        }
        if (isset($data['logo_footer']) && $data['logo_footer'] instanceof \Illuminate\Http\UploadedFile) {
            deleteFile($setting->logo_footer);
            $image = $data['logo_footer'];
            $data['logo_footer'] = uploadFile($image, $this->fileFolder, 1);
        }
        if (isset($data['favicon']) && $data['favicon'] instanceof \Illuminate\Http\UploadedFile) {
            deleteFile($setting->favicon);
            $image = $data['favicon'];
            $data['favicon'] = uploadFile($image, $this->fileFolder, 1);
        }
        if (isset($data['about_image']) && $data['about_image'] instanceof \Illuminate\Http\UploadedFile) {
            deleteFile($setting->about_image);
            $image = $data['about_image'];
            $data['about_image'] = uploadFile($image, $this->fileFolder, 1);
        }
//new keys
        $setting['note_en'] = $data['note_en'];
        $setting['note_ar'] = $data['note_ar'];
        $setting['years'] = $data['years'];
        $setting['show_customer_service_during_booking'] = $data['show_customer_service_during_booking'];
        $setting['link_rights_and_duties'] = $data['link_rights_and_duties'];

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
            Storage::delete('public/images/admins/settings/' . $obj->image);
        }
        $this->delete($id);
    }

}
