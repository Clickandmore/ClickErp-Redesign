<?php

namespace App\Support;

use App\Models\SmsTemplate;
use App\Services\WhatsApp\WhatsAppMessagingService;
use Illuminate\Support\Facades\Schema;

/**
 * قائمة تحقق «هل كليك شات جاهز ليحل محل الرسائل النصية؟» — تُقرأ من الإعدادات والقوالب فقط،
 * بلا أي اتصال بالمنصة حتى لا تبطئ الصفحة. اختبار الاتصال الفعلي زر منفصل.
 */
class ClickChatReadiness
{
    /** @return array<int, array{key: string, ok: bool, label: string, hint: string}> */
    public static function checks(): array
    {
        $whatsapp = CommunicationSettings::whatsapp();
        $clickchat = (array) ($whatsapp['clickchat'] ?? []);
        $isActive = ($whatsapp['driver'] ?? '') === 'clickchat';
        $hasBindingColumn = Schema::hasTable('sms_templates') && Schema::hasColumn('sms_templates', 'clickchat_binding');

        $whatsappTemplates = Schema::hasTable('sms_templates')
            ? SmsTemplate::query()->where('is_active', true)->where('channel', SmsTemplate::CHANNEL_WHATSAPP)->get()
            : collect();
        $unbound = $hasBindingColumn
            ? $whatsappTemplates->filter(fn (SmsTemplate $t) => empty(data_get($t->clickchat_binding, 'name')))
            : $whatsappTemplates;
        $verification = $whatsappTemplates->firstWhere('name', CommunicationSettings::VERIFICATION_TEMPLATE);
        $verificationChannel = CommunicationSettings::verificationChannel();

        return [
            [
                'key' => 'driver',
                'ok' => $isActive,
                'label' => 'Click Live Chat هو مزود واتساب الحالي',
                'hint' => 'اختره في حقل «مزود واتساب» ثم احفظ.',
            ],
            [
                'key' => 'credentials',
                'ok' => trim((string) ($clickchat['base_url'] ?? '')) !== '' && trim((string) ($clickchat['api_token'] ?? '')) !== '',
                'label' => 'رابط المنصة ومفتاح API محفوظان',
                'hint' => 'من لوحة Click Live Chat: الإعدادات ← مفاتيح API.',
            ],
            [
                'key' => 'sender',
                'ok' => trim((string) ($clickchat['default_channel_account_id'] ?? '')) !== '',
                'label' => 'الرقم المرسِل الافتراضي محدد',
                'hint' => 'اضغط «اختبار الاتصال» ثم اختر رقم Meta الرسمي (أرقام QR لا تدعم القوالب المعتمدة).',
            ],
            [
                'key' => 'webhook',
                'ok' => str_starts_with((string) ($clickchat['webhook_secret'] ?? ''), 'whsec_'),
                'label' => 'المفتاح السري للـ Webhook محفوظ (لاستقبال حالة التسليم والقراءة والردود)',
                'hint' => 'سجّل رابط الـ Webhook في المنصة وانسخ المفتاح الذي يبدأ بـ whsec_.',
            ],
            [
                'key' => 'templates_bound',
                'ok' => $hasBindingColumn && $whatsappTemplates->isNotEmpty() && $unbound->isEmpty(),
                'label' => $whatsappTemplates->isEmpty()
                    ? 'لا توجد قوالب قناتها واتساب بعد'
                    : 'كل قوالب واتساب مربوطة بقالب معتمد (' . ($whatsappTemplates->count() - $unbound->count()) . ' من ' . $whatsappTemplates->count() . ')',
                'hint' => $hasBindingColumn
                    ? 'القالب غير المربوط يُرسل نصاً حراً، ولا يصل إلا لعميل راسلكم خلال آخر 24 ساعة. اربطه من «قوالب الرسائل».'
                    : 'شغّل ترحيلات قاعدة البيانات أولاً (عمود clickchat_binding غير موجود).',
            ],
            [
                'key' => 'verification',
                'ok' => $verificationChannel === 'whatsapp'
                    && $hasBindingColumn
                    && $verification
                    && !empty(data_get($verification->clickchat_binding, 'name')),
                'label' => 'رمز التحقق يُرسل عبر واتساب بقالب معتمد',
                'hint' => $verificationChannel === 'whatsapp'
                    ? 'اربط قالب «رمز التحقق» بقالب معتمد من فئة AUTHENTICATION في Meta.'
                    : 'رمز التحقق ما زال يُرسل SMS. غيّره من بطاقة «رمز التحقق» أدناه.',
            ],
            [
                'key' => 'fallback',
                'ok' => ($clickchat['fallback'] ?? 'none') !== 'none' || CommunicationSettings::verification()['fallback_to_sms'],
                'label' => 'توجد قناة احتياطية عند فشل واتساب',
                'hint' => 'اختر «القناة الاحتياطية» للرسائل العامة، وفعّل «الرجوع إلى SMS» لرمز التحقق.',
            ],
        ];
    }

    public static function whatsappIsConfigured(): bool
    {
        return app(WhatsAppMessagingService::class)->isConfigured();
    }
}
