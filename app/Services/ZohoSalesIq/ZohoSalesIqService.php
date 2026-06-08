<?php

namespace App\Services\ZohoSalesIq;

use App\Models\Branch;
use App\Support\ZohoSalesIqSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ZohoSalesIqService
{
    private ?Branch $branch = null;
    private ?array $settings = null;

    public function forBranch(?Branch $branch): self
    {
        $clone = clone $this;
        $clone->branch = $branch;
        $clone->settings = null;

        return $clone;
    }

    public function isConfigured(): bool
    {
        return $this->clientId() !== ''
            && $this->clientSecret() !== ''
            && $this->refreshToken() !== ''
            && $this->screenName() !== '';
    }

    public function getAccessToken(): string
    {
        return $this->accessToken();
    }

    public function missingConfigKeys(): array
    {
        return ZohoSalesIqSettings::missingKeys($this->branch);
    }

    public function connectionInfo(): array
    {
        $settings = $this->settings();

        return [
            'branch' => $this->branch,
            'source' => $this->branch ? $this->branch->name : 'Global settings',
            'salesiq_base_url' => $settings['salesiq_base_url'] ?? '',
            'accounts_base_url' => $settings['accounts_base_url'] ?? '',
            'screen_name' => $settings['screen_name'] ?? '',
            'client_id' => $settings['client_id'] ?? '',
            'widget_url' => $settings['widget_url'] ?? '',
        ];
    }

    public function visitors(array $query = []): array
    {
        // Remove unsupported next_key parameter for v2/v3
        $query = array_diff_key($query, array_flip(['next_key']));
        
        // Try v2 API first
        $result = $this->get(
            sprintf('api/v2/%s/visitors', rawurlencode($this->screenName())),
            $query,
            ['data.visitors', 'visitors', 'data']
        );

        // If invalid scope, try v1
        if (!$result['ok'] && (int)($result['status_code'] ?? 0) === 400 && !str_contains($result['error'] ?? '', 'not enabled')) {
            return $this->get(
                sprintf('api/v1/%s/visitors', rawurlencode($this->screenName())),
                $query,
                ['data.visitors', 'visitors', 'data']
            );
        }

        return $result;
    }

    public function conversations(array $query = []): array
    {
        $result = $this->get(
            sprintf('api/v2/%s/conversations', rawurlencode($this->screenName())),
            $query,
            ['data.conversations', 'conversations', 'data', 'data.chats', 'chats']
        );

        if (!$result['ok'] && in_array((int) ($result['status_code'] ?? 0), [404, 405], true)) {
            return $this->get(
                sprintf('api/v1/%s/chats', rawurlencode($this->screenName())),
                $query,
                ['data.chats', 'chats', 'data']
            );
        }

        return $result;
    }

    public function conversationDetails(string $conversationId): array
    {
        $result = $this->get(
            sprintf(
                'api/v2/%s/conversations/%s',
                rawurlencode($this->screenName()),
                rawurlencode($conversationId)
            ),
            [],
            []
        );

        if (!$result['ok'] && in_array((int) ($result['status_code'] ?? 0), [404, 405], true)) {
            return $this->get(
                sprintf(
                    'api/v1/%s/chats/%s',
                    rawurlencode($this->screenName()),
                    rawurlencode($conversationId)
                ),
                [],
                []
            );
        }

        return $result;
    }

    public function conversationMessages(string $conversationId, array $query = []): array
    {
        return $this->get(
            sprintf(
                'api/v2/%s/conversations/%s/messages',
                rawurlencode($this->screenName()),
                rawurlencode($conversationId)
            ),
            $query,
            ['data.messages', 'messages', 'data']
        );
    }

    private function get(string $path, array $query, array $listKeys): array
    {
        if (!$this->isConfigured()) {
            return [
                'ok' => false,
                'status_code' => null,
                'error' => 'not_configured',
                'missing' => $this->missingConfigKeys(),
                'items' => [],
                'payload' => [],
                'meta' => [],
            ];
        }

        try {
            $token = $this->accessToken();
            $cleanQuery = $this->cleanQuery($query);
            $endpoint = $this->endpoint($path);
            
            $response = Http::withHeaders([
                'Authorization' => 'Zoho-oauthtoken ' . $token,
            ])
                ->acceptJson()
                ->timeout($this->timeoutSeconds())
                ->get($endpoint, $cleanQuery);

            $payload = $response->json();
            $payload = is_array($payload) ? $payload : [];
            $ok = $response->successful() && !$this->payloadHasError($payload);

            if (!$ok) {
                Log::warning('Zoho SalesIQ request failed', [
                    'path' => $path,
                    'endpoint' => $endpoint,
                    'query' => $cleanQuery,
                    'status' => $response->status(),
                    'response' => $payload,
                ]);
            }

            return [
                'ok' => $ok,
                'status_code' => $response->status(),
                'error' => $ok ? null : $this->extractErrorMessage($payload),
                'missing' => [],
                'items' => $ok ? $this->extractItems($payload, $listKeys) : [],
                'payload' => $payload,
                'meta' => $this->extractMeta($payload),
            ];
        } catch (Throwable $exception) {
            Log::error('Zoho SalesIQ request exception', [
                'path' => $path,
                'query' => $this->cleanQuery($query),
                'message' => $exception->getMessage(),
            ]);

            return [
                'ok' => false,
                'status_code' => null,
                'error' => $exception->getMessage(),
                'missing' => [],
                'items' => [],
                'payload' => [],
                'meta' => [],
            ];
        }
    }

    private function accessToken(): string
    {
        $cacheKey = 'zoho_salesiq_access_token_' . md5(implode('|', [
            $this->branch?->id ?: 'global',
            $this->clientId(),
            $this->screenName(),
            $this->refreshToken(),
        ]));

        return Cache::remember($cacheKey, now()->addMinutes(50), function () {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout($this->timeoutSeconds())
                ->post($this->accountsBaseUrl() . '/oauth/v2/token', [
                    'refresh_token' => $this->refreshToken(),
                    'client_id' => $this->clientId(),
                    'client_secret' => $this->clientSecret(),
                    'grant_type' => 'refresh_token',
                ]);

            $payload = $response->json();
            $payload = is_array($payload) ? $payload : [];
            $token = trim((string) data_get($payload, 'access_token', ''));

            if (!$response->successful() || $token === '') {
                throw new \RuntimeException($this->extractErrorMessage($payload) ?: 'Unable to refresh Zoho SalesIQ access token.');
            }

            return $token;
        });
    }

    private function extractItems(array $payload, array $preferredKeys): array
    {
        foreach ($preferredKeys as $key) {
            $value = data_get($payload, $key);

            if ($this->isListArray($value)) {
                return $value;
            }
        }

        foreach (['data.list', 'list', 'results', 'items'] as $key) {
            $value = data_get($payload, $key);

            if ($this->isListArray($value)) {
                return $value;
            }
        }

        return [];
    }

    private function extractMeta(array $payload): array
    {
        $meta = [];

        foreach (['next_key', 'prev_key', 'more', 'has_more', 'count', 'limit'] as $key) {
            $value = data_get($payload, $key, data_get($payload, 'data.' . $key));

            if ($value !== null && $value !== '') {
                $meta[$key] = $value;
            }
        }

        return $meta;
    }

    private function payloadHasError(array $payload): bool
    {
        return data_get($payload, 'error') !== null
            || data_get($payload, 'error_code') !== null
            || data_get($payload, 'data.error') !== null
            || data_get($payload, 'code') === 'ERROR';
    }

    private function extractErrorMessage(array $payload): string
    {
        foreach (['error.message', 'message', 'error_description', 'error', 'error_message', 'data.message', 'data.error'] as $key) {
            $value = data_get($payload, $key);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return (string) $value;
            }
        }

        return 'Zoho SalesIQ request failed.';
    }

    private function cleanQuery(array $query): array
    {
        return array_filter($query, static fn ($value) => $value !== null && $value !== '');
    }

    private function isListArray($value): bool
    {
        return is_array($value) && array_is_list($value);
    }

    private function endpoint(string $path): string
    {
        return $this->salesIqBaseUrl() . '/' . ltrim($path, '/');
    }

    private function salesIqBaseUrl(): string
    {
        return rtrim((string) ($this->settings()['salesiq_base_url'] ?? 'https://salesiq.zoho.sa'), '/');
    }

    private function accountsBaseUrl(): string
    {
        return rtrim((string) ($this->settings()['accounts_base_url'] ?? 'https://accounts.zoho.sa'), '/');
    }

    private function clientId(): string
    {
        return trim((string) ($this->settings()['client_id'] ?? ''));
    }

    private function clientSecret(): string
    {
        return trim((string) ($this->settings()['client_secret'] ?? ''));
    }

    private function refreshToken(): string
    {
        return trim((string) ($this->settings()['refresh_token'] ?? ''));
    }

    private function screenName(): string
    {
        return trim((string) ($this->settings()['screen_name'] ?? ''));
    }

    private function timeoutSeconds(): int
    {
        return max(5, (int) ($this->settings()['timeout'] ?? 20));
    }

    private function settings(): array
    {
        return $this->settings ??= ZohoSalesIqSettings::forBranch($this->branch);
    }
}
