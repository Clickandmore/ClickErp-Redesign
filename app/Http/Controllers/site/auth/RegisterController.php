<?php

namespace App\Http\Controllers\site\auth;

use App\Http\Actions\ingaz\ServiceAction;
use App\Http\Actions\ingaz\SliderAction;
use App\Http\Actions\ingaz\StatisticAction;
use App\Http\Actions\ingaz\StepAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    public function index()
    {
        return view('site.pages.auth.register');
    }


}
