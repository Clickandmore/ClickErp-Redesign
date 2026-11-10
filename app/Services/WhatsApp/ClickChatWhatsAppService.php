<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppSendRetry;
use App\Support\OutboundMessageRecorder;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * WhatsApp through the Click Live Chat platform public API (/api/v1).
 * Works the same for official Meta numbers and QR numbers linked to the platform.
 */
class ClickChatWhatsAppService
{
    private const CHANNELS_CACHE_KEY = 'clickchat:channels:';

    private const TEMPLATES_CACHE_KEY = 'clickchat:templates:';

    /** The platform allows 60 sends a minute per key; stay a little under it. */
    private const SENDS_PER_MINUTE = 55;

    /** Errors where the platform received nothing or refused before sending, so a retry cannot duplicate. */
    public const RETRYABLE_ERRORS = ['rate_limited', 'server_error'];

    /** Errors where the customer surely got nothing from the platform, so another channel may be used. */
    private const FALLBACK_ERRORS = ['auth_error', 'forbidden', 'window_closed', 'no_active_number', 'rate_limited', 'server_error'];

    public function __construct(private readonly ClickChatMessageStore $store)
    {
    }

    public function missingConfigKeys(): array
    {
        return array_values(array_filter(['base_url', 'api_token'],
            fn (string $key) => trim((string) config('services.whatsapp.clickchat.' . $key, '')) === ''));
    }

    public function isConfigured(): bool
    {
        return $this->missingConfigKeys() === [];
    }

    public function defaultChannelAccountId(): ?int
    {
        $id = (int) config('services.whatsapp.clickchat.default_channel_account_id', 0);

        return $id > 0 ? $id : null;
    }

    public function webhookSecret(): string
    {
        return trim((string) config('services.whatsapp.clickchat.webhook_secret', ''));
    }

    public function fallbackProvider(): string
    {
        $fallback = (string) config('services.whatsapp.clickchat.fallback', 'none');

        return in_array($fallback, ['ultramsg', 'sms'], true) ? $fallback : 'none';
    }

