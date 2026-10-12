<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class SmsGateway
{
    public static function send($phone, string $message, $code = null, $senderType = null, array $context = []): ?string
    {
        $settings = CommunicationSettings::sms();
        $provider = strtolower((string) ($settings['provider'] ?? 'msegat'));
        $context += ['verification' => $code !== null || $senderType === 'verification', 'sender' => self::resolveSenderName($senderType, $code)];
        $recipients = is_array($phone) ? $phone : (preg_split('/\s*,\s*/', (string) $phone) ?: ['']);
        $logs = [];
        foreach ($recipients as $to) $logs[] = OutboundMessageRecorder::begin('sms', (string) $to, $message, $provider, $context);
        $details = [];
        try {
            $raw = self::sendRaw($phone, $message, $code, $senderType, $details);
            $disabled = (string) ($settings['gateway_status'] ?? 'on') === 'off';
            foreach ($logs as $index => $log) {
                $accepted = !$disabled && $raw !== null && (empty($details['status_code']) || $details['status_code'] < 400) && OutboundMessageRecorder::smsAccepted($raw, (string) $recipients[$index]);
                $decoded = json_decode((string) $raw, true) ?: [];
                OutboundMessageRecorder::finish($log, $disabled ? 'skipped' : ($accepted ? 'accepted' : 'failed'), $details + [
                    'response' => json_decode((string) $raw, true) ?: ['message' => $raw],
                    'error_message' => $disabled ? 'بوابة SMS متوقفة في الإعدادات.' : null,
                    'message_id' => $decoded['messageId'] ?? $decoded['message_id'] ?? null,
                    'safe_error' => $disabled ? 'بوابة SMS متوقفة في الإعدادات.' : ($details['error_message'] ?? data_get($details, 'response.message') ?? $decoded['message'] ?? 'لم يقبل المزوّد رسالة التحقق؛ راجع إعدادات البوابة ورمز الاستجابة.'),
                ]);
            }
            return $raw;
        } catch (\Throwable $e) {
            foreach ($logs as $log) OutboundMessageRecorder::finish($log, 'failed', ['error_message' => $e->getMessage()]);
            throw $e;
        }
    }

    private static function sendRaw($phone, string $message, $code, $senderType, array &$details): ?string
    {
        $smsSettings = CommunicationSettings::sms();
        if ((string) ($smsSettings['gateway_status'] ?? 'on') === 'off') {
            logger()->info('SMS gateway disabled; message was not sent.', [
                'phone' => $phone,
                'sender_type' => $senderType,
            ]);

            return null;
        }

        $provider = strtolower((string) ($smsSettings['provider'] ?? 'msegat'));

        logger()->info('SMS provider: ', [
            'provider' => $provider,
        ]);

        if ($provider === 'taqnyat') {
            return self::sendViaTaqnyat($phone, $message, $code, $senderType, $details);
        }

        return self::sendViaMsegat($phone, $message, $code, $senderType, $details);
    }

    public static function resolveSenderName($senderType = null, $code = null): string
    {
        $smsSettings = CommunicationSettings::sms();
        $resolvedType = strtolower(trim((string) ($senderType ?? '')));
        if ($resolvedType === '') {
            $resolvedType = $code !== null
                ? 'verification'
                : strtolower((string) ($smsSettings['default_sender_type'] ?? 'advertising'));
        }

        if ($resolvedType === 'verification') {
            return (string) ($smsSettings['verification_sender'] ?: ($smsSettings['sender'] ?? ''));
        }

        if ($resolvedType === 'advertising') {
            return (string) ($smsSettings['advertising_sender'] ?: ($smsSettings['sender'] ?? ''));
        }

        return (string) ($smsSettings['sender'] ?? '');
    }

    private static function sendViaMsegat($phone, string $message, $code, $senderType, array &$details): ?string
    {
        $smsSettings = CommunicationSettings::sms();
        $resolvedSender = self::resolveSenderName($senderType, $code);

        $ch = curl_init((string) ($smsSettings['url'] ?? 'https://www.msegat.com/gw/sendsms.php'));
        $data = [
            'userName' => (string) ($smsSettings['username'] ?? ''),
            'apiKey' => (string) ($smsSettings['api_key'] ?? ''),
            'numbers' => $phone,
            'userSender' => $resolvedSender,
            'msg' => $message,
            'msgEncoding' => (string) ($smsSettings['encoding'] ?? 'UTF8'),
        ];

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        $details['status_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: null;
        if ($result === false) $details['error_message'] = curl_error($ch) ?: 'تعذر الاتصال بمزوّد SMS.';
        curl_close($ch);

        logger()->info('Msegat SMS Sent Successfully: ', [
            'code' => self::safeLogCode($code),
            'phone' => $phone,
            'msg' => self::safeLogMessage($message, $code, $senderType),
            'sender_type' => $senderType,
            'sender' => $resolvedSender,
            'response' => $result,
        ]);

        return $result === false ? null : (string) $result;
    }

    private static function sendViaTaqnyat($phone, string $message, $code, $senderType, array &$details): ?string
    {
        $smsSettings = CommunicationSettings::sms();
        $resolvedSender = self::resolveSenderName($senderType, $code);
        $baseUrl = rtrim((string) data_get($smsSettings, 'taqnyat.base_url', 'https://api.taqnyat.sa'), '/');
        $bearerToken = trim((string) data_get($smsSettings, 'taqnyat.bearer_token', ''));

        $recipients = self::normalizeRecipients($phone);
        if ($recipients === []) {
            $details['error_message'] = 'لا يوجد رقم مستلم صالح.';
            logger()->warning('Taqnyat SMS aborted: no valid recipients.', [
                'phone' => $phone,
                'sender_type' => $senderType,
            ]);

            return null;
        }

        if ($bearerToken === '') {
            $details['error_message'] = 'إعدادات بوابة SMS غير مكتملة (رمز الاتصال).';
            logger()->error('Taqnyat SMS aborted: missing bearer token.', [
                'phone' => $phone,
                'sender_type' => $senderType,
                'sender' => $resolvedSender,
            ]);

            return null;
        }

        try {
            $response = Http::withToken($bearerToken)
                ->acceptJson()
                ->asJson()
                ->timeout(15)
                ->post($baseUrl . '/v1/messages', [
                    'recipients' => $recipients,
                    'sender' => $resolvedSender,
                    'body' => $message,
                ]);
        } catch (\Throwable $exception) {
            $details['error_message'] = $exception->getMessage();
            logger()->error('Taqnyat SMS request failed.', [
                'code' => self::safeLogCode($code),
                'phone' => $phone,
                'msg' => self::safeLogMessage($message, $code, $senderType),
                'sender_type' => $senderType,
                'sender' => $resolvedSender,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        $result = $response->body();
        $details['status_code'] = $response->status();
        $details['response'] = $response->json() ?: ['message' => $result];

        if (!$response->successful()) {
            logger()->warning('Taqnyat SMS rejected.', [
                'code' => self::safeLogCode($code),
                'phone' => $phone,
                'msg' => self::safeLogMessage($message, $code, $senderType),
                'sender_type' => $senderType,
                'sender' => $resolvedSender,
                'status' => $response->status(),
                'response' => $result,
            ]);

            return null;
        }

        logger()->info('Taqnyat SMS Sent Successfully: ', [
            'code' => self::safeLogCode($code),
            'phone' => $phone,
            'msg' => self::safeLogMessage($message, $code, $senderType),
            'sender_type' => $senderType,
            'sender' => $resolvedSender,
            'status' => $response->status(),
            'response' => $result,
        ]);

        return $result === '' ? null : $result;
    }

    private static function safeLogCode($code): ?string
    {
        return $code === null ? null : '[redacted]';
    }

    private static function safeLogMessage(string $message, $code, $senderType): string
    {
        if ($code !== null || strtolower((string) $senderType) === 'verification') {
            return '[verification message redacted]';
        }

        return $message;
    }

    private static function normalizeRecipients($phone): array
    {
        if (is_array($phone)) {
            $recipients = $phone;
        } else {
            $recipients = preg_split('/\s*,\s*/', (string) $phone) ?: [];
        }

        return array_values(array_filter(array_map(
            static fn ($recipient): string => preg_replace('/\D+/', '', (string) $recipient),
            $recipients
        )));
    }
}
