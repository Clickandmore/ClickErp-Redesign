<?php

namespace App\Http\Controllers\site\auth;

use App\Http\Actions\ingaz\PhoneVerificationAction;
use App\Http\Actions\ingaz\ServiceAction;
use App\Http\Actions\ingaz\SliderAction;
use App\Http\Actions\ingaz\StatisticAction;
use App\Http\Actions\ingaz\StepAction;
use App\Http\Actions\ingaz\UserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\site\auth\RegisterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class RegisterController extends Controller
{
    public function index()
    {
        return view('site.pages.auth.register');
    }

    public function store(RegisterRequest $request, UserAction $userAction, PhoneVerificationAction $phoneVerificationAction)
    {
        $data = $request->validated();
        RateLimiter::hit('register:' . $data['phone'], 60);
        $data['password'] = Hash::make($data['password']);
        unset($data['g-recaptcha-response']);
        $userAction->store($data);
        $dataVerificationCode['phone'] = $data['phone'];
        $dataVerificationCode['phone_code'] = $data['phone_code'];
        $phoneVerificationAction->storeSendCode($dataVerificationCode, $request);
        $encryptedPhone = Crypt::encryptString($request->phone);
        return jsonSuccess(['redirect_url' => route('site.otp-register.index', ['phone' => $encryptedPhone])], __("ingaz.register_successfully"));
    }


}
