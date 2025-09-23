<?php

namespace App\Http\Actions\office;

use App\Http\Actions\MainAction;
use App\Models\ingaz\JobTitle;
use App\Models\office\JopTypeOffice;
use App\Models\office\Office;
use Illuminate\Support\Facades\Storage;
use function App\Http\Actions\ingaz\request;

class JopTypeOfficeAction extends MainAction
{
    public function __construct(JopTypeOffice $model)
    {
        $this->model = $model;
    }
}
