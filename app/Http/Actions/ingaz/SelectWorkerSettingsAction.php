<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\Client;
use App\Models\Feature;
use App\Models\Requirement;
use App\Models\SelectSetting;
use App\Models\SelectWorker;

class SelectWorkerSettingsAction extends MainAction
{
    public function __construct(SelectSetting $model)
    {
        $this->model = $model;
        $this->files = ['image'];
        $this->fileFolder = 'select_workers_settings/';
    }

    public function storeSelectSettings($data)
    {
        if (isset($data['image'])) {
            $data['image'] = uploadFile($data['image'], $this->fileFolder);
        }
        return $this->store($data);
    }

    public function updateSelectSettings($id, $data)
    {
        if (isset($data['image'])) {
            $data['image'] = uploadFile($data['image'], $this->fileFolder);
        }
        return $this->find($id)->update($data);
    }

}
