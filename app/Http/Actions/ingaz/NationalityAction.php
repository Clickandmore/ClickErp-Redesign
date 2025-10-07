<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use Illuminate\Support\Facades\Storage;

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
        return parent::store($data);
    }

    public function updateNationality($id, $data)
    {
        $row = $this->find($id);

        if (request()->hasFile('logo')) {
            Storage::delete($row->logo);
            $data['logo'] = uploadFile($data['logo'], $this->fileFolder, 1);
        }
        $data['is_show'] = isset($data['is_show']) ? 1 : 0;

        if (array_key_exists('price', $data) && $data['price'] !== null){
            Maid::where('nationality_id', $id)->update(['price' => $data['price']]);
        }

        return $row->update($data);
    }

    public function getNationalities(){
        return $this->model->latest()->get();
    }

    public function getVisible()
    {
        return $this->model->where('is_show', true)->latest()->get();
    }
}
