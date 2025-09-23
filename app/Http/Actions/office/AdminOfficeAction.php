<?php

namespace App\Http\Actions\office;

use App\Http\Actions\MainAction;
use App\Models\ingaz\JobTitle;
use App\Models\office\AdminOffice;
use App\Models\office\JopTypeOffice;
use App\Models\office\Office;
use Illuminate\Support\Facades\Storage;
use function App\Http\Actions\ingaz\request;

class AdminOfficeAction extends MainAction
{
    public function __construct(AdminOffice $model)
    {
        $this->model = $model;
    }
}
