<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ArrivalWork;
use App\Models\ingaz\Question;
use App\Models\Testimonial;
use App\Models\WhyUs;

class ArrivalWorkerAction extends MainAction
{
    public function __construct(ArrivalWork $model)
    {
        $this->model = $model;
        $this->fileFolder = 'arrival-workers_images/';
    }

    public function storeArrivalWorkers($data)
    {
        if (isset($data['image'])) {
            $data['image'] = uploadFile($data['image'], $this->fileFolder);
        }
        return $this->store($data);
    }

    public function updateArrivalWorkers($data, $id)
    {
        $arrivalWorker = $this->find($id);
        $oldImage = null;
        $newImage = null;
        if (isset($data['image'])) {
            $oldImage = $arrivalWorker->image;
            $data['image'] = uploadFile($data['image'], $this->fileFolder);
            $newImage = $data['image'];
        }
        try {
            $updated = $arrivalWorker->update($data);
        } catch (\Throwable $exception) {
            deleteFile($newImage);
            throw $exception;
        }
        if ($newImage) {
            deleteFile($oldImage);
        }
        return $updated;
    }
}
