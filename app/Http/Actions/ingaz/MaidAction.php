<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ingaz\Maid;

class MaidAction extends MainAction
{
    public function __construct(Maid $model)
    {
        $this->model = $model;
        $this->fileFolder = 'images/Maid/';
    }

    public function storeMaid($data, $request)
    {
        if (isset($data['cv_file']) && $data['cv_file'] instanceof \Illuminate\Http\UploadedFile) {
            $image = $data['cv_file'];
            $data['cv_file'] = uploadFile($image, $this->fileFolder);
        }
        if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
            $image = $data['image'];
            $data['image'] = uploadFile($image, $this->fileFolder);
        }
        if (isset($data['cv_preview']) && $data['cv_preview'] instanceof \Illuminate\Http\UploadedFile) {
            $image = $data['cv_preview'];
            $data['cv_preview'] = uploadFile($image, $this->fileFolder);
        }
        $maid = parent::store($data);
        $maid->jobTitles()->attach($request->job_id);
        $maid->jobTypes()->attach($request->type_id);
        return $maid;
    }

    public function getMaids($request)
    {
        return $this->model
            ->when($request->filled('nationality_id'), function ($q) use ($request) {
                $q->where('nationality_id', $request->nationality_id);
            })
            ->when($request->filled('religion_id'), function ($q) use ($request) {
                $q->where('religion_id', $request->religion_id);
            })
            ->when($request->filled('job_type_id') && is_array($request->job_type_id), function ($q) use ($request) {
                $q->whereHas('jobTypes', function ($subQ) use ($request) {
                    $subQ->whereIn('job_type_id', $request->job_type_id);
                });
            })
            ->when($request->filled('job_title_id') && is_array($request->job_title_id), function ($q) use ($request) {
                $q->whereHas('jobTitles', function ($subQ) use ($request) {
                    $subQ->whereIn('job_title_id', $request->job_title_id);
                });
            })
            ->when($request->filled('age_from') && $request->filled('age_to'), function ($q) use ($request) {
                $q->whereBetween('age', [$request->age_from, $request->age_to]);
            });
    }

    /**
     * @param $request
     * @return mixed
     */
    public function getMaidsSearch($request)
    {
        $keyword = $request->search;

        $results = Maid::select('maids.*')
            ->leftJoin('nationalities_translations', 'maids.nationality_id', '=', 'nationalities_translations.nationality_id')
            ->leftJoin('religions_translations', 'maids.religion_id', '=', 'religions_translations.religion_id')
            ->where(function ($query) use ($keyword) {
                $query->where('nationalities_translations.title', 'LIKE', '%' . $keyword . '%')
                    ->orWhereHas('jobTypes', function ($subQ) use ($keyword) {
                        $subQ->whereTranslationLike('title', '%' . $keyword . '%');
                    })
                    ->orWhereHas('jobTitles', function ($subQ) use ($keyword) {
                        $subQ->whereTranslationLike('title', '%' . $keyword . '%');
                    })
                    ->orWhere('religions_translations.title', 'LIKE', '%' . $keyword . '%');
            })->distinct();
        return $results;
    }

    public function updateMaid($id, $data, $request)
    {
        $maid = parent::find($id);

        if (isset($data['cv_file'])) {
            deleteFile($maid->cv_file);
            $image = $data['cv_file'];
            $data['cv_file'] = uploadFile($image, $this->fileFolder);
        }
        if (isset($data['cv_preview'])) {
            deleteFile($maid->cv_preview);
            $image = $data['cv_preview'];
            $data['cv_preview'] = uploadFile($image, $this->fileFolder);
        }
        if (isset($data['image'])) {
            deleteFile($maid->image);
            $image = $data['image'];
            $data['image'] = uploadFile($image, $this->fileFolder);
        }
        $row = parent::find($id);
        $row->update($data);
//        parent::update($id, $data);
        if ($request->job_id) {
            $maid->jobTitles()->sync($request->job_id);
        }
        if ($request->type_id) {
            $maid->jobTypes()->sync($request->type_id);
        }
        return parent::find($id);
    }
}
