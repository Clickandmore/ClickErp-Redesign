<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ChatwootWhatsAppService
{
    public function templates(): array
    {
        $inbox = $this->request('GET', 'inboxes/'.(int) config('services.whatsapp.chatwoot.inbox_id'));
        return array_values(array_filter($inbox['message_templates'] ?? [], function ($template) {
            if (strtoupper($template['status'] ?? '') !== 'APPROVED') { return false; }
            foreach ($template['components'] ?? [] as $component) {
                $type = strtoupper($component['type'] ?? '');
                if (in_array($type, ['BODY', 'FOOTER'], true)) { continue; }
                if ($type === 'HEADER'
                    && strtoupper($component['format'] ?? '') === 'TEXT'
                    && !preg_match('/\{\{\s*\w+\s*\}\}/u', (string) ($component['text'] ?? ''))) {
                    continue;
                }
                // Other component types need additional provider-specific values.
                return false;
            }
            return true;
        }));
    }

    public function prepareTemplate(string $key, array $values): array
    {
        foreach ($this->templates() as $template) {
            if (($template['name'].'|'.$template['language']) !== $key) { continue; }
            if (strtoupper($template['category'] ?? '') === 'MARKETING'
                && !config('services.whatsapp.allow_advertising', false)) {
                throw new RuntimeException('الإرسال الدعائي غير مفعّل.');
            }
            $body = collect($template['components'])->first(fn ($c) => strtoupper($c['type']) === 'BODY')['text'] ?? '';
            preg_match_all('/\{\{\s*(\w+)\s*\}\}/u', $body, $matches);
            $params = [];
            foreach (array_unique($matches[1]) as $variable) {
                $value = trim((string) ($values[$variable] ?? ''));
                if ($value === '') { throw new RuntimeException('أكمل متغير القالب: '.$variable); }
                $params[$variable] = $value;
            }
            $rendered = preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/u', fn ($m) => $params[$m[1]], $body);
            return ['message' => $rendered, 'template_params' => [
                'name' => $template['name'], 'language' => $template['language'],
                'category' => $template['category'], 'processed_params' => ['body' => $params],
            ]];
        }
        throw new RuntimeException('القالب غير متاح أو غير معتمد. حدّث الصفحة واختر قالبًا صالحًا.');
    }
    public function missingConfigKeys(): array
    {
        return array_values(array_filter(['base_url', 'account_id', 'access_token', 'inbox_id'],
            fn ($key) => !config('services.whatsapp.chatwoot.'.$key)));
    }

    private function request(string $method, string $path, array $data = []): array
    {
        $base = rtrim((string) config('services.whatsapp.chatwoot.base_url'), '/');
        $parts = parse_url($base);
        if (($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            throw new RuntimeException('عنوان المنصة يجب أن يكون HTTPS بدون بيانات دخول أو معاملات.');
        }
        $account = (int) config('services.whatsapp.chatwoot.account_id');
        if ($account <= 0 || !config('services.whatsapp.chatwoot.access_token')) {
            throw new RuntimeException('أكمل معرف الحساب ورمز الوصول أولاً.');
        }
        // Never retry POST automatically: a timeout may occur after the provider accepted it.
        $response = Http::acceptJson()->asJson()->withoutRedirecting()
            ->withHeaders(['api_access_token' => config('services.whatsapp.chatwoot.access_token')])
            ->timeout(max(5, (int) config('services.whatsapp.timeout', 15)))
            ->send($method, "$base/api/v1/accounts/$account/$path", [
                $method === 'GET' ? 'query' : 'json' => $data,
            ]);
        if (!$response->successful()) {
            throw new RuntimeException('رفضت المنصة الطلب (HTTP '.$response->status().').', $response->status());
        }
        if (!is_array($response->json())) {
            throw new RuntimeException('استجابة المنصة ليست JSON صالحًا.');
        }
        return $response->json();
    }

    public function connectionStatus(): array
    {
        try {
            $data = $this->request('GET', 'inboxes');
            $inboxes = collect($data['payload'] ?? [])->map(fn ($inbox) => [
                'id' => $inbox['id'], 'name' => $inbox['name'] ?? '',
                'channel_type' => $inbox['channel_type'] ?? '',
                'phone_number' => $inbox['phone_number'] ?? '',
            ])->values()->all();
            return ['success' => true, 'inboxes' => $inboxes];
        } catch (Throwable $e) {
            return $this->failure($e);
        }
    }

    public function sendText(string $to, string $message, array $context = []): array
    {
        return \App\Support\OutboundMessageRecorder::attempt('whatsapp', $to, $message, 'chatwoot',
            fn () => $this->sendTextRaw($to, $message, $context), $context);
    }

    private function sendTextRaw(string $to, string $message, array $context = []): array
    {
        $phone = app(MetaWhatsAppService::class)->normalizePhone($to);
        if (!$phone || trim($message) === '' || $this->missingConfigKeys()) {
            return ['success' => false, 'error' => 'invalid_input_or_config', 'response' => [
                'message' => 'تحقق من الرقم ونص الرسالة وإعدادات Widers.',
                'missing' => $this->missingConfigKeys(),
            ]];
        }
        try {
            $key = hash('sha256', config('services.whatsapp.chatwoot.base_url').'|'.
                config('services.whatsapp.chatwoot.account_id').'|'.$phone);
            return Cache::lock('chatwoot-send:'.$key, 300)->block(5, function () use ($phone, $message, $context) {
                $inboxId = (int) config('services.whatsapp.chatwoot.inbox_id');
                $inbox = $this->request('GET', 'inboxes/'.$inboxId);
                if (($inbox['channel_type'] ?? '') !== 'Channel::Whatsapp') {
                    throw new RuntimeException('اختر صندوق وارد من نوع WhatsApp للإرسال.');
                }
                $contact = null;
                $page = 1;
                do {
                    $found = $this->request('GET', 'contacts/search', ['q' => '+'.$phone, 'page' => $page]);
                    foreach ($found['payload'] ?? [] as $candidate) {
                        if (preg_replace('/\D/', '', $candidate['phone_number'] ?? '') === $phone) {
                            if ($contact && $contact['id'] !== $candidate['id']) {
                                throw new RuntimeException('يوجد أكثر من عميل بنفس الرقم في المنصة. راجع التكرار أولاً.');
                            }
                            $contact = $candidate;
                        }
                    }
                    $count = (int) data_get($found, 'meta.count', count($found['payload'] ?? []));
                    $page++;
                } while (($page - 1) * 15 < $count && !empty($found['payload']));
                if (!$contact) {
                    $created = $this->request('POST', 'contacts', [
                        'inbox_id' => $inboxId, 'phone_number' => '+'.$phone,
                        'name' => $context['name'] ?? '+'.$phone,
                    ]);
                    $contact = data_get($created, 'payload.contact') ?? data_get($created, 'payload.0') ?? $created;
                }
                if (empty($contact['id']) || ($contact['blocked'] ?? false)) {
                    throw new RuntimeException('تعذر تحديد العميل أو العميل محظور.');
                }
                $contactId = (int) $contact['id'];
                $conversations = $this->request('GET', "contacts/$contactId/conversations");
                $conversation = collect($conversations['payload'] ?? [])
                    ->filter(fn ($row) => (int) ($row['inbox_id'] ?? 0) === $inboxId && ($row['status'] ?? '') === 'open')
                    ->sortByDesc('id')->first();
                if (!$conversation) {
                    if (empty($context['template_params'])) {
                        throw new RuntimeException('لبدء محادثة واتساب اختر قالبًا معتمدًا، أو اطلب من العميل إرسال رسالة أولاً.');
                    }
                    $link = collect($contact['contact_inboxes'] ?? [])->first(
                        fn ($row) => (int) data_get($row, 'inbox.id') === $inboxId);
                    if (!$link) {
                        $link = $this->request('POST', "contacts/$contactId/contact_inboxes", ['inbox_id' => $inboxId]);
                    }
                    if (empty($link['source_id'])) {
                        throw new RuntimeException('تعذر ربط العميل بصندوق الوارد.');
                    }
                    $conversation = $this->request('POST', 'conversations', [
                        'source_id' => $link['source_id'], 'inbox_id' => $inboxId,
                        'contact_id' => $contactId, 'status' => 'open',
                    ]);
                }
                if (empty($conversation['id'])) {
                    throw new RuntimeException('لم تُرجع المنصة معرف المحادثة.');
                }
                $payload = ['content' => $message, 'message_type' => 'outgoing', 'private' => false];
                if (empty($context['template_params']) && !($conversation['can_reply'] ?? false)) {
                    throw new RuntimeException('نافذة الرد غير متاحة أو تعذر التحقق منها. اختر قالب واتساب معتمدًا.');
                }
                if (!empty($context['template_params'])) {
                    $payload['template_params'] = $context['template_params'];
                }
                $result = $this->request('POST', 'conversations/'.(int) $conversation['id'].'/messages', $payload);
                if (empty($result['id']) || ($result['status'] ?? '') === 'failed') {
                    throw new RuntimeException('لم تقبل المنصة الرسالة.');
                }
                Log::info('Chatwoot message accepted', [
                    'account_id' => (int) config('services.whatsapp.chatwoot.account_id'),
                    'inbox_id' => $inboxId,
                    'conversation_id' => $conversation['id'],
                    'message_id' => $result['id'],
                    'status' => $result['status'] ?? 'accepted',
                ]);
                return ['success' => true, 'message_id' => (string) $result['id'],
                    'conversation_id' => $conversation['id'], 'normalized_to' => $phone,
                    'status' => $result['status'] ?? 'accepted', 'response' => $result];
            });
        } catch (Throwable $e) {
            return $this->failure($e);
        }
    }

    private function failure(Throwable $e): array
    {
        // Transport exception text may contain credentials or remote response bodies.
        $message = get_class($e) === RuntimeException::class
            ? $e->getMessage() : 'تعذر إكمال الاتصال. تحقق من المنصة قبل إعادة الإرسال لتجنب التكرار.';
        return ['success' => false, 'error' => 'chatwoot_error', 'response' => ['message' => $message]];
    }
}
