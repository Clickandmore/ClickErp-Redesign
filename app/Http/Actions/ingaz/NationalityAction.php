<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use Illuminate\Support\Facades\DB;

class NationalityAction extends MainAction
{
    public function __construct(Nationality $model)
    {
        $this->model = $model;
        $this->fileFolder = 'images/Nationality/';
    }

    public function store($data)
    {
        if (!empty($data['logo'])) {
            $image = $data['logo'];
            $data['logo'] = uploadFile($image, $this->fileFolder);
        }

        $data['is_show'] = isset($data['is_show']) ? 1 : 0;

        return DB::transaction(function () use ($data) {
            $translations = $this->extractTranslations($data);
            $baseData = $this->clearRequesrt($this->withoutTranslations($data));

            $nationality = $this->model->create($baseData);

            foreach ($translations as $locale => $translationData) {
                $translationData = $this->clearRequesrt($translationData);

                if (empty($translationData)) {
                    continue;
                }

                $nationality->translations()->updateOrCreate(
                    ['locale' => $locale],
                    $translationData
                );
            }

            return $nationality->load('translations');
        });
    }

    public function updateNationality($id, $data)
    {
        $row = $this->find($id);
        $oldLogo = null;
        $newLogo = null;

        if (request()->hasFile('logo')) {
            $oldLogo = $row->logo;
            $data['logo'] = uploadFile($data['logo'], $this->fileFolder, 1);
            $newLogo = $data['logo'];
        }
        $data['is_show'] = isset($data['is_show']) ? 1 : 0;

        if (array_key_exists('price', $data) && $data['price'] !== null){
            Maid::where('nationality_id', $id)->update(['price' => $data['price']]);
        }

        $data['expected_duration'] = (int) $data['expected_duration'];
        // dd($row, $data);
        try {
            $updated = $row->update($data);
        } catch (\Throwable $exception) {
            deleteFile($newLogo);
            throw $exception;
        }

        if ($newLogo) {
            deleteFile($oldLogo);
        }

        return $updated;
    }

    public function getNationalities()
    { 
        return $this->model->orderByTranslation('sub_title', 'asc')->get();
    }

    public function getVisible()
    {
        return $this->model->where('is_show', true)
            ->orderByTranslation('sub_title', 'asc')
            ->get();
    }

    private function extractTranslations(array $data): array
    {
        $translations = [];

        foreach ($data as $key => $value) {
            if (!str_contains($key, ':')) {
                continue;
            }

            [$attribute, $locale] = explode(':', $key, 2);

            if (!in_array($attribute, $this->model->translatedAttributes, true)) {
                continue;
            }

            $translations[$locale][$attribute] = $value;
        }

        return $translations;
    }

    private function withoutTranslations(array $data): array
    {
        return array_filter(
            $data,
            fn ($key) => !str_contains($key, ':'),
            ARRAY_FILTER_USE_KEY
        );
    }
}
