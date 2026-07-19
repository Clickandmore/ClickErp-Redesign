<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class MetaWhatsAppTemplateService
{
    public function isConfigured(): bool
    {
        return $this->wabaId() !== '' && $this->accessToken() !== '';
    }

    public function listTemplates(bool $fresh = false): array
    {
        if (!$this->isConfigured()) {
            return $this->failure('WABA ID أو Access Token غير محفوظ.');
        }

        if ($fresh) {
            Cache::forget($this->cacheKey());
        }

        return Cache::remember($this->cacheKey(), now()->addMinutes(5), function (): array {
            try {
                $response = Http::withToken($this->accessToken())
                    ->acceptJson()
                    ->timeout($this->timeout())
                    ->get($this->endpoint(), [
                        'fields' => 'id,name,status,category,language,components,quality_score,rejected_reason',
                        'limit' => 100,
                    ]);

                $decoded = $response->json() ?: ['body' => $response->body()];

                return [
                    'success' => $response->successful(),
                    'status_code' => $response->status(),
                    'templates' => (array) data_get($decoded, 'data', []),
                    'response' => $decoded,
                ];
            } catch (Throwable $exception) {
                return $this->failure($exception->getMessage());
            }
        });
    }

    public function create(array $data): array
    {
        if (!$this->isConfigured()) {
            return $this->failure('أكمل ربط حساب Meta أولًا.');
        }

        $components = [];
        $header = trim((string) ($data['header'] ?? ''));
        if ($header !== '') {
            $components[] = ['type' => 'HEADER', 'format' => 'TEXT', 'text' => $header];
        }

        $body = [
            'type' => 'BODY',
            'text' => trim((string) $data['body']),
        ];
        $examples = array_values(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            (array) ($data['examples'] ?? [])
        ), static fn (string $value): bool => $value !== ''));
        if ($examples !== []) {
            $body['example'] = ['body_text' => [$examples]];
        }
        $components[] = $body;

        $footer = trim((string) ($data['footer'] ?? ''));
        if ($footer !== '') {
            $components[] = ['type' => 'FOOTER', 'text' => $footer];
        }

        $quickReplies = array_values(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            (array) ($data['quick_replies'] ?? [])
        ), static fn (string $value): bool => $value !== ''));
        if ($quickReplies !== []) {
            $components[] = [
                'type' => 'BUTTONS',
                'buttons' => array_map(
                    static fn (string $text): array => ['type' => 'QUICK_REPLY', 'text' => $text],
                    array_slice($quickReplies, 0, 3)
                ),
            ];
        }

        try {
            $response = Http::withToken($this->accessToken())
                ->acceptJson()
                ->asJson()
                ->timeout($this->timeout())
                ->post($this->endpoint(), [
                    'name' => (string) $data['name'],
                    'language' => (string) $data['language'],
                    'category' => (string) $data['category'],
                    'components' => $components,
                ]);

            $decoded = $response->json() ?: ['body' => $response->body()];
            $success = $response->successful() && (string) data_get($decoded, 'id', '') !== '';

            if ($success) {
                Cache::forget($this->cacheKey());
            }

            return [
                'success' => $success,
                'status_code' => $response->status(),
                'response' => $decoded,
            ];
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    public function approvedTemplate(string $name, string $language): ?array
    {
        $result = $this->listTemplates();

        return collect($result['templates'] ?? [])->first(function (array $template) use ($name, $language): bool {
            return (string) ($template['name'] ?? '') === $name
                && (string) ($template['language'] ?? '') === $language
                && strtoupper((string) ($template['status'] ?? '')) === 'APPROVED';
        });
    }

    public function bodyVariableCount(array $template): int
    {
        $body = collect((array) ($template['components'] ?? []))
            ->first(fn (array $component): bool => strtoupper((string) ($component['type'] ?? '')) === 'BODY');
        preg_match_all('/\{\{(\d+)\}\}/', (string) ($body['text'] ?? ''), $matches);

        return count(array_unique($matches[1] ?? []));
    }

    private function endpoint(): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/%s/message_templates',
            trim((string) config('services.whatsapp.graph_version', 'v23.0')),
            $this->wabaId()
        );
    }

    private function wabaId(): string
    {
        return trim((string) config('services.whatsapp.business_account_id', ''));
    }

    private function accessToken(): string
    {
        return trim((string) config('services.whatsapp.access_token', ''));
    }

    private function timeout(): int
    {
        return max(5, (int) config('services.whatsapp.timeout', 15));
    }

    private function cacheKey(): string
    {
        return 'meta_whatsapp_templates:' . $this->wabaId();
    }

    private function failure(string $message): array
    {
        return [
            'success' => false,
            'status_code' => null,
            'templates' => [],
            'response' => ['message' => $message],
        ];
    }
}
