<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class SmsGateway
{
    public static function send($phone, string $message, $code = null, $senderType = null): ?string
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
            return self::sendViaTaqnyat($phone, $message, $code, $senderType);
        }

        return self::sendViaMsegat($phone, $message, $code, $senderType);
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

    private static function sendViaMsegat($phone, string $message, $code = null, $senderType = null): ?string
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
        curl_close($ch);

        logger()->info('Msegat SMS Sent Successfully: ', [
            'code' => $code,
            'phone' => $phone,
            'msg' => $message,
            'sender_type' => $senderType,
            'sender' => $resolvedSender,
            'response' => $result,
        ]);

        return $result === false ? null : (string) $result;
    }

    private static function sendViaTaqnyat($phone, string $message, $code = null, $senderType = null): ?string
    {
        $smsSettings = CommunicationSettings::sms();
        $resolvedSender = self::resolveSenderName($senderType, $code);
        $baseUrl = rtrim((string) data_get($smsSettings, 'taqnyat.base_url', 'https://api.taqnyat.sa'), '/');
        $bearerToken = trim((string) data_get($smsSettings, 'taqnyat.bearer_token', ''));

        $recipients = self::normalizeRecipients($phone);
        if ($recipients === []) {
            logger()->warning('Taqnyat SMS aborted: no valid recipients.', [
                'phone' => $phone,
                'sender_type' => $senderType,
            ]);

            return null;
        }

        if ($bearerToken === '') {
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
            logger()->error('Taqnyat SMS request failed.', [
                'code' => $code,
                'phone' => $phone,
                'msg' => $message,
                'sender_type' => $senderType,
                'sender' => $resolvedSender,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        $result = $response->body();

        if (!$response->successful()) {
            logger()->warning('Taqnyat SMS rejected.', [
                'code' => $code,
                'phone' => $phone,
                'msg' => $message,
                'sender_type' => $senderType,
                'sender' => $resolvedSender,
                'status' => $response->status(),
                'response' => $result,
            ]);

            return null;
        }

        logger()->info('Taqnyat SMS Sent Successfully: ', [
            'code' => $code,
            'phone' => $phone,
            'msg' => $message,
            'sender_type' => $senderType,
            'sender' => $resolvedSender,
            'status' => $response->status(),
            'response' => $result,
        ]);

        return $result === '' ? null : $result;
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
