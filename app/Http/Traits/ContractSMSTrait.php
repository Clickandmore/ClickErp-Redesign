<?php

namespace App\Http\Traits;

use App\Models\Contract;
use App\Models\SmsTemplate;
use App\Enums\ingaz\ContractStatusEnum;
use App\Enums\ingaz\NotificationActionEnum;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\SendSMSTrait;

trait ContractSMSTrait
{
    use SendSMSTrait, StoreMessageTrait;
    /**
     * Send SMS notification to client about contract status update
     *
     * @param Contract $contract
     * @param string $newStatus
     * @return bool
     */
    public function sendContractStatusSMS(Contract $contract, string $newStatus)
    {
        try {
            // التحقق من وجود رقم هاتف العميل
            if (!$contract->user || !$contract->user->phone) {
                Log::warning('Cannot send SMS: Client phone number not found', [
                    'contract_id' => $contract->id
                ]);
                return false;
            }

            // إعداد رسالة SMS حسب الحالة
            $message = $this->getStatusSMSMessage($contract, $newStatus);

            if (!$message) {
                Log::warning('Cannot send SMS: No message template found for status', [
                    'contract_id' => $contract->id,
                    'status' => $newStatus
                ]);
                return false;
            }

            // إرسال الرسالة (يمكن تخصيص هذا الجزء حسب مزود الخدمة)
            $result = $this->sendSMSMessage($contract->user->phone, $message, $contract->user->id);

            // تسجيل العملية
            Log::info('Contract status SMS sent', [
                'contract_id' => $contract->id,
                'client_phone' => $contract->user->phone,
                'status' => $newStatus,
                'success' => $result
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Error sending contract status SMS', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

        /**
     * Get SMS message based on contract status
     *
     * @param Contract $contract
     * @param string $status
     * @return string|null
     */
    private function getStatusSMSMessage(Contract $contract, string $status)
    {
        // محاولة جلب القالب من قاعدة البيانات أولاً
        $template = SmsTemplate::getByName($status);

        if ($template) {
            // استخدام قالب قاعدة البيانات
            $variables = [
                'client_name' => $contract->user->name ?? 'عزيزنا العميل',
                'contract_number' => $contract->id,
                'company_name' => 'شركة بلاتنيوم الشرقية للاستقدام',
                'client_phone' => $contract->user->phone ?? '',
                'client_email' => $contract->user->email ?? '',
                'company_phone' => '0112345678',
                'company_email' => 'info@platinumeastern.com'
            ];

            return $template->processMessage($variables);
        }

        // إذا لم يوجد قالب في قاعدة البيانات، استخدم القوالب الافتراضية
        $clientName = $contract->user->name ?? 'عزيزنا العميل';
        $contractNumber = $contract->id;
        $companyName = 'شركة بلاتنيوم الشرقية للاستقدام';

        $messages = [
            ContractStatusEnum::Msaned->value => "عزيزي {$clientName}، نود إعلامكم بأن عقدكم رقم {$contractNumber} قد تم إسناده بنجاح. سنتواصل معكم قريباً لمتابعة الإجراءات. {$companyName}",

            ContractStatusEnum::Tafwed->value => "عزيزي {$clientName}، تم تفويض عقدكم رقم {$contractNumber} بنجاح. الآن في مرحلة المعالجة والمراجعة. {$companyName}",

            ContractStatusEnum::Taffez->value => "عزيزي {$clientName}، تم تجهيز عقدكم رقم {$contractNumber} وهو الآن في مرحلة الإعداد النهائي. {$companyName}",

            ContractStatusEnum::Tazker->value => "عزيزي {$clientName}، تم إصدار تذكرة السفر لعقدكم رقم {$contractNumber}. سنتواصل معكم قريباً لتحديد موعد الاستلام. {$companyName}",

            ContractStatusEnum::Completed->value => "عزيزي {$clientName}، تم إكمال عقدكم رقم {$contractNumber} بنجاح وتم تسليم العاملة. نتمنى لكم تجربة مميزة. للاستفسار: اتصل بنا. {$companyName}",

            ContractStatusEnum::Back->value => "عزيزي {$clientName}، نعتذر لإبلاغكم بأن عقدكم رقم {$contractNumber} قد تم إرجاعه. سنتواصل معكم لتوضيح الأسباب والحلول البديلة. {$companyName}",

            ContractStatusEnum::Closed->value => "عزيزي {$clientName}، تم إغلاق عقدكم رقم {$contractNumber}. شكراً لثقتكم بخدماتنا. {$companyName}"
        ];

        return $messages[$status] ?? null;
    }

    /**
     * Send SMS message using SMS service provider
     * This is a placeholder - implement according to your SMS provider
     *
     * @param string $phone
     * @param string $message
     * @return bool
     */
    private function sendSMSMessage(string $phone, string $message, $user_id = null)
    {
        try {
            // هنا يمكن تنفيذ إرسال الرسالة الفعلي باستخدام مزود الخدمة
            // مثال: استخدام Twilio, Nexmo, أو أي مزود SMS محلي

            /*
            // مثال باستخدام Twilio
            $twilio = new Client($accountSid, $authToken);
            $message = $twilio->messages->create(
                $phone,
                [
                    'from' => $fromNumber,
                    'body' => $message
                ]
            );
            return true;
            */
            
            // Send SMS using the existing SendSMSTrait
            $result = $this->sendSMS(formatSaudiPhone($phone), $message);

            if (isset($result['code']) && $result['code'] == "1") {
                $this->storeMessage('ارسال رسالة نصية الى العميل', $message, NotificationActionEnum::SMS->value, $user_id);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
