<?php

namespace App\Services;

use App\Models\CommunicationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UltraMsgWhatsAppService
{
    public function sendChatMessage(?string $phone, string $message, array $context = []): array
    {
        $gateway = app(\App\Services\WhatsApp\WhatsAppMessagingService::class);
        $normalized = $this->normalizePhone($phone);
        if (!$normalized || !$gateway->isConfigured()) return $this->finalizeResult($phone, $message, ['success'=>false,'status'=>'skipped'], $context);
        try {
            $result = $gateway->sendText($normalized, $message, $context);
            $result['status'] = !empty($result['success']) ? 'sent' : 'failed';
            $result['to'] = $normalized;
            return $this->finalizeResult($phone, $message, $result, $context);
        } catch (\Throwable $e) {
            report($e);
            return $this->finalizeResult($phone, $message, ['success'=>false,'status'=>'failed'], $context);
        }
    }

    protected function isEnabled(array $settings): bool
    {
        return (bool) ($settings['enabled'] ?? false) || $this->hasRequiredConfiguration($settings);
    }

    protected function hasRequiredConfiguration(array $settings): bool
    {
        return filled($settings['instance_id'] ?? null)
            && filled($settings['token'] ?? null);
    }

    protected function endpoint(array $settings): string
    {
        $baseUrl = rtrim((string) ($settings['base_url'] ?? 'https://api.ultramsg.com'), '/');
        $instanceId = trim((string) $settings['instance_id']);

        if (str_contains($baseUrl, '/messages/chat')) {
            return $baseUrl;
        }

        return $baseUrl . '/' . $instanceId . '/messages/chat';
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

    protected function skip(string $message, ?string $phone = null): array
    {
        Log::info('UltraMsg WhatsApp message skipped', [
            'phone' => $phone,
            'message' => $message,
        ]);

        return [
            'success' => false,
            'status' => 'skipped',
            'message' => $message,
        ];
    }

    protected function finalizeResult(?string $phone, string $message, array $result, array $context = []): array
    {
        $result['sender_meta'] = array_intersect_key($context['meta'] ?? [], array_flip(['whatsapp_account', 'whatsapp_instance_id']));
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
                'channel' => 'whatsapp',
                'recipient_type' => $context['recipient_type'] ?? 'direct',
                'user_id' => $context['user_id'] ?? null,
                'admin_id' => $context['admin_id'] ?? auth('admin')->id(),
                'notification_id' => $context['notification_id'] ?? null,
                'recipient_name' => $context['recipient_name'] ?? null,
                'recipient_phone' => $result['to'] ?? $phone,
                'title' => $context['title'] ?? null,
                'message' => $context['message'] ?? $message,
                'provider' => app(\App\Services\WhatsApp\WhatsAppMessagingService::class)->driver(),
                'status' => $result['status'] ?? 'failed',
                'response' => $this->stringifyResponse($result['response'] ?? $result['error'] ?? $result['message'] ?? $result),
                'meta' => $this->buildMeta($phone, $result, $context),
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Unable to persist WhatsApp communication log', [
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
