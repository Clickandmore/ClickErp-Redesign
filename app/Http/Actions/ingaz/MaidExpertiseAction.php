<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ingaz\Maid;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ingaz\JobType;
use App\Models\ingaz\User as ClientUser;
use App\Enums\ingaz\ContractStatusEnum;
use App\Models\MaidExpertise;
use Illuminate\Support\Facades\DB;

class MaidExpertiseAction extends MainAction
{
    public function __construct(MaidExpertise $model)
    {
        $this->model = $model;
        $this->fileFolder = 'images/Maid/';
    }


}
