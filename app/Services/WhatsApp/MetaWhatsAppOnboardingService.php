<?php

namespace App\Services\WhatsApp;

use App\Support\CommunicationSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MetaWhatsAppOnboardingService
{
    public function complete(string $code, string $wabaId, ?string $phoneNumberId, ?string $pin = null): array
    {
        $missing = $this->missingSetupKeys();
        if ($missing !== []) {
            return $this->failure('إعدادات Embedded Signup غير مكتملة: ' . implode(', ', $missing));
        }

        $tokenResult = $this->exchangeCode($code);
        if (!($tokenResult['success'] ?? false)) {
            return $tokenResult;
        }

        $accessToken = (string) data_get($tokenResult, 'response.access_token', '');
        $subscription = $this->subscribeApp($wabaId, $accessToken);
        if (!($subscription['success'] ?? false)) {
            return $this->failure(
                'تم تسجيل الدخول لكن تعذر اشتراك التطبيق في Webhook: ' . $this->responseError($subscription),
                $subscription
            );
        }

        $warnings = [];
        if ($phoneNumberId && $pin) {
            $registration = $this->registerPhone($phoneNumberId, $pin, $accessToken);
            if (!($registration['success'] ?? false)) {
                $warnings[] = 'تعذر تسجيل الرقم تلقائيًا، وقد يكون مسجلًا مسبقًا: ' . $this->responseError($registration);
            }
        }

        $settings = CommunicationSettings::whatsapp();
        $settings['driver'] = 'meta';
        $settings['meta'] = array_replace(
            (array) ($settings['meta'] ?? []),
            [
                'business_account_id' => $wabaId,
                'phone_number_id' => $phoneNumberId ?: (string) data_get($settings, 'meta.phone_number_id', ''),
                'access_token' => $accessToken,
            ]
        );
        CommunicationSettings::persistSection('whatsapp', $settings);

        return [
            'success' => true,
            'waba_id' => $wabaId,
            'phone_number_id' => $phoneNumberId,
            'warnings' => $warnings,
        ];
    }

    public function missingSetupKeys(): array
    {
        $missing = [];
        foreach ([
            'META_APP_ID' => config('services.whatsapp.app_id'),
            'META_CONFIGURATION_ID' => config('services.whatsapp.configuration_id'),
            'META_APP_SECRET' => config('services.whatsapp.app_secret'),
        ] as $key => $value) {
            if (trim((string) $value) === '') {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    private function exchangeCode(string $code): array
    {
        try {
            $response = Http::acceptJson()
                ->timeout($this->timeout())
                ->get($this->graphUrl('oauth/access_token'), [
                    'client_id' => (string) config('services.whatsapp.app_id'),
                    'client_secret' => (string) config('services.whatsapp.app_secret'),
                    'code' => $code,
                ]);

            $decoded = $response->json() ?: ['body' => $response->body()];

            return [
                'success' => $response->successful() && (string) data_get($decoded, 'access_token', '') !== '',
                'status_code' => $response->status(),
                'response' => $decoded,
            ];
        } catch (Throwable $exception) {
            Log::error('Meta embedded signup token exchange failed', ['message' => $exception->getMessage()]);

            return $this->failure($exception->getMessage());
        }
    }

    private function subscribeApp(string $wabaId, string $accessToken): array
    {
        return $this->post($wabaId . '/subscribed_apps', $accessToken, []);
    }

    private function registerPhone(string $phoneNumberId, string $pin, string $accessToken): array
    {
        return $this->post($phoneNumberId . '/register', $accessToken, [
            'messaging_product' => 'whatsapp',
            'pin' => $pin,
        ]);
    }

    private function post(string $path, string $accessToken, array $payload): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->asJson()
                ->timeout($this->timeout())
                ->post($this->graphUrl($path), $payload);

            return [
                'success' => $response->successful() && data_get($response->json(), 'success', true) !== false,
                'status_code' => $response->status(),
                'response' => $response->json() ?: ['body' => $response->body()],
            ];
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    private function graphUrl(string $path): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/%s',
            trim((string) config('services.whatsapp.graph_version', 'v23.0')),
            ltrim($path, '/')
        );
    }

    private function timeout(): int
    {
        return max(5, (int) config('services.whatsapp.timeout', 15));
    }

    private function responseError(array $result): string
    {
        return (string) (
            data_get($result, 'response.error.message')
            ?: data_get($result, 'response.message')
            ?: 'خطأ غير معروف من Meta'
        );
    }

    private function failure(string $message, array $context = []): array
    {
        return [
            'success' => false,
            'error' => $message,
            'response' => $context['response'] ?? ['message' => $message],
            'status_code' => $context['status_code'] ?? null,
        ];
    }
}
