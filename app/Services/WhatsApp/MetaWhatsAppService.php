<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MetaWhatsAppService
{
    public function isConfigured(): bool
    {
        return $this->phoneNumberId() !== '' && $this->accessToken() !== '';
    }

    public function missingConfigKeys(): array
    {
        $missing = [];

        if ($this->phoneNumberId() === '') {
            $missing[] = 'WHATSAPP_PHONE_NUMBER_ID';
        }

        if ($this->accessToken() === '') {
            $missing[] = 'WHATSAPP_ACCESS_TOKEN';
        }

        if ($this->webhookVerifyToken() === '') {
            $missing[] = 'WHATSAPP_WEBHOOK_VERIFY_TOKEN';
        }

        return $missing;
    }

    public function webhookVerifyToken(): string
    {
        return trim((string) config('services.whatsapp.webhook_verify_token', ''));
    }

    public function sendText(string $to, string $message, array $context = []): array
    {
        $normalizedPhone = $this->normalizePhone($to);

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

        if (!$this->isConfigured()) {
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
            'https://graph.facebook.com/%s/%s/messages',
            $this->graphVersion(),
            $this->phoneNumberId()
        );

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $normalizedPhone,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $message,
            ],
        ];

        try {
            $response = Http::withToken($this->accessToken())
                ->acceptJson()
                ->asJson()
                ->timeout($this->timeoutSeconds())
                ->post($endpoint, $payload);

            $decoded = $response->json();
            $success = $response->successful() && $this->responseHasMessageId($decoded);

            if (!$success) {
                Log::warning('Meta WhatsApp send failed', [
                    'to' => $normalizedPhone,
                    'status' => $response->status(),
                    'response' => $decoded,
                    'context' => $context,
                ]);
            }

            return [
                'success' => $success,
                'to' => $to,
                'normalized_to' => $normalizedPhone,
                'status_code' => $response->status(),
                'error' => $success ? null : 'api_error',
                'response' => $decoded,
            ];
        } catch (Throwable $exception) {
            Log::error('Meta WhatsApp exception', [
                'to' => $normalizedPhone,
                'message' => $exception->getMessage(),
                'context' => $context,
            ]);

            return [
                'success' => false,
                'to' => $to,
                'normalized_to' => $normalizedPhone,
                'status_code' => null,
                'error' => 'exception',
                'response' => ['message' => $exception->getMessage()],
            ];
        }
    }

    public function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        $countryCode = $this->defaultCountryCode();

        if ($countryCode !== '' && str_starts_with($digits, $countryCode)) {
            return $digits;
        }

        // International number with a non-default country code; keep as-is.
        if (!str_starts_with($digits, '0') && strlen($digits) >= 11) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            $digits = ltrim($digits, '0');
        }

        if ($countryCode !== '') {
            return $countryCode . $digits;
        }

        return $digits;
    }

    private function responseHasMessageId($response): bool
    {
        if (!is_array($response)) {
            return false;
        }

        $messageId = Arr::get($response, 'messages.0.id');

        return is_string($messageId) && $messageId !== '';
    }

    private function graphVersion(): string
    {
        return trim((string) config('services.whatsapp.graph_version', 'v23.0'));
    }

    private function phoneNumberId(): string
    {
        return trim((string) config('services.whatsapp.phone_number_id', ''));
    }

    private function accessToken(): string
    {
        return trim((string) config('services.whatsapp.access_token', ''));
    }

    private function defaultCountryCode(): string
    {
        return trim((string) config('services.whatsapp.default_country_code', '966'));
    }

    private function timeoutSeconds(): int
    {
        return max(5, (int) config('services.whatsapp.timeout', 15));
    }
}