    /**
     * Check a "t=...,v1=..." signature: HMAC-SHA256 of "t.<raw body>" with the webhook secret.
     */
    public function verifySignature(string $rawBody, ?string $header, int $toleranceSeconds = 300): bool
    {
        $secret = $this->webhookSecret();
        if ($secret === '' || !$header) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $pair) {
            [$key, $value] = array_pad(explode('=', trim($pair), 2), 2, '');
            $parts[$key] = $value;
        }
        $timestamp = $parts['t'] ?? '';
        $signature = $parts['v1'] ?? '';
        if (!ctype_digit($timestamp) || $signature === '' || abs(time() - (int) $timestamp) > $toleranceSeconds) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret), $signature);
    }

    /** "Test connection": lists the WhatsApp numbers linked to the platform. */
    public function connectionStatus(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'response' => ['message' => 'أدخل رابط المنصة ومفتاح API ثم احفظ قبل الاختبار.']];
        }

        try {
            $response = $this->client()->get($this->url('channels'));
        } catch (Throwable $e) {
            return $this->transportFailure();
        }

        if (!$response->successful()) {
            return ['success' => false, 'status_code' => $response->status(),
                'error' => in_array($response->status(), [401, 403], true) ? 'auth_error' : 'api_error',
                'response' => ['message' => $this->httpErrorMessage($response, 'conversations:read')]];
        }

        $channels = $this->mapChannels((array) $response->json('data', []));
        Cache::put($this->cacheKey(self::CHANNELS_CACHE_KEY), $channels, now()->addMinutes(10));

        return ['success' => true, 'channels' => $channels];
    }

    /** Linked numbers, cached for ten minutes. Empty when the platform cannot be reached. */
    public function channels(): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $cached = Cache::get($this->cacheKey(self::CHANNELS_CACHE_KEY));
        if (is_array($cached)) {
            return $cached;
        }

        try {
            $response = $this->client()->get($this->url('channels'));
        } catch (Throwable) {
            return [];
        }
        if (!$response->successful()) {
            return [];
        }

        // Only a good answer is cached, so a short outage does not hide the numbers for ten minutes.
        $channels = $this->mapChannels((array) $response->json('data', []));
        Cache::put($this->cacheKey(self::CHANNELS_CACHE_KEY), $channels, now()->addMinutes(10));

        return $channels;
    }

    /**
     * Approved templates that can be filled from body variables only, cached for ten minutes.
     * Empty when the platform cannot be reached.
     */
    public function templates(bool $fresh = false, ?int $channelId = null): array
    {
        if (!$this->isConfigured()) {
            return [];
        }
        $channelId ??= $this->channelAccountId([]);
        $key = $this->cacheKey(self::TEMPLATES_CACHE_KEY) . ':' . ($channelId ?? 'all');
        if ($fresh) {
            Cache::forget($key);
        }
        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }

        try {
            $response = $this->client()->get($this->url('templates'), array_filter(['channel_account_id' => $channelId]));
        } catch (Throwable) {
            return [];
        }
        if (!$response->successful()) {
            return [];
        }

        $templates = collect((array) $response->json('data', []))
            ->filter(fn ($row) => is_array($row) && !empty($row['name']) && ($row['sendable_by_api'] ?? true))
            ->unique(fn ($row) => $row['name'] . '|' . ($row['language'] ?? ''))
            ->map(fn ($row) => [
                'name' => (string) $row['name'],
                'language' => (string) ($row['language'] ?? 'ar'),
                'category' => (string) ($row['category'] ?? ''),
                'body' => (string) ($row['body'] ?? ''),
                'variables' => (int) ($row['variables'] ?? 0),
            ])->values()->all();
        Cache::put($key, $templates, now()->addMinutes(10));

        return $templates;
    }

    /**
     * Fill a template chosen as "name|language" with values keyed by variable number.
     * Checked against the templates of the number that will send it: templates
     * belong to one Meta business account, and QR numbers have none.
     *
     * @return array{message: string, clickchat_template: array}
     */
    public function prepareTemplate(string $key, array $values, ?int $channelId = null): array
    {
        foreach ($this->templates(false, $channelId) as $template) {
            if ($template['name'] . '|' . $template['language'] !== $key) {
                continue;
            }
            if (strtoupper($template['category']) === 'MARKETING' && !config('services.whatsapp.allow_advertising', false)) {
                throw new RuntimeException('الإرسال الدعائي غير مفعّل.');
            }
            preg_match_all('/\{\{\s*(\d+)\s*\}\}/', $template['body'], $matches);
            $numbers = array_values(array_unique(array_map('intval', $matches[1])));
            sort($numbers);
            $vars = [];
            foreach ($numbers as $number) {
                $value = trim((string) ($values[$number] ?? ''));
                if ($value === '') {
                    throw new RuntimeException('أكمل متغير القالب: ' . $number);
                }
                $vars[$number] = $value;
            }
            $rendered = preg_replace_callback('/\{\{\s*(\d+)\s*\}\}/', fn ($m) => $vars[(int) $m[1]] ?? $m[0], $template['body']);

            return ['message' => $rendered, 'clickchat_template' => [
                'name' => $template['name'], 'language' => $template['language'], 'vars' => array_values($vars),
            ]];
        }

        throw new RuntimeException('القالب غير متاح أو غير معتمد على الرقم المرسِل المختار. أرقام QR لا تدعم قوالب ميتا؛ اختر رقم ميتا الذي يملك القالب.');
    }

    /** All local platform states for badges. Failures must not masquerade as missing templates. */
    public function templateStatusSnapshot(?int $channelId = null): array
    {
        if (!$this->isConfigured()) return ['success' => false, 'error' => 'not_configured'];
        $channelId ??= $this->channelAccountId([]);
        if (!$channelId) return ['success' => false, 'error' => 'sender_required'];
        try {
            $response = $this->client()->get($this->url('templates'), ['channel_account_id' => $channelId, 'include_status' => 1]);
            if (!$response->successful()) {
                $error = match ($response->status()) {
                    401 => 'auth_error', 403 => 'forbidden', 404 => 'api_missing',
                    422 => 'invalid_sender', 429 => 'rate_limited', default => 'unavailable',
                };
                return ['success' => false, 'error' => $error, 'http_status' => $response->status()];
            }
            if ($response->json('meta.includes_all_statuses') !== true || !is_array($response->json('data'))) {
                return ['success' => false, 'error' => 'api_outdated'];
            }
            return ['success' => true, 'channel_account_id' => $channelId, 'templates' => $response->json('data')];
        } catch (Throwable) {
            return ['success' => false, 'error' => 'unavailable'];
        }
    }

    /** Submit a utility template through our platform; never through ERP Meta credentials. */
    public function create(array $data): array
    {
        $sender = $this->channelAccountId([]);
        if (!$sender) return ['success' => false, 'response' => ['message' => 'اختر رقم ميتا الافتراضي في إعدادات Click Live Chat.']];
        if (!empty($data['header']) || !empty($data['footer']) || !empty($data['quick_replies']) || ($data['category'] ?? 'UTILITY') !== 'UTILITY') {
            return ['success' => false, 'response' => ['message' => 'قوالب التحديث عبر اللايف شات تدعم نص الخدمة ومتغيراته فقط. أنشئ القوالب المتقدمة من اللايف شات.']];
        }
        try {
            $response = $this->client()->asJson()->post($this->url('templates'), [
                'channel_account_id' => $sender, 'name' => $data['name'], 'language' => $data['language'],
                'body' => $data['body'], 'examples' => array_values($data['examples'] ?? []),
            ]);
            return ['success' => $response->successful(), 'response' => $response->successful()
                ? $response->json('data', []) : ['message' => $response->json('message', 'تعذر تقديم القالب؛ راجع اللايف شات قبل إعادة المحاولة.')]];
        } catch (Throwable) {
            return ['success' => false, 'response' => ['message' => 'تعذر تأكيد تقديم القالب؛ راجع القوالب في اللايف شات قبل إعادة المحاولة.']];
        }
    }

    /** Current state of one platform message, or null when it cannot be read. */
    public function message(int $platformMessageId): ?array
    {
        try {
            $response = $this->client()->get($this->url('messages/' . $platformMessageId));
        } catch (Throwable) {
            return null;
        }

        return $response->successful() && is_array($response->json('data')) ? $response->json('data') : null;
    }

    /**
     * Context keys used here:
     * - sender_key / channel_account_id: sending number (else the branch number, else the default)
     * - branch_id: picks the branch number when no sender is given (defaults to the signed-in admin's branch)
     * - platform_conversation_id: reply inside an existing platform conversation
     * - name / recipient_name: customer name for a new contact
     * - reference: invoice or contract number, returned in every webhook
     * - clickchat_template: ['name' => ..., 'language' => 'ar', 'vars' => [...]] for an approved Meta template
     * - attachment_path / attachment_filename: a file on the local disk sent as a real attachment
     */
    public function sendText(string $to, string $message, array $context = []): array
    {
        $optOut = app(WhatsAppOptOutService::class);
        if ($optOut->blocks($to, $context)) {
            return OutboundMessageRecorder::attempt('whatsapp', $to, $message, ClickChatMessageStore::PROVIDER,
                fn () => $optOut->blockedResult(), $context);
        }

        $result = OutboundMessageRecorder::attempt('whatsapp', $to, $message, ClickChatMessageStore::PROVIDER,
            fn () => $this->sendRaw($to, $message, $context), $context);

        return $this->handleFailure($to, $message, $context, $result);
    }

    /** One send without audit, retry or fallback; used by the retry runner. */
    public function deliver(string $to, string $message, array $context): array
    {
        return $this->sendRaw($to, $message, $context);
    }

    public function isRetryable(array $result, array $context, string $message = ''): bool
    {
        return in_array($result['error'] ?? '', self::RETRYABLE_ERRORS, true)
            && filter_var(config('services.whatsapp.clickchat.auto_retry', true), FILTER_VALIDATE_BOOLEAN)
            // A login code is useless minutes later and should not sit in a table, so it is never retried.
            && !OutboundMessageRecorder::sensitive($message, $context)
            && ($context['purpose'] ?? '') !== 'verification'
            && empty($context['_retry']);
    }

    public function canFallback(array $result, array $context): bool
    {
        return $this->fallbackProvider() !== 'none'
            && in_array($result['error'] ?? '', self::FALLBACK_ERRORS, true)
            && empty($context['clickchat_template'])
            && empty($context['platform_conversation_id'])
            && ($context['source'] ?? '') !== 'whatsapp_inbox';
    }

    /** Send through the configured backup channel; the result carries which one was used. */
    public function fallback(string $to, string $message, array $context): array
    {
        $provider = $this->fallbackProvider();
        if (!empty($context['attachment_filename']) && !empty($context['attachment_url'])) {
            $message = trim($message . "\n" . $context['attachment_filename'] . "\n" . $context['attachment_url']);
        }
        try {
            $result = app(WhatsAppMessagingService::class)->sendViaFallback($provider, $to, $message, $context);
        } catch (Throwable $e) {
            Log::warning('WhatsApp fallback send failed', ['provider' => $provider, 'exception' => get_class($e)]);
            $result = ['success' => false, 'error' => 'fallback_failed', 'response' => ['message' => 'تعذر الإرسال عبر القناة الاحتياطية.']];
        }

        return $result + ['fallback_provider' => $provider];
    }

    private function handleFailure(string $to, string $message, array $context, array $result): array
    {
        if (!empty($result['success'])) {
            return $result;
        }

        $error = (string) ($result['error'] ?? '');
        if (in_array($error, ['auth_error', 'forbidden'], true)) {
            app(ClickChatAlertService::class)->credentialsRejected((string) data_get($result, 'response.message', ''));
        }

        if ($this->isRetryable($result, $context, $message)) {
            return $this->scheduleRetry($to, $message, $context, $result);
        }

        if ($this->canFallback($result, $context)) {
            $fallback = $this->fallback($to, $message, $context);
            if (!empty($fallback['success'])) {
                return $fallback + ['clickchat_error' => $error];
            }
        }

        return $result;
    }

    private function scheduleRetry(string $to, string $message, array $context, array $result): array
    {
        if (!Schema::hasTable('whatsapp_send_retries')) {
            return $result;
        }

        WhatsAppSendRetry::query()->create([
            'provider' => ClickChatMessageStore::PROVIDER,
            'recipient' => mb_substr($to, 0, 64),
            'message' => $message,
            'context' => $this->storableContext($context),
            'outbound_message_log_id' => $result['audit_log_id'] ?? null,
            'attempts' => 0,
            'next_attempt_at' => now()->addMinute(),
            'status' => 'pending',
            'last_error' => (string) data_get($result, 'response.message', ''),
        ]);
        if (!empty($result['audit_log_id'])) {
            \App\Models\OutboundMessageLog::withoutBranchScope()->whereKey($result['audit_log_id'])
                ->update(['status' => 'pending', 'failed_at' => null, 'error' => 'سيُعاد الإرسال تلقائيًا بعد دقيقة.']);
        }

        $result['retry_scheduled'] = true;
        $result['response'] = ['message' => 'المنصة مشغولة الآن؛ سيُعاد الإرسال تلقائيًا خلال دقائق.'];

        return $result;
    }

    /** Only plain values survive in the retry row; closures or models in a context are dropped. */
    private function storableContext(array $context): array
    {
        $context['branch_id'] ??= auth('admin')->user()?->branch_id;
        $context['admin_id'] ??= auth('admin')->id();

        return json_decode((string) json_encode($context, JSON_PARTIAL_OUTPUT_ON_ERROR), true) ?: [];
    }

    private function sendRaw(string $to, string $message, array $context): array
    {
        $phone = app(MetaWhatsAppService::class)->normalizePhone($to);
        $conversationId = (int) ($context['platform_conversation_id'] ?? 0);
        if ($phone === null && $conversationId <= 0) {
            return ['success' => false, 'to' => $to, 'normalized_to' => null, 'status_code' => null,
                'error' => 'invalid_phone', 'response' => ['message' => 'رقم الهاتف غير صالح للإرسال.']];
        }
        if (!$this->isConfigured()) {
            return ['success' => false, 'to' => $to, 'normalized_to' => $phone, 'status_code' => null,
                'error' => 'not_configured', 'response' => ['missing' => $this->missingConfigKeys()]];
        }
        if (RateLimiter::tooManyAttempts($this->rateKey(), self::SENDS_PER_MINUTE)) {
            return ['success' => false, 'to' => $to, 'normalized_to' => $phone, 'status_code' => 429,
                'error' => 'rate_limited', 'response' => ['message' => 'تم بلوغ حد 60 رسالة في الدقيقة.']];
        }
        RateLimiter::hit($this->rateKey(), 60);

        $template = (array) ($context['clickchat_template'] ?? []);
        if (!$template && empty($context['attachment_url']) && empty($context['platform_conversation_id'])) {
            $sender = $this->channelAccountId($context);
            try {
                $template = app(ClickChatTemplateBinding::class)->resolve($message, $context) ?? [];
                if ($template) {
                    $channel = collect($this->channels())->firstWhere('id', $sender);
                    if (($channel['type'] ?? '') === 'whatsapp_qr') {
                        $template = [];
                    } else {
                        if (!$sender) throw new RuntimeException('حدد رقم الإرسال لربط قالب اللايف شات.');
                        $approved = collect($this->templates(false, $sender))->first(fn ($row) =>
                            $row['name'] === $template['name'] && $row['language'] === $template['language']);
                        if (!$approved || $approved['variables'] !== count($template['vars'])) {
                            throw new RuntimeException('قالب اللايف شات غير معتمد لهذا الرقم أو عدد متغيراته غير صحيح.');
                        }
                    }
                }
            } catch (RuntimeException $e) {
                return ['success' => false, 'error' => 'template_binding_error', 'response' => ['message' => $e->getMessage()]];
            }
        }
        $isTemplate = trim((string) ($template['name'] ?? '')) !== '';
        $attachment = $isTemplate ? null : $this->attachment($context);
        $name = trim((string) ($context['name'] ?? $context['recipient_name'] ?? ''));
        $reference = $this->reference($context);
        $body = $attachment ? trim((string) ($context['caption'] ?? '')) : $message;
        $payload = array_filter([
            'conversation_id' => $conversationId > 0 ? $conversationId : null,
            'to' => $conversationId > 0 ? null : '+' . $phone,
            'channel_account_id' => $this->channelAccountId($context),
            'name' => $name !== '' ? mb_substr($name, 0, 120) : null,
            'reference' => $reference,
            'body' => $isTemplate ? null : $body,
            'template_name' => $isTemplate ? (string) $template['name'] : null,
            'template_language' => $isTemplate ? (string) ($template['language'] ?? 'ar') : null,
            'template_vars' => $isTemplate ? array_values(array_map('strval', (array) ($template['vars'] ?? []))) : null,
        ], static fn ($value) => $value !== null && $value !== '' && $value !== []);

        try {
            // Never retried here: a timeout may come after the platform already sent the message.
            $request = $this->client();
            $response = $attachment
                ? $request->attach('file', $attachment['contents'], $attachment['filename'])->post($this->url('messages/send'), $payload)
                : $request->asJson()->post($this->url('messages/send'), $payload);
        } catch (Throwable $e) {
            Log::warning('Click Live Chat send failed before a response', ['exception' => get_class($e)]);
            return ['normalized_to' => $phone] + $this->transportFailure();
        }

        $data = is_array($response->json()) ? $response->json() : [];
        $messageId = (int) ($data['message_id'] ?? 0);

        if ($messageId > 0) {
            // Save it now so the inbox and the reference are complete even before the webhook arrives.
            $this->store->upsert(
                ['id' => $messageId, 'conversation_id' => $data['conversation_id'] ?? null, 'direction' => 'out',
                    'type' => $isTemplate ? 'template' : ($attachment ? 'document' : 'text'),
                    'body' => $isTemplate ? $message : ($attachment ? ($body ?: $attachment['filename']) : $message),
                    'status' => (string) ($data['status'] ?? 'sent'), 'error' => $data['error'] ?? null,
                    'reference' => $reference],
                ['id' => $data['contact_id'] ?? null, 'phone_e164' => $phone ? '+' . $phone : null, 'name' => $name],
                ['id' => $data['conversation_id'] ?? null],
                ['id' => $data['channel_account_id'] ?? null],
                ['admin_id' => $context['admin_id'] ?? null]
            );
        }

        $success = $response->successful() && $messageId > 0 && ($data['status'] ?? '') !== 'failed';
        if ($success) {
            return ['success' => true, 'to' => $to, 'normalized_to' => $phone, 'status_code' => $response->status(),
                'message_id' => (string) $messageId, 'conversation_id' => $data['conversation_id'] ?? null,
                'status' => $data['status'] ?? 'sent', 'response' => $data];
        }

        [$error, $text] = $this->sendError($response, $data, $messageId);
        Log::warning('Click Live Chat send rejected', ['status' => $response->status(), 'error' => $error]);

        return ['success' => false, 'to' => $to, 'normalized_to' => $phone, 'status_code' => $response->status(),
            'message_id' => $messageId > 0 ? (string) $messageId : null, 'error' => $error,
            'window_closed' => (bool) ($data['window_closed'] ?? false),
            'response' => ['message' => $text]];
    }

    /** A file on the local disk, read here so no outside URL is ever fetched. */
    private function attachment(array $context): ?array
    {
        $path = (string) ($context['attachment_path'] ?? '');
        if ($path === '' || !Storage::disk('local')->exists($path)) {
            return null;
        }

        return ['contents' => Storage::disk('local')->get($path),
            'filename' => (string) ($context['attachment_filename'] ?? basename($path))];
    }

    private function sendError(Response $response, array $data, int $messageId): array
    {
        $platformError = trim((string) ($data['error'] ?? $data['message'] ?? ''));

        return match (true) {
            !empty($data['window_closed']) => ['window_closed',
                'انتهت نافذة الـ 24 ساعة لهذا العميل على رقم ميتا. أرسل قالبًا معتمدًا.'],
            $messageId > 0 => ['send_failed', 'رفض واتساب الإرسال: ' . ($platformError ?: 'سبب غير معروف.')],
            $response->status() === 401 => ['auth_error', 'مفتاح API لمنصة Click Live Chat غير صحيح أو محذوف.'],
            $response->status() === 403 => ['forbidden', 'مفتاح API بدون صلاحية messages:write.'],
            $response->status() === 404 => ['not_found', 'جهة الاتصال أو المحادثة غير موجودة في المنصة.'],
            $response->status() === 429 => ['rate_limited', 'تم تجاوز حد 60 رسالة في الدقيقة. أعد المحاولة بعد دقيقة.'],
            $response->status() >= 500 => ['server_error', 'المنصة غير متاحة مؤقتًا.'],
            str_contains($platformError, 'No active WhatsApp number') => ['no_active_number',
                'رقم واتساب المرسِل غير متصل في المنصة أو غير موجود.'],
            default => ['invalid_request', $platformError ?: 'رفضت المنصة الطلب (HTTP ' . $response->status() . ').'],
        };
    }

    private function httpErrorMessage(Response $response, string $ability): string
    {
        return match ($response->status()) {
            401 => 'مفتاح API غير صحيح أو محذوف (401).',
            403 => "مفتاح API بدون صلاحية {$ability} (403).",
            404 => 'رابط المنصة غير صحيح (404).',
            default => 'رفضت المنصة الطلب (HTTP ' . $response->status() . ').',
        };
    }

    private function transportFailure(): array
    {
        // Exception text may include the URL or response body; keep it out of the result.
        return ['success' => false, 'status_code' => null, 'error' => 'exception',
            'response' => ['message' => 'تعذر الاتصال بمنصة Click Live Chat. تحقق من المنصة قبل إعادة الإرسال لتجنب التكرار.']];
    }

    private function mapChannels(array $rows): array
    {
        return array_values(array_map(static fn ($row) => [
            'id' => (int) ($row['id'] ?? 0),
            'type' => (string) ($row['type'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'status' => (string) ($row['status'] ?? ''),
        ], array_filter($rows, static fn ($row) => is_array($row) && ($row['channel'] ?? 'whatsapp') === 'whatsapp')));
    }

    /** Explicit sender, else the number of the branch, else the default number. */
    public function channelAccountId(array $context): ?int
    {
        $id = (int) ($context['channel_account_id'] ?? $context['sender_key'] ?? 0);
        if ($id > 0) {
            return $id;
        }

        $branchId = (int) ($context['branch_id'] ?? auth('admin')->user()?->branch_id ?? 0);
        $branchSender = (int) data_get((array) config('services.whatsapp.clickchat.branch_senders', []), (string) $branchId, 0);

        return $branchSender > 0 ? $branchSender : $this->defaultChannelAccountId();
    }

    private function reference(array $context): ?string
    {
        $reference = trim((string) ($context['reference'] ?? ''));
        if ($reference === '' && !empty($context['contract_id'])) {
            $reference = 'contract-' . $context['contract_id'];
        } elseif ($reference === '' && !empty($context['order_id'])) {
            $reference = 'order-' . $context['order_id'];
        }

        return $reference !== '' ? mb_substr($reference, 0, 120) : null;
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()
            ->withoutRedirecting()
            ->withToken((string) config('services.whatsapp.clickchat.api_token'))
            ->timeout(max(5, (int) config('services.whatsapp.timeout', 15)));
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.whatsapp.clickchat.base_url'), '/') . '/api/v1/' . ltrim($path, '/');
    }

    private function rateKey(): string
    {
        return 'clickchat-send:' . sha1((string) config('services.whatsapp.clickchat.api_token'));
    }

    private function cacheKey(string $prefix): string
    {
        return $prefix . sha1((string) config('services.whatsapp.clickchat.base_url')
            . '|' . (string) config('services.whatsapp.clickchat.api_token'));
    }
}
