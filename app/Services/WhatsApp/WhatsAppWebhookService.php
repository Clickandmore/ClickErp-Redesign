<?php

namespace App\Services\WhatsApp;

use App\Models\ingaz\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WhatsAppWebhookService
{
    public function handle(array $payload): array
    {
        $result = ['messages' => 0, 'statuses' => 0];

        foreach ((array) data_get($payload, 'entry', []) as $entry) {
            foreach ((array) data_get($entry, 'changes', []) as $change) {
                $value = (array) data_get($change, 'value', []);
                $phoneNumberId = (string) data_get($value, 'metadata.phone_number_id', '');
                $contacts = collect((array) ($value['contacts'] ?? []))->keyBy('wa_id');

                foreach ((array) ($value['messages'] ?? []) as $message) {
                    $contact = (array) $contacts->get((string) ($message['from'] ?? ''), []);
                    $this->storeIncoming((array) $message, $contact, $phoneNumberId, $payload);
                    $result['messages']++;
                }

                foreach ((array) ($value['statuses'] ?? []) as $status) {
                    $this->updateStatus((array) $status, $payload);
                    $result['statuses']++;
                }
            }
        }

        return $result;
    }

    private function storeIncoming(array $message, array $contact, string $phoneNumberId, array $payload): void
    {
        $metaMessageId = trim((string) ($message['id'] ?? ''));
        $phone = $this->digits((string) ($message['from'] ?? data_get($contact, 'wa_id', '')));

        if ($metaMessageId === '' || $phone === '') {
            Log::warning('Ignored malformed WhatsApp inbound message', ['message' => $message]);
            return;
        }

        DB::transaction(function () use ($message, $contact, $phoneNumberId, $payload, $metaMessageId, $phone) {
            if (WhatsAppMessage::query()->where('meta_message_id', $metaMessageId)->exists()) {
                return;
            }

            $occurredAt = $this->timestamp($message['timestamp'] ?? null);
            $profileName = trim((string) data_get($contact, 'profile.name', ''));
            $conversation = WhatsAppConversation::query()->firstOrNew(['phone' => $phone]);

            $conversation->fill([
                'wa_id' => $this->digits((string) data_get($contact, 'wa_id', '')) ?: $phone,
                'profile_name' => $profileName !== '' ? $profileName : $conversation->profile_name,
                'user_id' => $conversation->user_id ?: $this->findUserIdByPhone($phone),
                'phone_number_id' => $phoneNumberId !== '' ? $phoneNumberId : $conversation->phone_number_id,
                'unread_count' => (int) $conversation->unread_count + 1,
                'last_direction' => 'inbound',
                'last_message_preview' => $this->messageBody($message),
                'last_message_at' => $occurredAt,
                'customer_service_window_expires_at' => $occurredAt->copy()->addHours(24),
            ]);
            $conversation->save();

            WhatsAppMessage::query()->create([
                'whatsapp_conversation_id' => $conversation->id,
                'meta_message_id' => $metaMessageId,
                'reply_to_message_id' => data_get($message, 'context.id'),
                'direction' => 'inbound',
                'type' => (string) ($message['type'] ?? 'unknown'),
                'body' => $this->messageBody($message),
                'media_id' => $this->mediaValue($message, 'id'),
                'media_mime_type' => $this->mediaValue($message, 'mime_type'),
                'media_filename' => $this->mediaValue($message, 'filename'),
                'status' => 'received',
                'provider_payload' => $payload,
                'occurred_at' => $occurredAt,
            ]);
            if (($message['type'] ?? '') === 'text') {
                app(WhatsAppOptOutService::class)->handleInbound($phone, $this->messageBody($message), 'meta');
            }
        });
    }

    private function updateStatus(array $status, array $payload): void
    {
        $messageId = trim((string) ($status['id'] ?? ''));
        $state = strtolower(trim((string) ($status['status'] ?? '')));

        if ($messageId === '' || !in_array($state, ['sent', 'delivered', 'read', 'failed'], true)) {
            return;
        }

        // Also update sends made outside the inbox (OTP, reminders, campaigns).
        \App\Support\OutboundMessageRecorder::whatsAppStatus($messageId, $state, $status);
        if (Schema::hasTable('maid_cv_share_messages')) {
            \App\Services\ingaz\MaidCvShareStatusService::update($messageId, $state, $status);
        }

        $message = WhatsAppMessage::query()->where('meta_message_id', $messageId)->first();
        if (!$message) {
            Log::info('WhatsApp status received before local message was available', [
                'meta_message_id' => $messageId,
                'status' => $state,
            ]);
            return;
        }

        if ($this->statusRank($state) < $this->statusRank((string) $message->status) && $state !== 'failed') {
            return;
        }

        $occurredAt = $this->timestamp($status['timestamp'] ?? null);
        $message->status = $state;
        $message->provider_payload = $payload;

        if ($state === 'delivered') {
            $message->delivered_at = $occurredAt;
        } elseif ($state === 'read') {
            $message->delivered_at ??= $occurredAt;
            $message->read_at = $occurredAt;
        } elseif ($state === 'failed') {
            $message->failed_at = $occurredAt;
            $message->error_code = (string) data_get($status, 'errors.0.code', '');
            $message->error_message = (string) (
                data_get($status, 'errors.0.error_data.details')
                ?: data_get($status, 'errors.0.message')
                ?: 'تعذر تسليم الرسالة.'
            );
        }

        $message->save();
    }

    private function findUserIdByPhone(string $phone): ?int
    {
        if (!Schema::hasTable('users')) {
            return null;
        }

        $lastNine = substr($phone, -9);
        $columns = array_values(array_filter(
            ['phone', 'phone2', 'whatsapp_phone'],
            static fn (string $column): bool => Schema::hasColumn('users', $column)
        ));

        if ($columns === []) {
            return null;
        }

        $query = User::query()->select('id');
        $query->where(function ($builder) use ($columns, $lastNine) {
            foreach ($columns as $column) {
                $builder->orWhere($column, 'like', '%' . $lastNine);
            }
        });

        $id = $query->value('id');

        return $id ? (int) $id : null;
    }

    private function messageBody(array $message): string
    {
        $type = (string) ($message['type'] ?? 'unknown');

        return match ($type) {
            'text' => trim((string) data_get($message, 'text.body', '')),
            'button' => trim((string) data_get($message, 'button.text', '')),
            'interactive' => trim((string) (
                data_get($message, 'interactive.button_reply.title')
                ?: data_get($message, 'interactive.list_reply.title')
                ?: 'رد تفاعلي'
            )),
            'location' => sprintf(
                'موقع: %s, %s',
                (string) data_get($message, 'location.latitude', ''),
                (string) data_get($message, 'location.longitude', '')
            ),
            'contacts' => 'بطاقة جهة اتصال',
            'image' => trim((string) data_get($message, 'image.caption', '')) ?: 'صورة',
            'video' => trim((string) data_get($message, 'video.caption', '')) ?: 'فيديو',
            'document' => trim((string) (
                data_get($message, 'document.caption')
                ?: data_get($message, 'document.filename')
                ?: 'مستند'
            )),
            'audio' => 'رسالة صوتية',
            'sticker' => 'ملصق',
            default => 'رسالة من نوع ' . $type,
        };
    }

    private function mediaValue(array $message, string $key): ?string
    {
        $type = (string) ($message['type'] ?? '');
        if (!in_array($type, ['image', 'video', 'audio', 'document', 'sticker'], true)) {
            return null;
        }

        $value = trim((string) data_get($message, $type . '.' . $key, ''));

        return $value !== '' ? $value : null;
    }

    private function timestamp($timestamp): Carbon
    {
        return is_numeric($timestamp)
            ? Carbon::createFromTimestamp((int) $timestamp)
            : now();
    }

    private function statusRank(string $status): int
    {
        return ['pending' => 0, 'sent' => 1, 'delivered' => 2, 'read' => 3][$status] ?? 0;
    }

    private function digits(string $value): string
    {
        return (string) preg_replace('/\D+/', '', $value);
    }
}
