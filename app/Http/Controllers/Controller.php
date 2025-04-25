<?php

namespace App\Http\Controllers;

use App\Models\ingaz\Maid;
use App\Models\ingaz\User;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function usersList(){
        return User::where('is_blocked', 0)->pluck('name', 'id')->all();
    }

    public function maidsList(){
        return Maid::where('available', 1)
            // ->whereHas('job_types', function ($query) {
            //     $query->where('job_type_id', 2);
            // })
            ->pluck('passport_num', 'id')
            ->all();
    }
}
