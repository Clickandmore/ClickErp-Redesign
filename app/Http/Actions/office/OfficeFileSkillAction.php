<?php

namespace App\Http\Actions\office;

use App\Http\Actions\MainAction;
use App\Models\ingaz\JobTitle;
use App\Models\office\Office;
use App\Models\office\OfficeFile;
use App\Models\office\OfficeFileSkill;
use Illuminate\Support\Facades\Storage;
use function App\Http\Actions\ingaz\request;

class OfficeFileSkillAction extends MainAction
{
    public function __construct(OfficeFileSkill $model)
    {
        $this->model = $model;
        $this->fileFolder = 'images/offices/';
    }
}
