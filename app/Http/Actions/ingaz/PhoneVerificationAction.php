<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Http\Resources\Api\UserResource;
use App\Http\Traits\SendSMSTrait;
use App\Models\ingaz\PhoneVerification;
use App\Models\ingaz\User;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

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
            $storedData = $this->store(data: $data);  // Store the data
            $code = $storedData->code;  // Ensure $code is a string, not an object
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
        if (isset($code_isset)) {
            $user = User::where('phone', $request->phone)->where('phone_code', $request->phone_code)->first();
            if ($user) {
                Auth::guard(getGuardedUser())->login($user);
                $token = $this->generateToken();
                $user->token = $token;
                $users = UserResource::make($user);
                return jsonSuccess($users);
            }
            return jsonSuccess();
        } else {
            return false;
        }
    }

    protected function generateToken()
    {
        $user = auth()->user();
        $token = JWTAuth::fromUser($user);
        return $token;
    }

    public function updatePassword($request)
    {
        $user = User::find(Auth::user()->id);
        $user->update(['password' => bcrypt($request->new_password)]);
        return true;
    }
}