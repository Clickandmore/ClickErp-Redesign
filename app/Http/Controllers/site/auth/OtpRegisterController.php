<?php

namespace App\Http\Controllers\site\auth;

use App\Http\Actions\ingaz\PhoneVerificationAction;
use App\Http\Actions\ingaz\UserAction;
use App\Http\Actions\site\auth\LoginAction;
use App\Http\Controllers\admin\Controller;
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

    public function store(
        OtpRequest $request,
        PhoneVerificationAction $phoneVerificationAction,
        UserAction $userAction,
        LoginAction $loginAction
    ) {
        $data = $request->validated();

        $response = $phoneVerificationAction->confirmCode($request);
        if (!$response) {
            return jsonSuccess(null, __("ingaz.code_is_wrong"), 422);
        }

        $user = $phoneVerificationAction->getUser($request);
        auth('web')->login($user);

        // رجّع المستخدم للـ intended URL أو للصفحة الرئيسية لو مش موجودة
        $target = session()->pull('url.intended', route('site.home.index'));

        return jsonSuccess([
            'redirect_url' => $target
        ], __("ingaz.login_successfully"));
    }


}
