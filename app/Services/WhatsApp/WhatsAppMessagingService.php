<?php

namespace App\Services\WhatsApp;

use App\Support\CommunicationSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppMessagingService
{
    public function __construct(private readonly MetaWhatsAppService $metaWhatsAppService)
    {
    }

    public function driver(): string
    {
        $driver = trim((string) config('services.whatsapp.driver', 'meta'));

        return in_array($driver, ['meta', 'ultramsg'], true) ? $driver : 'meta';
    }

    public function providerLabel(): string
    {
        return $this->driver() === 'ultramsg' ? 'UltraMsg' : 'Meta API';
    }

    public function isConfigured(): bool
    {
        if ($this->driver() === 'ultramsg') {
            return $this->resolveSender() !== null;
        }

        return $this->metaWhatsAppService->isConfigured();
    }

    public function missingConfigKeys(): array
    {
        if ($this->driver() === 'ultramsg') {
            $sender = $this->resolveSender();

            if ($sender === null) {
                return ['ULTRAMSG_INSTANCES'];
            }

            $missing = [];
            if ((string) ($sender['instance_id'] ?? '') === '') {
                $missing[] = 'ULTRAMSG_INSTANCE_ID';
            }
            if ((string) ($sender['token'] ?? '') === '') {
                $missing[] = 'ULTRAMSG_TOKEN';
            }

            return $missing;
        }

        return $this->metaWhatsAppService->missingConfigKeys();
    }

    public function availableSenders(): array
    {
        if ($this->driver() !== 'ultramsg') {
            return [];
        }

        return array_map(function (array $instance): array {
            return [
                'key' => (string) ($instance['key'] ?? ''),
                'name' => (string) ($instance['name'] ?? ''),
                'phone_number' => (string) ($instance['phone_number'] ?? ''),
                'instance_id' => (string) ($instance['instance_id'] ?? ''),
            ];
        }, CommunicationSettings::activeUltraMsgInstances());
    }

    public function defaultSenderKey(): ?string
    {
        if ($this->driver() !== 'ultramsg') {
            return null;
        }

        $sender = CommunicationSettings::findUltraMsgInstance();

        return $sender['key'] ?? null;
    }

    public function resolveSender(?string $senderKey = null): ?array
    {
        if ($this->driver() !== 'ultramsg') {
            return null;
        }

        return CommunicationSettings::findUltraMsgInstance($senderKey);
    }

    public function sendText(string $to, string $message, array $context = []): array
    {
        if ($this->driver() === 'ultramsg') {
            return $this->sendViaUltraMsg($to, $message, $context);
        }

        return $this->metaWhatsAppService->sendText($to, $message, $context);
    }

    private function sendViaUltraMsg(string $to, string $message, array $context = []): array
    {
        $sender = $this->resolveSender((string) ($context['sender_key'] ?? ''));
        $normalizedPhone = $this->metaWhatsAppService->normalizePhone($to);

        if ($normalizedPhone === null) {
            return [
                'success' => false,
                'to' => $to,
                'normalized_to' => null,
                'status_code' => null,
                'error' => 'invalid_phone',
                'response' => ['message' => 'رقم الهاتف غير صالح للإرسال.'],
            ];
        }

        if ($sender === null) {
            return [
                'success' => false,
                'to' => $to,
                'normalized_to' => $normalizedPhone,
                'status_code' => null,
                'error' => 'not_configured',
                'response' => ['missing' => $this->missingConfigKeys()],
            ];
        }

        $endpoint = sprintf(
            '%s/%s/messages/chat',
            rtrim((string) config('services.whatsapp.ultramsg.api_url', 'https://api.ultramsg.com'), '/'),
            trim((string) $sender['instance_id'])
        );

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(max(5, (int) config('services.whatsapp.timeout', 15)))
                ->post($endpoint, [
                    'token' => (string) $sender['token'],
                    'to' => $normalizedPhone,
                    'body' => $message,
                ]);

            $decoded = $response->json();
            $success = $this->isUltraMsgSuccess($response->successful(), $decoded);

            if (!$success) {
                Log::warning('UltraMsg send failed', [
                    'to' => $normalizedPhone,
                    'status' => $response->status(),
                    'response' => $decoded ?: $response->body(),
                    'context' => $context,
                    'sender_key' => $sender['key'] ?? null,
                ]);
            }

            return [
                'success' => $success,
                'to' => $to,
                'normalized_to' => $normalizedPhone,
                'status_code' => $response->status(),
                'error' => $success ? null : 'api_error',
                'response' => $decoded ?: ['body' => $response->body()],
                'sender_key' => $sender['key'] ?? null,
            ];
        } catch (Throwable $exception) {
            Log::error('UltraMsg exception', [
                'to' => $normalizedPhone,
                'message' => $exception->getMessage(),
                'context' => $context,
                'sender_key' => $sender['key'] ?? null,
            ]);

            return [
                'success' => false,
                'to' => $to,
                'normalized_to' => $normalizedPhone,
                'status_code' => null,
                'error' => 'exception',
                'response' => ['message' => $exception->getMessage()],
                'sender_key' => $sender['key'] ?? null,
            ];
        }
    }

    private function isUltraMsgSuccess(bool $httpSuccessful, $decoded): bool
    {
        if (!$httpSuccessful) {
            return false;
        }

        if (!is_array($decoded)) {
            return true;
        }

        if (array_key_exists('sent', $decoded)) {
            $sent = filter_var($decoded['sent'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($sent !== null) {
                return $sent;
            }
        }

        if (isset($decoded['error']) && $decoded['error']) {
            return false;
        }

        return true;
    }
}
