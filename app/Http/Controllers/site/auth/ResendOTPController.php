<?php

namespace App\Http\Controllers\site\auth;

use App\Http\Actions\ingaz\PhoneVerificationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\site\auth\ForgetPasswordRequest;
use App\Http\Requests\site\auth\ResendOtpRequest;
use Illuminate\Support\Facades\Crypt;

class ResendOTPController extends Controller
{
    public function store(ResendOtpRequest $request, PhoneVerificationAction $phoneVerificationAction)
    {
        $data = $request->validated();
        $phoneVerificationAction->storeSendCode($data, $request);
        $encryptedPhone = Crypt::encryptString($data['phone']);
        dd('dsd');
        return jsonSuccess(['redirect_url' => route('site.otp.index', ['phone' => $encryptedPhone])], __("ingaz.code_send_successfully"));
    }
}
