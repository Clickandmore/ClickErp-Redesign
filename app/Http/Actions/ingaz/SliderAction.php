<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ingaz\Slider;

class SliderAction extends MainAction
{
    public function __construct(Slider $model)
    {
        $this->model = $model;
        $this->fileFolder = 'images/Slider/';
    }

    public function store($data)
    {
        if ($data['image']) {
            $image = $data['image'];
            $data['image'] = uploadFile($image, $this->fileFolder);
        }
        return parent::store($data);
    }

    public function updateSlider($id, $data)
    {
        $row = $this->find($id);

        if (request()->hasFile('image')) {
            deleteFile($row->image);
            $data['image'] = uploadFile($data['image'], $this->fileFolder, 1);
        }

        return $row->update($data);
    }
}
