<?php

namespace App\Services\ingaz;

use App\Models\MaidCvShareMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MaidCvShareStatusService
{
    public static function update(string $messageId, string $state, array $payload): void
    {
        if (!in_array($state, ['sent', 'delivered', 'read', 'failed'], true)) return;
        DB::transaction(function () use ($messageId, $state, $payload) {
            $message = MaidCvShareMessage::where('provider_message_id', $messageId)
                ->whereHas('share', fn ($q) => $q->where('provider', 'meta'))->lockForUpdate()->first();
            if (!$message) return;
            $ranks = ['pending' => 0, 'sending' => 0, 'unknown' => 0, 'failed' => 0, 'accepted' => 1, 'sent' => 2, 'delivered' => 3, 'read' => 4];
            if ($state === 'failed') {
                if (in_array($message->status, ['delivered', 'read'], true)) return;
            } elseif (($ranks[$message->status] ?? 0) > ($ranks[$state] ?? 0)) return;
            $at = isset($payload['timestamp']) ? Carbon::createFromTimestamp((int) $payload['timestamp']) : now();
            $changes = ['status' => $state];
            if ($state === 'delivered' || $state === 'read') $changes['delivered_at'] = $message->delivered_at ?? $at;
            if ($state === 'read') $changes['read_at'] = $at;
            if ($state === 'failed') $changes['error'] = 'أبلغ مزود واتساب بفشل الرسالة. رمز الخطأ: ' . (string) data_get($payload, 'errors.0.code', '');
            $message->update($changes);
            // Do not replace the queue's processing state while other messages are in flight.
            if (!in_array($message->share->status, ['queued', 'processing'], true)) $message->share->refreshStatus();
        });
    }
}
