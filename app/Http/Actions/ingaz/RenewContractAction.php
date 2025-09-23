<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\Client;
use App\Models\RenewContract;

class RenewContractAction extends MainAction
{
    public function __construct(RenewContract $model)
    {
        $this->model = $model;
        $this->files = ['image'];
        $this->fileFolder = 'images/renewcontract/';
    }
    public function deletewithfiles($id)
    {
        $modal = $this->find($id);
        $image = $modal->image;
        deleteFile($image);
        $this->delete($id);
    }
}
