<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\ingaz\User;
use App\Support\OutboundMessageRecorder;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Saves Click Live Chat messages into the shared WhatsApp inbox tables.
 *
 * The same message can arrive from the send response, from several webhook
 * deliveries and from the status poller, in any order. Rows are keyed by the
 * platform message id and the status only moves forward.
 */
class ClickChatMessageStore
{
    public const PROVIDER = 'clickchat';

    private const STATUS_RANK = ['queued' => 0, 'pending' => 0, 'accepted' => 0, 'sent' => 1, 'delivered' => 2, 'read' => 3, 'failed' => 4];

    /** Handle one verified webhook payload. */
    public function handleEvent(array $payload): ?WhatsAppMessage
    {
        $event = (string) ($payload['event'] ?? '');
        if (!in_array($event, ['message.received', 'message.sent', 'message.status'], true)) {
            return null;
        }

        return $this->upsert(
            (array) ($payload['message'] ?? []),
            (array) ($payload['contact'] ?? []),
            (array) ($payload['conversation'] ?? []),
            (array) ($payload['channel_account'] ?? []),
            ['event' => $event, 'timestamp' => $payload['timestamp'] ?? null, 'raw' => $payload]
        );
    }

    /**
     * @param array $extra admin_id, event, timestamp, raw
     */
    public function upsert(array $message, array $contact = [], array $conversation = [], array $channel = [], array $extra = []): ?WhatsAppMessage
    {
        $platformId = (int) ($message['id'] ?? 0);
        if ($platformId <= 0) {
            Log::warning('Ignored Click Live Chat message without id', ['event' => $extra['event'] ?? null]);
            return null;
        }

        try {
            return $this->write($platformId, $message, $contact, $conversation, $channel, $extra);
        } catch (QueryException $e) {
            // Two deliveries raced on the unique key: the row exists now, so update it.
            if ((string) $e->getCode() !== '23000') {
                throw $e;
            }
            return $this->write($platformId, $message, $contact, $conversation, $channel, $extra);
        }
    }

    /** Apply a status read back from GET /messages/{id}. */
    public function applyStatus(int $platformId, string $status, ?string $error = null): void
    {
        $this->upsert(['id' => $platformId, 'status' => $status, 'error' => $error]);
    }

    private function write(int $platformId, array $message, array $contact, array $conversation, array $channel, array $extra): ?WhatsAppMessage
    {
        return DB::transaction(function () use ($platformId, $message, $contact, $conversation, $channel, $extra) {
            $row = WhatsAppMessage::query()->where('platform_message_id', $platformId)->lockForUpdate()->first();
            $direction = ($message['direction'] ?? ($row?->direction === 'inbound' ? 'in' : 'out')) === 'in' ? 'inbound' : 'outbound';
            $status = strtolower(trim((string) ($message['status'] ?? '')));
            $error = trim((string) ($message['error'] ?? '')) ?: null;

            if ($row) {
                $this->fillMissing($row, $message, $channel);
                if ($row->direction === 'outbound') {
                    $this->upgradeStatus($row, $status, $error);
                }
                if (!empty($extra['raw'])) {
                    $row->provider_payload = $extra['raw'];
                }
                $row->save();
                $this->auditStatus($platformId, $row->direction, $status, $error);
                return $row;
            }

            $phone = $this->digits((string) ($contact['phone_e164'] ?? ''));
            if ($phone === '') {
                // A status that arrived before the message itself; the poller or a later event fills it in.
                Log::info('Click Live Chat status received before the message was stored', ['platform_message_id' => $platformId]);
                $this->auditStatus($platformId, $direction, $status, $error);
                return null;
            }

            $occurredAt = $this->time($message['sent_at'] ?? null) ?? $this->time($extra['timestamp'] ?? null) ?? now();
            $body = $this->body($message);
            $thread = $this->conversation($phone, $direction, $body, $occurredAt, $contact, $conversation, $channel, $message);

            $row = new WhatsAppMessage([
                'whatsapp_conversation_id' => $thread->id,
                'provider' => self::PROVIDER,
                'platform_message_id' => $platformId,
                'channel_account_id' => $channel['id'] ?? null,
                'reference' => $this->limit($message['reference'] ?? null, 120),
                'direction' => $direction,
                'type' => $this->limit($message['type'] ?? 'text', 32) ?: 'text',
                'body' => $body,
                'media_url' => $this->limit($message['media_url'] ?? null, 500),
                'status' => $direction === 'inbound' ? 'received' : 'queued',
                'sent_by_admin_id' => $extra['admin_id'] ?? null,
                'provider_payload' => $extra['raw'] ?? $message,
                'occurred_at' => $occurredAt,
            ]);
            if ($direction === 'outbound') {
                $this->upgradeStatus($row, $status ?: 'sent', $error);
            }
            $row->save();
            $this->auditStatus($platformId, $direction, $status, $error);
            if ($direction === 'inbound') {
                app(WhatsAppOptOutService::class)->handleInbound($phone, $message['body'] ?? '', self::PROVIDER);
            }

            return $row;
        });
    }

