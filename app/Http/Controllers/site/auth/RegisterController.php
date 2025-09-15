<?php

namespace App\Http\Controllers\site\auth;

use App\Http\Actions\ingaz\ServiceAction;
use App\Http\Actions\ingaz\SliderAction;
use App\Http\Actions\ingaz\StatisticAction;
use App\Http\Actions\ingaz\StepAction;
use App\Http\Actions\ingaz\UserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\site\auth\RegisterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function index()
    {
        return view('site.pages.auth.register');
    }

    public function store(RegisterRequest $request, UserAction $userAction)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['phone_code'] = "+966";
        $userAction->store($data);
        return jsonSuccess(['redirect_url'=>route('site.login.index')], __("ingaz.register_successfully"));
    }


}
