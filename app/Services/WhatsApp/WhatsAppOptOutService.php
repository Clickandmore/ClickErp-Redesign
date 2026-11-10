<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppOptOut;
use App\Support\OutboundMessageRecorder;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * A customer who replies with a stop word (the whole message, e.g. "إيقاف")
 * stops receiving automated WhatsApp messages; a start word turns them back on.
 * Login codes and replies typed by staff in the inbox are never blocked.
 */
class WhatsAppOptOutService
{
    private const EXEMPT_SOURCES = ['whatsapp_inbox', 'whatsapp_inbox_template'];

    public function enabled(): bool
    {
        return filter_var(config('services.whatsapp.opt_out.enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    /** Record a stop or start request from an incoming message. Returns 'stop', 'start' or null. */
    public function handleInbound(string $phone, ?string $body, string $source): ?string
    {
        $phone = $this->digits($phone);
        if (!$this->enabled() || $phone === '' || !$this->tableReady()) {
            return null;
        }

        $text = $this->normalize((string) $body);
        if ($text === '') {
            return null;
        }

        if (in_array($text, $this->keywords('stop_keywords'), true)) {
            WhatsAppOptOut::query()->updateOrCreate(['phone' => $phone],
                ['keyword' => mb_substr($text, 0, 60), 'source' => $source, 'opted_out_at' => now()]);
            return 'stop';
        }
        if (in_array($text, $this->keywords('start_keywords'), true)) {
            WhatsAppOptOut::query()->where('phone', $phone)->delete();
            return 'start';
        }

        return null;
    }

    public function isOptedOut(string $phone): bool
    {
        $digits = app(MetaWhatsAppService::class)->normalizePhone($phone) ?? $this->digits($phone);

        return $digits !== '' && $this->tableReady()
            && WhatsAppOptOut::query()->where('phone', $digits)->exists();
    }

    public function blocks(string $to, array $context): bool
    {
        if (!$this->enabled() || !empty($context['ignore_opt_out'])
            || in_array($context['source'] ?? '', self::EXEMPT_SOURCES, true)
            || ($context['purpose'] ?? '') === 'verification' || !empty($context['verification'])) {
            return false;
        }

        return $this->isOptedOut($to);
    }

    public function blockedResult(): array
    {
        return ['success' => false, 'error' => 'opted_out', 'status_code' => null,
            'response' => ['message' => 'لم تُرسل: العميل طلب إيقاف رسائل واتساب.']];
    }

    private function keywords(string $key): array
    {
        $raw = (string) config('services.whatsapp.opt_out.' . $key, '');

        return array_values(array_filter(array_map(fn ($word) => $this->normalize($word), preg_split('/[,،\n]+/u', $raw) ?: [])));
    }

    /** Lower case, one space, no punctuation, and أ/إ/آ read as ا so spelling variants match. */
    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = str_replace(['أ', 'إ', 'آ', 'ـ'], ['ا', 'ا', 'ا', ''], $text);
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', '', $text) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    private function tableReady(): bool
    {
        try {
            return Schema::hasTable('whatsapp_opt_outs');
        } catch (Throwable) {
            return false;
        }
    }

    private function digits(string $value): string
    {
        return (string) preg_replace('/\D+/', '', $value);
    }
}
