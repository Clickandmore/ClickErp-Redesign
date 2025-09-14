<?php

namespace App\Http\Actions\office;

use App\Http\Actions\MainAction;
use App\Models\ingaz\JobTitle;
use App\Models\office\Office;
use Illuminate\Support\Facades\Storage;
use function App\Http\Actions\ingaz\request;

class OfficeAction extends MainAction
{
    public function __construct(Office $model)
    {
        $this->model = $model;
        $this->fileFolder = 'images/offices/';
    }
}
