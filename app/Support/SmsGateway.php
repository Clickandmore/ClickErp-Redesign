<?php

namespace App\Support;

class SmsGateway
{
    public static function send($phone, string $message, $code = null, $senderType = null): ?string
    {
        $provider = strtolower((string) config('msegat.provider', 'msegat'));

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
        $baseUrl = rtrim((string) config('services.taqnyat.sms.base_url', 'https://api.taqnyat.sa'), '/');
        $bearerToken = (string) config('services.taqnyat.sms.bearer_token', '');

        $recipients = self::normalizeRecipients($phone);

        $ch = curl_init($baseUrl . '/v1/messages');
        $data = [
            'sender' => $resolvedSender,
            'recipients' => $recipients,
            'body' => $message,
        ];

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $bearerToken,
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        curl_close($ch);

        logger()->info('Taqnyat SMS Sent Successfully: ', [
            'code' => $code,
            'phone' => $phone,
            'msg' => $message,
            'sender_type' => $senderType,
            'sender' => $resolvedSender,
            'response' => $result,
        ]);

        return $result === false ? null : (string) $result;
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
