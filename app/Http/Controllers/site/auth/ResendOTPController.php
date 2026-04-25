<?php

namespace App\Http\Controllers\site\auth;

use App\Http\Actions\ingaz\PhoneVerificationAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\site\auth\ResendOtpRequest;

class ResendOTPController extends Controller
{
    public function store(ResendOtpRequest $request, PhoneVerificationAction $phoneVerificationAction)
    {
        $data = $request->validated();
        $phoneVerificationAction->storeSendCode($data, $request);
        return jsonSuccess(null, null, __("web.resend_code_successfuly"));
    }
}
