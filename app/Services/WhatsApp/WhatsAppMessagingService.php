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

        return in_array($driver, ['meta', 'ultramsg', 'chatwoot', 'clickchat'], true) ? $driver : 'meta';
    }

    public function providerLabel(): string
    {
        return match ($this->driver()) { 'clickchat' => 'Click Live Chat', 'chatwoot' => 'Widers / Chatwoot', 'ultramsg' => 'UltraMsg', default => 'Meta API' };
    }

    public function isConfigured(): bool
    {
        if ($this->driver() === 'clickchat') { return app(ClickChatWhatsAppService::class)->isConfigured(); }
        if ($this->driver() === 'chatwoot') { return app(ChatwootWhatsAppService::class)->missingConfigKeys() === []; }
        if ($this->driver() === 'ultramsg') {
            return $this->resolveSender() !== null;
        }

        return $this->metaWhatsAppService->isConfigured();
    }

    public function missingConfigKeys(): array
    {
        if ($this->driver() === 'clickchat') { return app(ClickChatWhatsAppService::class)->missingConfigKeys(); }
        if ($this->driver() === 'chatwoot') { return app(ChatwootWhatsAppService::class)->missingConfigKeys(); }
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
        if ($this->driver() === 'clickchat') {
            return array_values(array_map(static fn (array $channel): array => [
                'key' => (string) $channel['id'],
                'name' => $channel['type'] === 'whatsapp_qr' ? 'رقم QR' : 'رقم ميتا الرسمي',
                'phone_number' => $channel['name'],
                'instance_id' => '',
            ], array_filter(app(ClickChatWhatsAppService::class)->channels(),
                static fn (array $channel): bool => $channel['status'] === 'active')));
        }

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
        if ($this->driver() === 'clickchat') {
            $id = app(ClickChatWhatsAppService::class)->defaultChannelAccountId();

            return $id ? (string) $id : null;
        }

        if ($this->driver() !== 'ultramsg') {
            return null;
        }

        $sender = CommunicationSettings::findUltraMsgInstance();

        return $sender['key'] ?? null;
    }

    public function resolveSender(?string $senderKey = null): ?array
    {
        if ($this->driver() === 'clickchat') {
            // The platform checks that the number is active; here we only need its id.
            $key = trim((string) ($senderKey ?: $this->defaultSenderKey()));

            return ctype_digit($key) && (int) $key > 0
                ? ['key' => $key, 'name' => '', 'phone_number' => '', 'instance_id' => '', 'channel_account_id' => (int) $key]
                : null;
        }

        if ($this->driver() !== 'ultramsg') {
            return null;
        }

        return CommunicationSettings::findUltraMsgInstance($senderKey);
    }

    public function sendText(string $to, string $message, array $context = []): array
    {
        if ($this->driver() === 'clickchat') { return app(ClickChatWhatsAppService::class)->sendText($to, $message, $context); }
        $optOut = app(WhatsAppOptOutService::class);
        if ($optOut->blocks($to, $context)) {
            return \App\Support\OutboundMessageRecorder::attempt('whatsapp', $to, $message, $this->driver(),
                fn () => $optOut->blockedResult(), $context);
        }
        if ($this->driver() === 'chatwoot') { return app(ChatwootWhatsAppService::class)->sendText($to, $message, $context); }
        if ($this->driver() === 'ultramsg') {
            return \App\Support\OutboundMessageRecorder::attempt('whatsapp', $to, $message, 'ultramsg',
                fn () => $this->sendViaUltraMsg($to, $message, $context), $context + ['sender_key' => $this->resolveSender($context['sender_key'] ?? null)['key'] ?? null]);
        }

        return $this->metaWhatsAppService->sendText($to, $message, $context);
    }

    /**
     * Backup channel used by Click Live Chat when it cannot deliver: an UltraMsg number or SMS.
     * Recorded in the message log under the backup provider.
     */
    public function sendViaFallback(string $provider, string $to, string $message, array $context = []): array
    {
        $context = ['sender_key' => null, 'channel_account_id' => null, 'platform_conversation_id' => null] + $context;
        if ($provider === 'ultramsg') {
            $sender = CommunicationSettings::findUltraMsgInstance();

            return \App\Support\OutboundMessageRecorder::attempt('whatsapp', $to, $message, 'ultramsg',
                fn () => $this->sendViaUltraMsg($to, $message, $context, $sender), $context + ['sender_key' => $sender['key'] ?? null]);
        }
        if ($provider === 'sms') {
            $verification = ($context['purpose'] ?? '') === 'verification' || !empty($context['verification']);
            $raw = \App\Support\SmsGateway::send($to, $message, null, $verification ? 'verification' : null, $context);
            $success = $raw !== null && \App\Support\OutboundMessageRecorder::smsAccepted($raw, $to);

            return ['success' => $success, 'to' => $to, 'error' => $success ? null : 'sms_failed',
                'response' => ['message' => $success ? 'أُرسلت عبر SMS.' : 'تعذر الإرسال عبر SMS أيضًا.']];
        }

        return ['success' => false, 'error' => 'no_fallback', 'response' => ['message' => 'لا توجد قناة احتياطية.']];
    }

    private function sendViaUltraMsg(string $to, string $message, array $context = [], ?array $sender = null): array
    {
        $sender ??= $this->resolveSender((string) ($context['sender_key'] ?? ''));
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
