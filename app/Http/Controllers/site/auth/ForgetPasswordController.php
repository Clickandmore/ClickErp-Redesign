<?php

namespace App\Http\Controllers\site\auth;

use App\Http\Actions\ingaz\PhoneVerificationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\site\auth\ForgetPasswordRequest;
use Illuminate\Support\Facades\Crypt;

class ForgetPasswordController extends Controller
{
    public function index()
    {
        return view('site.pages.auth.forget_password');
    }

    public function store(ForgetPasswordRequest $request, PhoneVerificationAction $phoneVerificationAction)
    {
        $data = $request->validated();
        unset($data['g-recaptcha-response']);
        $phoneVerificationAction->storeSendCode($data, $request);
        $encryptedPhone = Crypt::encryptString($data['phone']);
        return jsonSuccess(['redirect_url' => route('site.otp.index', ['phone' => $encryptedPhone])], __("ingaz.code_send_successfully"));
    }
}
