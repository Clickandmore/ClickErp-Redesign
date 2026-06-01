<?php

namespace App\Services\Zoho;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SalesIqClient
{
    public const DEFAULT_SCOPES = [
        'SalesIQ.portals.READ',
        'SalesIQ.visitors.READ',
    ];

    public function authorizationUrl(array $scopes, ?string $state = null): string
    {
        $query = [
            'scope' => implode(',', $scopes),
            'client_id' => $this->config('client_id'),
            'response_type' => 'code',
            'access_type' => 'offline',
            'redirect_uri' => $this->config('redirect_uri'),
            'prompt' => 'consent',
        ];

        if ($state) {
            $query['state'] = $state;
        }

        return rtrim($this->config('accounts_url'), '/') . '/oauth/v2/org/auth?' . http_build_query($query);
    }

    public function exchangeCodeForTokens(string $code): array
    {
        $response = Http::asForm()
            ->timeout($this->timeout())
            ->post(rtrim($this->config('accounts_url'), '/') . '/oauth/v2/token', [
                'grant_type' => 'authorization_code',
                'client_id' => $this->config('client_id'),
                'client_secret' => $this->config('client_secret'),
                'redirect_uri' => $this->config('redirect_uri'),
                'scope' => implode(',', self::DEFAULT_SCOPES),
                'code' => $code,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Zoho token exchange failed: ' . $response->body());
        }

        return $response->json();
    }

    public function accessToken(): string
    {
        $response = Http::asForm()
            ->timeout($this->timeout())
            ->post(rtrim($this->config('accounts_url'), '/') . '/oauth/v2/token', [
                'grant_type' => 'refresh_token',
                'client_id' => $this->config('client_id'),
                'client_secret' => $this->config('client_secret'),
                'redirect_uri' => $this->config('redirect_uri'),
                'refresh_token' => $this->config('refresh_token'),
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Zoho access token refresh failed: ' . $response->body());
        }

        $token = $response->json('access_token');

        if (!$token) {
            throw new RuntimeException('Zoho access token response did not include access_token: ' . $response->body());
        }

        return $token;
    }

    public function visitors(array $query = []): array
    {
        $screenname = trim($this->config('screenname'), '/');

        $response = $this->request()
            ->get(rtrim($this->config('api_url'), '/') . "/api/v3/{$screenname}/visitors/", array_filter([
                'limit' => $query['limit'] ?? 200,
                'next_key' => $query['next_key'] ?? null,
                'prev_key' => $query['prev_key'] ?? null,
            ], static fn ($value) => $value !== null && $value !== ''));

        if ($response->failed()) {
            throw new RuntimeException('Zoho visitors request failed: ' . $response->body());
        }

        return $response->json();
    }

    private function request(): PendingRequest
    {
        return Http::withToken($this->accessToken())
            ->acceptJson()
            ->timeout($this->timeout());
    }

    private function config(string $key): string
    {
        $value = config("services.zoho_salesiq.{$key}");

        if ($value === null || $value === '') {
            throw new RuntimeException("Missing Zoho SalesIQ config value: {$key}");
        }

        if ($key === 'refresh_token' && str_contains((string) $value, 'نحتاج')) {
            throw new RuntimeException('Missing Zoho SalesIQ refresh_token. Run php artisan salesiq:authorize, approve Zoho access, then exchange the returned code.');
        }

        return (string) $value;
    }

    private function timeout(): int
    {
        return max(5, (int) config('services.zoho_salesiq.timeout', 20));
    }
}
