<?php

use App\Models\SmsTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $templates = [
            [
                'name' => 'msaned',
                'title' => 'رسالة الإسناد',
                'channel' => SmsTemplate::CHANNEL_SMS,
                'sort_order' => 10,
                'message' => 'عزيزي {client_name}، نود إعلامكم بأن عقدكم رقم {contract_number} قد تم إسناده بنجاح. سنتواصل معكم قريباً على رقم {client_phone} لمتابعة الإجراءات. للاستفسار: {company_phone} | {company_name}',
                'variables' => ['client_name', 'contract_number', 'company_name', 'client_phone', 'client_email', 'company_phone', 'company_email'],
            ],
            [
                'name' => 'tafwed',
                'title' => 'رسالة التفويض',
                'channel' => SmsTemplate::CHANNEL_SMS,
                'sort_order' => 20,
                'message' => 'عزيزي {client_name}، تم تفويض عقدكم رقم {contract_number} بنجاح. الآن في مرحلة المعالجة والمراجعة. للاستفسار: {company_phone} أو {company_email} | {company_name}',
                'variables' => ['client_name', 'contract_number', 'company_name', 'client_phone', 'client_email', 'company_phone', 'company_email'],
            ],
            [
                'name' => 'taffez',
                'title' => 'رسالة التجهيز',
                'channel' => SmsTemplate::CHANNEL_SMS,
                'sort_order' => 30,
                'message' => 'عزيزي {client_name}، تم تجهيز عقدكم رقم {contract_number} وهو الآن في مرحلة الإعداد النهائي. {company_name}',
                'variables' => ['client_name', 'contract_number', 'company_name'],
            ],
            [
                'name' => 'tazker',
                'title' => 'رسالة التذكرة',
                'channel' => SmsTemplate::CHANNEL_SMS,
                'sort_order' => 40,
                'message' => 'عزيزي {client_name}، تم إصدار تذكرة السفر لعقدكم رقم {contract_number}. سنتواصل معكم قريباً لتحديد موعد الاستلام. {company_name}',
                'variables' => ['client_name', 'contract_number', 'company_name'],
            ],
            [
                'name' => 'completed',
                'title' => 'رسالة الإكمال',
                'channel' => SmsTemplate::CHANNEL_SMS,
                'sort_order' => 50,
                'message' => 'عزيزي {client_name}، تم إكمال عقدكم رقم {contract_number} بنجاح وتم تسليم العاملة. نتمنى لكم تجربة مميزة. للاستفسار: {company_phone} أو {company_email} | {company_name}',
                'variables' => ['client_name', 'contract_number', 'company_name', 'client_phone', 'client_email', 'company_phone', 'company_email'],
            ],
            [
                'name' => 'back',
                'title' => 'رسالة الإرجاع',
                'channel' => SmsTemplate::CHANNEL_SMS,
                'sort_order' => 60,
                'message' => 'عزيزي {client_name}، نعتذر لإبلاغكم بأن عقدكم رقم {contract_number} قد تم إرجاعه. سنتواصل معكم لتوضيح الأسباب والحلول البديلة. {company_name}',
                'variables' => ['client_name', 'contract_number', 'company_name', 'client_phone', 'client_email', 'company_phone', 'company_email'],
            ],
            [
                'name' => 'closed',
                'title' => 'رسالة الإغلاق',
                'channel' => SmsTemplate::CHANNEL_SMS,
                'sort_order' => 70,
                'message' => 'عزيزي {client_name}، تم إغلاق عقدكم رقم {contract_number}. شكراً لثقتكم بخدماتنا. للتواصل المستقبلي: {company_phone} | {company_name}',
                'variables' => ['client_name', 'contract_number', 'company_name', 'client_phone', 'client_email', 'company_phone', 'company_email'],
            ],
            [
                'name' => 'recruitment_contract_created',
                'title' => 'رسالة إنشاء عقد الاستقدام',
                'channel' => SmsTemplate::CHANNEL_WHATSAPP,
                'sort_order' => 110,
                'message' => "عميلنا العزيز،\n\nنرحب بكم بأجمل ترحيب، ونسعد كثيرًا بثقتكم الغالية في خدماتنا.\nتم بحمد الله إتمام التعاقد معكم، ونود إشعاركم بأنه قد تم البدء فعليًا في إجراءات تنفيذ عقدكم.\n\nنأمل منكم التكرم بتقييم خدمة التعاقد من خلال الاستبيان التالي:\n{feedback_url}\n\nرأيكم محل تقديرنا، ويساهم في تطوير خدماتنا لتقديم الأفضل دائمًا.\n\nشاكرين لكم حسن تعاونكم،\n{company_name}",
                'variables' => ['client_name', 'contract_number', 'feedback_url', 'company_name', 'company_phone'],
            ],
            [
                'name' => 'recruitment_contract_tazker_created',
                'title' => 'رسالة مرحلة التذكرة',
                'channel' => SmsTemplate::CHANNEL_WHATSAPP,
                'sort_order' => 120,
                'message' => "عميلنا العزيز،\n\nنود إفادتكم بأنه تم حجز تذكرة لعاملتكم المنزلية، ونعمل حاليًا على استكمال الترتيبات اللازمة لاستقبالها.\n\nنأمل منكم التكرم بتحديد مدينة استلام العاملة، وذلك لاستكمال إجراءات التنسيق والتسليم بالشكل المناسب.\n\nشاكرين تعاونكم وحرصكم، ونسعد بخدمتكم دائمًا.\n\n{company_name}",
                'variables' => ['client_name', 'contract_number', 'company_name', 'company_phone'],
            ],
            [
                'name' => 'complaint_feedback_request',
                'title' => 'رسالة طلب تقييم الشكوى',
                'channel' => SmsTemplate::CHANNEL_SMS,
                'sort_order' => 130,
                'message' => "عزيزنا العميل،\nتم حل شكواك رقم #{complaint_number}\nنود معرفة رأيك في جودة الخدمة المقدمة.\nللتقييم: {feedback_url}\nشكراً لثقتك بنا.\n{company_name}",
                'variables' => ['client_name', 'complaint_number', 'feedback_url', 'company_name', 'company_phone'],
            ],
            [
                'name' => 'complaint_status_updated',
                'title' => 'رسالة تحديث حالة الشكوى',
                'channel' => SmsTemplate::CHANNEL_SMS,
                'sort_order' => 140,
                'message' => "مرحباً {client_name}\n{complaint_status}\nالرد: {complaint_response}",
                'variables' => ['client_name', 'complaint_number', 'complaint_status', 'complaint_response', 'company_name', 'company_phone'],
            ],
            [
                'name' => 'order_started',
                'title' => 'رسالة بدء معالجة الطلب',
                'channel' => SmsTemplate::CHANNEL_SMS,
                'sort_order' => 150,
                'message' => 'تم بدء معالجة طلبكم رقم #{order_number}. شكراً لثقتكم بنا.',
                'variables' => ['client_name', 'order_number', 'company_name', 'company_phone'],
            ],
            [
                'name' => 'phone_verification_code',
                'title' => 'رسالة رمز التحقق',
                'channel' => SmsTemplate::CHANNEL_SMS,
                'sort_order' => 160,
                'message' => 'رمز التحقق: {verification_code} لدخول منصة {website_name}',
                'variables' => ['verification_code', 'website_name', 'company_name'],
            ],
        ];

        foreach ($templates as $template) {
            $existing = DB::table('sms_templates')->where('name', $template['name'])->first();
            $payload = [
                'title' => $existing?->title ?: $template['title'],
                'channel' => $existing?->channel ?: $template['channel'],
                'sort_order' => ((int) ($existing?->sort_order ?? 0)) > 0 ? (int) $existing->sort_order : $template['sort_order'],
                'variables' => json_encode($template['variables'], JSON_UNESCAPED_UNICODE),
                'updated_at' => $now,
            ];

            if (!$existing || trim((string) $existing->message) === '') {
                $payload['message'] = $template['message'];
            }

            if ($existing) {
                DB::table('sms_templates')->where('id', $existing->id)->update($payload);
                continue;
            }

            DB::table('sms_templates')->insert(array_merge($payload, [
                'name' => $template['name'],
                'message' => $template['message'],
                'is_active' => true,
                'created_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('sms_templates')
            ->whereIn('name', [
                'recruitment_contract_created',
                'recruitment_contract_tazker_created',
                'complaint_feedback_request',
                'complaint_status_updated',
                'order_started',
                'phone_verification_code',
            ])
            ->delete();
    }
};
