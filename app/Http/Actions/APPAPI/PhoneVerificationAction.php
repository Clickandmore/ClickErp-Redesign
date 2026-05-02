<?php

namespace App\Http\Actions\APPAPI;

use App\Enums\ingaz\NotificationActionEnum;
use App\Http\Actions\MainAction;
use App\Http\Resources\Api\UserResource;
use App\Http\Traits\SendSMSTrait;
use App\Http\Traits\StoreMessageTrait;
use App\Models\ingaz\PhoneVerification;
use App\Models\ingaz\User;
use App\Models\SmsTemplate;
use App\Services\WhatsApp\WhatsAppMessagingService;
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
        $phoneExists = $this->model->where('phone', $request->phone)
            ->where('phone_code', $request->phone_code)
            ->first();

        $code = '12345';
        if (env('MESSAGE_Live') == true) {
            $code = sprintf("%05d", rand(0, 99999));
        }

        if ($phoneExists) {
            $phoneExists->update(['code' => $code]);
        } else {
            $data['code'] = $code;
            $storedData = $this->store(data: $data);
            $code = $storedData->code;
        }

        $appDomainName = config('msegat.app_domain_for_msegat', 'click.com');
        $template = SmsTemplate::getByName('phone_verification_code');
        $variables = [
            'verification_code' => $code,
            'website_name' => $appDomainName,
            'company_name' => documentSetting('document_company_name', documentSetting('website_name', config('app.name'))),
        ];

        $msg = $template
            ? $template->processMessage($variables)
            : "مرحبًا بك، نأمل استخدام رمز التفعيل للتحقق من الهاتف: {$code}. للدخول إلى منصة {$appDomainName}";

        if ($template && $template->channel === SmsTemplate::CHANNEL_WHATSAPP) {
            $result = app(WhatsAppMessagingService::class)->sendText($request->phone, $msg, [
                'purpose' => 'verification',
                'template_id' => $template->id,
                'source' => 'phone_verification_code',
            ]);
        } else {
            $result = $this->sendSMS(formatSaudiPhone($request->phone), $msg, $code, 'verification');
        }

        $user = User::where('phone', $request->phone)
            ->where('phone_code', $request->phone_code)
            ->first();

        if ($this->messageWasSent($result)) {
            $this->storeMessage('ارسال رسالة تحقق الى العميل', $msg, NotificationActionEnum::SMS->value, optional($user)->id);
        }

        logger()->info('App API SMS Sent Successfully', [
            'phone' => $request->phone,
            'code' => $code,
            'result' => $result,
        ]);

        return ['code' => $code];
    }

    private function messageWasSent($result): bool
    {
        if (is_array($result)) {
            if (array_key_exists('success', $result)) {
                return (bool) $result['success'];
            }

            return isset($result['code']) && in_array((string) $result['code'], ['1', '200'], true);
        }

        $decoded = json_decode((string) $result, true);
        if (is_array($decoded)) {
            return isset($decoded['code']) && in_array((string) $decoded['code'], ['1', '200'], true);
        }

        return false;
    }

    public function confirmCode($request)
    {
        $codeExists = $this->model->where('phone', $request->phone)
            ->where('phone_code', $request->phone_code)
            ->where('code', $request->code)
            ->first();

        if (!$codeExists) {
            return false;
        }

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
