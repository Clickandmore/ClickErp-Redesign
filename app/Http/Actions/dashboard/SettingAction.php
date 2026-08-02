<?php

namespace App\Http\Actions\dashboard;


use App\Http\Actions\MainAction;
use App\Models\ingaz\Setting;
use Illuminate\Support\Facades\Schema;

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
        if ($request->hasFile('admin_login_background')) {
            $image = $data['admin_login_background'];
            $data['admin_login_background'] = uploadFile($image, $this->fileFolder, 1);
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

        if ($request->hasFile('logo_header')) {
            $image = $data['logo_header'];
            $data['logo_header'] = uploadFile($image, $this->fileFolder, 1);
        }
        if ($request->hasFile('document_logo')) {
            $image = $data['document_logo'];
            $data['document_logo'] = uploadFile($image, $this->fileFolder, 1);
        }
        if ($request->hasFile('admin_logo')) {
            $image = $data['admin_logo'];
            $data['admin_logo'] = uploadFile($image, $this->fileFolder, 1);
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
        unset($data['_token'], $data['_method']);
        $oldFiles = [];
        $newFiles = [];
        $fileFields = [
            'qr_image',
            'zatch_logo',
            'cover_image',
            'admin_login_background',
            'light_image',
            'experience_image',
            'second_qr_image',
            'logo_header',
            'document_logo',
            'admin_logo',
            'logo_footer',
            'favicon',
            'about_image',
        ];

        try {
            foreach ($fileFields as $field) {
                if (!isset($data[$field]) || !$data[$field] instanceof \Illuminate\Http\UploadedFile) {
                    continue;
                }

                $oldFiles[] = $setting->{$field};
                $data[$field] = uploadFile($data[$field], $this->fileFolder, 1);
                $newFiles[] = $data[$field];
            }

            // Optional keys that may not be present in every section update
            $optionalKeys = [
            'note_en',
            'note_ar',
            'years',
            'show_customer_service_during_booking',
            'link_rights_and_duties',
            ];
            foreach ($optionalKeys as $key) {
                if (array_key_exists($key, $data)) {
                    $setting->{$key} = $data[$key];
                }
            }

        if (array_key_exists('admin_quick_links_empty', $data) && !array_key_exists('admin_quick_links', $data)) {
            $data['admin_quick_links'] = [];
        }
        unset($data['admin_quick_links_empty']);

        if (array_key_exists('admin_quick_links', $data)) {
            $data['admin_quick_links'] = $this->normalizeAdminQuickLinks($data['admin_quick_links']);
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
        $translationColumns = Schema::hasTable('settings_translations')
            ? Schema::getColumnListing('settings_translations')
            : [];

        foreach ($translationData as $locale => $fields) {
            if ($translationColumns) {
                $fields = array_intersect_key($fields, array_flip($translationColumns));
            }

            $setting->translateOrNew($locale)->fill($fields);
        }

            $setting->save();
        } catch (\Throwable $exception) {
            $this->deleteManagedFiles($newFiles);
            throw $exception;
        }

        $this->deleteManagedFiles($oldFiles);

        return $setting;
    }

    private function deleteManagedFiles(array $paths): void
    {
        foreach (array_unique(array_filter($paths)) as $path) {
            deleteFile($path);
        }
    }

    private function normalizeAdminQuickLinks($links): array
    {
        if (!is_array($links)) {
            return [];
        }

        $normalized = [];
        foreach ($links as $link) {
            $title = trim((string)($link['title'] ?? ''));
            $url = trim((string)($link['url'] ?? ''));

            if ($title === '' || $url === '') {
                continue;
            }

            $normalized[] = [
                'title' => $title,
                'url' => $url,
                'icon' => trim((string)($link['icon'] ?? 'fa-external-link')) ?: 'fa-external-link',
                'is_visible' => !empty($link['is_visible']),
            ];
        }

        return array_values($normalized);
    }

    public function deleteSetting($id)
    {
        $obj = $this->find($id);
        $image = $obj->image != 'default.png' ? $obj->image : null;
        $this->delete($id);
        deleteFile($image);
    }

}
