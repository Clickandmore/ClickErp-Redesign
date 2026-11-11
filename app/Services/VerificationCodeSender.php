<?php

namespace App\Services;

use App\Models\SmsTemplate;
use App\Services\WhatsApp\WhatsAppMessagingService;
use App\Support\CommunicationSettings;
use App\Support\SmsGateway;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * يرسل رمز التحقق عبر القناة المختارة لقالب phone_verification_code:
 * واتساب (عبر المزود المربوط، مثل Click Live Chat بقالب معتمد) أو SMS،
 * مع الرجوع إلى SMS عند فشل واتساب إن كان ذلك مفعلاً في «إعدادات الربط».
 *
 * لا يسجل الرمز نفسه في أي سجل.
 */
class VerificationCodeSender
{
    /**
     * @return array{success: bool, channel: string, fallback_used: bool, message: string, result: mixed}
     */
    public function send(string $phone, string $code, string $source, array $extraVariables = []): array
    {
        $template = Schema::hasTable('sms_templates')
            ? SmsTemplate::getByName(CommunicationSettings::VERIFICATION_TEMPLATE)
            : null;
        $variables = $extraVariables + [
            'verification_code' => $code,
            'website_name' => (string) (CommunicationSettings::sms()['app_domain_for_msegat'] ?? config('app.url')),
            'company_name' => documentSetting('document_company_name', documentSetting('website_name', config('app.name'))),
        ];
        $message = $template
            ? $template->processMessage($variables)
            : "رمز التحقق الخاص بك: {$code}";

        $channel = CommunicationSettings::verificationChannel();
        $fallbackUsed = false;

        if ($channel === 'whatsapp') {
            $result = app(WhatsAppMessagingService::class)->sendText($phone, $message, [
                'purpose' => 'verification',
                'template_id' => $template?->id,
                // تمرير القيم يجعل ربط قالب Click Live Chat يعتمد عليها مباشرة بدل مطابقة النص.
                'template_variables' => $variables,
                'source' => $source,
            ]);

            if (!self::wasSent($result)
                && CommunicationSettings::verification()['fallback_to_sms']
                && CommunicationSettings::smsIsConfigured()) {
                Log::info('Verification code fell back to SMS', [
                    'source' => $source,
                    'error' => is_array($result) ? ($result['error'] ?? null) : null,
                ]);
                $result = SmsGateway::send(formatSaudiPhone($phone), $message, $code, 'verification', ['source' => $source]);
                $fallbackUsed = true;
            }
        } else {
            $result = SmsGateway::send(formatSaudiPhone($phone), $message, $code, 'verification', ['source' => $source]);
        }

        return [
            'success' => self::wasSent($result),
            'channel' => $fallbackUsed ? 'sms' : $channel,
            'fallback_used' => $fallbackUsed,
            'message' => $message,
            'result' => $result,
        ];
    }

    public static function wasSent($result): bool
    {
        if (is_array($result)) {
            if (array_key_exists('success', $result)) {
                return (bool) $result['success'];
            }

            return isset($result['code']) && in_array((string) $result['code'], ['1', '200'], true);
        }

        $decoded = json_decode((string) $result, true);

        return is_array($decoded)
            && isset($decoded['code'])
            && in_array((string) $decoded['code'], ['1', '200'], true);
    }
}
