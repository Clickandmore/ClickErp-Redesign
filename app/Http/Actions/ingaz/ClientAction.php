<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\Client;

class ClientAction extends MainAction
{
    public function __construct(Client $model)
    {
        $this->model = $model;
        $this->files = ['image'];
        $this->fileFolder = 'images/clients/';
    }
       public function store($data)
    {
        if ($data['image']) {
            $image = $data['image'];
            $data['image'] = uploadFile($image, $this->fileFolder);
        }
        return parent::store($data);
    }

}
