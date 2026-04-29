<?php

namespace App\Http\Actions\ingaz;

use App\Enums\ingaz\NotificationActionEnum;
use App\Http\Actions\MainAction;
use App\Http\Resources\Api\UserResource;
use App\Http\Traits\SendSMSTrait;
use App\Http\Traits\StoreMessageTrait;
use App\Models\ingaz\PhoneVerification;
use App\Models\ingaz\User;
use Illuminate\Support\Facades\Auth;

class PhoneVerificationAction extends MainAction
{
    use SendSMSTrait, StoreMessageTrait;
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
        if (env('MESSAGE_Live') == true) {
            $code = sprintf("%05d", rand(0, 99999));
        }

        if ($phone_isset) {
            $phone_isset->update(["code" => $code]);
        } else {
            $data['code'] = $code;
            $storedData = $this->store(data: $data);
            $code = $storedData->code;
        }

        $app_domain_name = config('msegat.app_domain_for_msegat', 'click.com');

        $msg = "مرحبًا بك، نأمل استخدام رمز التفعيل للتحقق من الهاتف: {$code}. للدخول إلى منصة {$app_domain_name}";
        $result = $this->sendSMS(formatSaudiPhone($request->phone), $msg, $code);

        $user = User::where('phone', $request->phone)
            ->where('phone_code', $request->phone_code)
            ->first();

        if (isset($result['code']) && $result['code'] == "1") {
            $this->storeMessage('ارسال رسالة نصية الى العميل', $msg, NotificationActionEnum::SMS->value, $user->id);
        }

        logger()->info('Msegat SMS Sent Successfully', [
            'phone' => $request->phone,
            'code' => $code,
            'result' => $result
        ]);

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

    public function getUser($request)
    {
        return User::where('phone', $request->phone)
            ->where('phone_code', $request->phone_code)
            ->first();
    }

    public function updatePassword($request)
    {
        $user = User::find(Auth::user()->id);
        $user->update(['password' => bcrypt($request->new_password)]);
        return true;
    }
}
