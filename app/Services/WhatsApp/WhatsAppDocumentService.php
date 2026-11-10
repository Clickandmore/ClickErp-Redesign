<?php

namespace App\Services\WhatsApp;

use App\Support\OutboundMessageRecorder;
use Illuminate\Support\Facades\Http;

class WhatsAppDocumentService
{
    public function __construct(private WhatsAppMessagingService $messaging, private MetaWhatsAppService $meta) {}

    public function send(string $to, string $url, string $filename, array $context = []): array
    {
        if ($this->messaging->driver() === 'clickchat') {
            // A local file goes as a real attachment; without one, a download link is shared.
            $hasFile = !empty($context['attachment_path'])
                && \Illuminate\Support\Facades\Storage::disk('local')->exists((string) $context['attachment_path']);

            return app(ClickChatWhatsAppService::class)->sendText($to, $hasFile ? $filename : $filename . "\n" . $url,
                $hasFile ? $context + ['attachment_filename' => $filename, 'attachment_url' => $url] : $context);
        }
        if ($this->messaging->driver() === 'chatwoot') {
            return app(ChatwootWhatsAppService::class)->sendText($to, $filename, $context + [
                'attachment_url' => $url, 'attachment_filename' => $filename,
            ]);
        }

        return OutboundMessageRecorder::attempt('whatsapp', $to, $filename, $this->messaging->driver(), function () use ($to, $url, $filename, $context) {
            $phone = $this->meta->normalizePhone($to);
            if (!$phone || !$this->messaging->isConfigured()) {
                return ['success' => false, 'error' => 'invalid_phone_or_config'];
            }
            $timeout = max(5, (int) config('services.whatsapp.timeout', 15));
            if ($this->messaging->driver() === 'ultramsg') {
                $sender = $this->messaging->resolveSender($context['sender_key'] ?? null);
                if (!$sender) return ['success' => false, 'error' => 'not_configured'];
                $response = Http::asForm()->acceptJson()->withoutRedirecting()->timeout($timeout)->post(
                    rtrim(config('services.whatsapp.ultramsg.api_url', 'https://api.ultramsg.com'), '/') . '/' . $sender['instance_id'] . '/messages/document',
                    ['token' => $sender['token'], 'to' => $phone, 'document' => $url, 'filename' => $filename]
                );
                $data = $response->json() ?? [];
                $id = $data['id'] ?? null;
                $success = $response->successful() && !empty($id) && empty($data['error'])
                    && filter_var($data['sent'] ?? false, FILTER_VALIDATE_BOOLEAN);
            } else {
                $response = Http::withToken(config('services.whatsapp.access_token'))->acceptJson()->withoutRedirecting()->timeout($timeout)->post(
                    'https://graph.facebook.com/' . config('services.whatsapp.graph_version', 'v23.0') . '/' . config('services.whatsapp.phone_number_id') . '/messages',
                    ['messaging_product' => 'whatsapp', 'to' => $phone, 'type' => 'document', 'document' => ['link' => $url, 'filename' => $filename]]
                );
                $data = $response->json() ?? [];
                $id = data_get($data, 'messages.0.id');
                $success = $response->successful() && !empty($id);
            }
            return ['success' => $success, 'message_id' => $id, 'status_code' => $response->status(),
                'error' => $success ? null : 'api_error', 'response' => $data];
        }, $context);
    }
}
