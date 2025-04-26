<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Http\Resources\Api\UserResource;
use App\Http\Traits\SendSMSTrait;
use App\Models\ingaz\PhoneVerification;
use App\Models\ingaz\User;
use Illuminate\Support\Facades\Auth;

class PhoneVerificationAction extends MainAction
{
    use SendSMSTrait;
    public function __construct(PhoneVerification $model)
    {
        $this->model = $model;
        $this->fileFolder = 'images/PhoneVerification/';
    }

    public function storeSendCode($data, $request)
    {
        $phone_isset = $this->model->where('phone', $request->phone)
            ->where('phone_code', $request->phone_code)->first();

        $code = '12345';
        if (env('MESSAGE_Live')) {
            $code = sprintf("%05d", rand(0, 99999));
        }

        if ($phone_isset) {
            $phone_isset->update(["code" => $code]);
        } else {
            $data['code'] = $code;
            $storedData = $this->store(data: $data);
            $code = $storedData->code;
        }

        $msg = "رمز التحقق  : $code  لدخول منصة ussusalenjaz.sa";
        $result = $this->sendSMS($request->phone, $msg);

        return ["code" => $code];
    }

    public function confirmCode($request)
    {
        $code_isset = $this->model->where('phone', $request->phone)
            ->where('phone_code', $request->phone_code)
            ->where('code', $request->code)
            ->first();

        if (!$code_isset) {
            return false;
        }

        $user = User::where('phone', $request->phone)
            ->where('phone_code', $request->phone_code)
            ->first();

        if ($user) {
            $users = UserResource::make($user);
            return jsonSuccess($users);
        }

        return jsonSuccess();
    }

    public function updatePassword($request)
    {
        $user = User::find(Auth::user()->id);
        $user->update(['password' => bcrypt($request->new_password)]);
        return true;
    }
}
