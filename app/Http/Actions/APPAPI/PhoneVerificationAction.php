<?php

namespace App\Http\Actions\APPAPI;

use App\Enums\ingaz\NotificationActionEnum;
use App\Http\Actions\MainAction;
use App\Http\Resources\Api\UserResource;
use App\Http\Traits\SendSMSTrait;
use App\Http\Traits\StoreMessageTrait;
use App\Models\ingaz\PhoneVerification;
use App\Models\ingaz\User;
use App\Services\VerificationCodeSender;
use App\Support\CommunicationSettings;
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
        $reviewCode = \App\Support\HourlyAppReviewAccounts::code('customer', (string) $request->phone, null, (string) $request->phone_code);
        if ($reviewCode !== null) {
            return ['code' => $reviewCode, 'review_account' => true];
        }
        $phoneExists = $this->model->where('phone', $request->phone)
            ->where('phone_code', $request->phone_code)
            ->first();

        $verificationDisabled = CommunicationSettings::phoneVerificationIsDisabled();
        // الرمز الثابت فقط عند إلغاء التحقق صراحةً؛ إيقاف بوابة SMS لا يفتح الدخول برمز ثابت.
        $code = $verificationDisabled
            ? CommunicationSettings::phoneVerificationBypassCode()
            : sprintf('%05d', random_int(0, 99999));

        if ($phoneExists) {
            $phoneExists->update(['code' => $code]);
        } else {
            $data['code'] = $code;
            $storedData = $this->store(data: $data);
            $code = $storedData->code;
        }

        if ($verificationDisabled) {
            return ['code' => $code, 'verification_disabled' => true];
        }

        // القناة (SMS أو واتساب مع الرجوع إلى SMS) تُحدد من «إعدادات الربط ← رمز التحقق».
        $delivery = app(VerificationCodeSender::class)->send((string) $request->phone, (string) $code, 'phone_verification_code');

        $user = User::where('phone', $request->phone)
            ->where('phone_code', $request->phone_code)
            ->first();

        if ($delivery['success']) {
            $this->storeMessage('ارسال رسالة تحقق الى العميل', $delivery['message'], NotificationActionEnum::SMS->value, optional($user)->id);
        }

        logger()->info('App API verification code dispatched', [
            'phone' => $request->phone,
            'channel' => $delivery['channel'],
            'fallback_used' => $delivery['fallback_used'],
            'sent' => $delivery['success'],
        ]);

        return ['code' => $code];
    }

    public function confirmCode($request)
    {
        $reviewCode = \App\Support\HourlyAppReviewAccounts::code('customer', (string) $request->phone, null, (string) $request->phone_code);
        if ($reviewCode !== null) {
            // Review credentials live in their own settings, never in the site's OTP table.
            return hash_equals($reviewCode, (string) $request->code) ? $this->confirmedResponse($request) : false;
        }
        if (CommunicationSettings::phoneVerificationIsDisabled()
            && hash_equals(CommunicationSettings::phoneVerificationBypassCode(), (string) $request->code)) {
            return $this->confirmedResponse($request);
        }

        $codeExists = $this->model->where('phone', $request->phone)
            ->where('phone_code', $request->phone_code)
            ->where('code', $request->code)
            ->first();

        if (!$codeExists) {
            return false;
        }

        return $this->confirmedResponse($request);
    }

    private function confirmedResponse($request)
    {
        // الكود صحيح: يُسمح لهذا الرقم بتغيير كلمة المرور خلال مدة قصيرة
        \App\Support\PasswordResetGrant::grant($request->phone);

        $user = User::where('phone', $request->phone)
            ->where('phone_code', $request->phone_code)
            ->first();

        if ($user) {
            return jsonSuccess(UserResource::make($user));
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