    private function conversation(string $phone, string $direction, string $body, Carbon $at, array $contact, array $conversation, array $channel, array $message): WhatsAppConversation
    {
        // The webhook and the queue run without a branch; one thread per phone across branches.
        $thread = WhatsAppConversation::withoutBranchScope()->lockForUpdate()->firstOrNew(['phone' => $phone]);
        $name = trim((string) ($contact['name'] ?? ''));

        $thread->fill([
            'wa_id' => $thread->wa_id ?: $phone,
            'provider' => self::PROVIDER,
            'platform_conversation_id' => $conversation['id'] ?? $message['conversation_id'] ?? $thread->platform_conversation_id,
            'channel_account_id' => $channel['id'] ?? $thread->channel_account_id,
            'profile_name' => $name !== '' ? $name : $thread->profile_name,
            'user_id' => $thread->user_id ?: $this->findUserIdByPhone($phone),
        ]);

        if ($direction === 'inbound') {
            $thread->unread_count = (int) $thread->unread_count + 1;
            $thread->customer_service_window_expires_at = $at->copy()->addHours(24);
        }
        if (!$thread->last_message_at || $at->greaterThanOrEqualTo($thread->last_message_at)) {
            $thread->last_direction = $direction;
            $thread->last_message_preview = $body;
            $thread->last_message_at = $at;
        }
        $thread->save();

        return $thread;
    }

    /** Status only moves forward: a late "delivered" never replaces "read". */
    private function upgradeStatus(WhatsAppMessage $row, string $status, ?string $error): void
    {
        if (!array_key_exists($status, self::STATUS_RANK)
            || self::STATUS_RANK[$status] <= (self::STATUS_RANK[(string) $row->status] ?? -1)) {
            return;
        }

        $row->status = $status;
        if ($status === 'delivered') {
            $row->delivered_at = now();
        } elseif ($status === 'read') {
            $row->delivered_at ??= now();
            $row->read_at = now();
        } elseif ($status === 'failed') {
            $row->failed_at = now();
            $row->error_code = 'provider_failed';
            $row->error_message = $error ?: 'تعذر تسليم الرسالة.';
        }
    }

    private function fillMissing(WhatsAppMessage $row, array $message, array $channel): void
    {
        $row->reference ??= $this->limit($message['reference'] ?? null, 120);
        $row->channel_account_id ??= $channel['id'] ?? null;
        $row->media_url ??= $this->limit($message['media_url'] ?? null, 500);
        if (($row->body === null || $row->body === '') && isset($message['body'])) {
            $row->body = $this->body($message);
        }
    }

    /** Keep the outbound audit log (sends made outside the inbox) in step. */
    private function auditStatus(int $platformId, string $direction, string $status, ?string $error): void
    {
        if ($direction !== 'outbound' || !in_array($status, ['sent', 'delivered', 'read', 'failed'], true)) {
            return;
        }
        OutboundMessageRecorder::whatsAppStatus((string) $platformId, $status, ['error' => $error], self::PROVIDER);
    }

    private function body(array $message): string
    {
        $body = trim((string) ($message['body'] ?? ''));
        if ($body !== '') {
            return $body;
        }

        return match ((string) ($message['type'] ?? '')) {
            'image' => 'صورة',
            'video' => 'فيديو',
            'audio', 'voice' => 'رسالة صوتية',
            'document' => 'مستند',
            'sticker' => 'ملصق',
            'location' => 'موقع',
            default => '',
        };
    }

    private function findUserIdByPhone(string $phone): ?int
    {
        try {
            if (!Schema::hasTable('users')) {
                return null;
            }
            $columns = array_values(array_filter(['phone', 'phone2', 'whatsapp_phone'],
                static fn (string $column): bool => Schema::hasColumn('users', $column)));
            if ($columns === []) {
                return null;
            }
            $lastNine = substr($phone, -9);
            $id = User::query()->where(function ($query) use ($columns, $lastNine) {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', '%' . $lastNine);
                }
            })->value('id');

            return $id ? (int) $id : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function time($value): ?Carbon
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function limit($value, int $length): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $length);
    }

    private function digits(string $value): string
    {
        return (string) preg_replace('/\D+/', '', $value);
    }
}
