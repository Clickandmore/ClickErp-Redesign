<?php

namespace App\Services;

use App\Models\CommunicationLog;
use App\Support\MessageSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TeqnyatSmsService
{
    public function sendMessage(?string $phone, string $message, array $context = []): array
    {
        $normalized = $this->normalizePhone($phone);
        if (!$normalized || !\App\Support\CommunicationSettings::smsIsConfigured()) {
            return $this->finalizeResult($phone, $message, ['success'=>false,'status'=>'skipped','message'=>'بوابة الرسائل غير مهيأة أو الرقم غير صالح.'], $context);
        }
        try {
            $verification = ($context['source'] ?? '') === 'hourly_staff_login';
            $raw = \App\Support\SmsGateway::send($normalized, $message, null, $verification ? 'verification' : null);
            $response = json_decode((string) $raw, true) ?: [];
            $provider = \App\Support\CommunicationSettings::sms()['provider'] ?? 'msegat';
            $success = $provider === 'taqnyat'
                ? in_array((int) ($response['statusCode'] ?? 0), [200, 201, 202], true)
                : in_array((string) ($response['code'] ?? ''), ['1', 'M0000'], true);
            return $this->finalizeResult($phone, $message, ['success'=>$success,'status'=>$success?'sent':'failed','to'=>$normalized,'response'=>$response], $context);
        } catch (\Throwable $e) {
            report($e);
            return $this->finalizeResult($phone, $message, ['success'=>false,'status'=>'failed','message'=>'تعذر إرسال الرسالة.'], $context);
        }
    }

    protected function endpoint(): string
    {
        $configuredUrl = trim(MessageSettings::messageUrl());

        if ($configuredUrl === '') {
            return 'https://api.taqnyat.sa/v1/messages';
        }

        if (str_contains($configuredUrl, '/v1/messages')) {
            return $configuredUrl;
        }

        return rtrim($configuredUrl, '/') . '/v1/messages';
    }

    protected function isEnabled(): bool
    {
        return MessageSettings::smsEnabled();
    }

    protected function hasRequiredConfiguration(): bool
    {
        return filled(MessageSettings::token())
            && filled(MessageSettings::sender());
    }

    protected function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '9660')) {
            $digits = '966' . substr($digits, 4);
        } elseif (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '966' . substr($digits, 1);
        } elseif (str_starts_with($digits, '5') && strlen($digits) === 9) {
            $digits = '966' . $digits;
        }

        return $digits;
    }

    protected function skip(string $message): array
    {
        return [
            'success' => false,
            'status' => 'skipped',
            'message' => $message,
        ];
    }

    protected function finalizeResult(?string $phone, string $message, array $result, array $context = []): array
    {
        $this->logAttempt($phone, $message, $result, $context);

        return $result;
    }

    protected function logAttempt(?string $phone, string $message, array $result, array $context = []): void
    {
        if (($context['should_log'] ?? true) !== true) {
            return;
        }

        try {
            CommunicationLog::create([
                'batch_id' => $context['batch_id'] ?? (string) Str::uuid(),
                'channel' => 'sms',
                'recipient_type' => $context['recipient_type'] ?? 'direct',
                'user_id' => $context['user_id'] ?? null,
                'admin_id' => $context['admin_id'] ?? auth('admin')->id(),
                'notification_id' => $context['notification_id'] ?? null,
                'recipient_name' => $context['recipient_name'] ?? null,
                'recipient_phone' => $result['to'] ?? $phone,
                'title' => $context['title'] ?? null,
                'message' => $context['message'] ?? $message,
                'provider' => (\App\Support\CommunicationSettings::sms()['provider'] ?? 'sms'),
                'status' => $result['status'] ?? 'failed',
                'response' => $this->stringifyResponse($result['response'] ?? $result['error'] ?? $result['message'] ?? $result),
                'meta' => $this->buildMeta($phone, $result, $context),
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Unable to persist SMS communication log', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function buildMeta(?string $phone, array $result, array $context = []): ?array
    {
        $meta = array_filter([
            'raw_phone' => $phone,
            'normalized_phone' => $result['to'] ?? null,
            'source' => $context['source'] ?? 'direct',
            'http_status' => $result['http_status'] ?? null,
        ], fn ($value) => filled($value));

        if (! empty($context['meta']) && is_array($context['meta'])) {
            $meta = array_merge($meta, $context['meta']);
        }

        return $meta ?: null;
    }

    protected function stringifyResponse($response): ?string
    {
        if (blank($response)) {
            return null;
        }

        if (is_string($response)) {
            return $response;
        }

        return json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
