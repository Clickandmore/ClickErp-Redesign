<?php

namespace App\Http\Controllers\site\auth;

use App\Http\Actions\ingaz\PhoneVerificationAction;
use App\Http\Actions\ingaz\UserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\site\auth\OtpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class OtpRegisterController extends Controller
{
    public function index(Request $request)
    {
        $phone = Crypt::decryptString($request->phone);
        $type = 'register';
        return view('site.pages.auth.otp', compact('phone','type'));
    }

    public function store(OtpRequest $request, PhoneVerificationAction $phoneVerificationAction, UserAction $userAction)
    {
        $data = $request->validated();
        $response = $phoneVerificationAction->confirmCode($request);
        if (!$response) {
            return jsonSuccess(null, __("ingaz.code_is_wrong"), 422);
        }
        return jsonSuccess(['redirect_url' => route('site.login.index')], __("ingaz.code_confirm_successfully"));

    }

}
