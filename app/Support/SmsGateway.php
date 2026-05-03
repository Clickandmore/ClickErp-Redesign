<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class SmsGateway
{
    public static function send($phone, string $message, $code = null, $senderType = null): ?string
    {
        $provider = strtolower((string) config('msegat.provider', 'msegat'));

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
        $resolvedType = strtolower(trim((string) ($senderType ?? '')));
        if ($resolvedType === '') {
            $resolvedType = $code !== null
                ? 'verification'
                : strtolower((string) config('msegat.default_sender_type', 'advertising'));
        }

        if ($resolvedType === 'verification') {
            return (string) config('msegat.verification_sender', config('msegat.sender', ''));
        }

        if ($resolvedType === 'advertising') {
            return (string) config('msegat.advertising_sender', config('msegat.sender', ''));
        }

        return (string) config('msegat.sender', '');
    }

    private static function sendViaMsegat($phone, string $message, $code = null, $senderType = null): ?string
    {
        $resolvedSender = self::resolveSenderName($senderType, $code);

        $ch = curl_init(config('msegat.url'));
        $data = [
            'userName' => config('msegat.username'),
            'apiKey' => config('msegat.api_key'),
            'numbers' => $phone,
            'userSender' => $resolvedSender,
            'msg' => $message,
            'msgEncoding' => config('msegat.encoding'),
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
        $resolvedSender = self::resolveSenderName($senderType, $code);
        $baseUrl = rtrim((string) config(
            'services.taqnyat.sms.base_url',
            config('msegat.taqnyat.base_url', 'https://api.taqnyat.sa')
        ), '/');
        $bearerToken = trim((string) (
            config('services.taqnyat.sms.bearer_token')
            ?? config('services.taqnyat.sms.bearer')
            ?? config('msegat.taqnyat.bearer_token')
            ?? ''
        ));

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
