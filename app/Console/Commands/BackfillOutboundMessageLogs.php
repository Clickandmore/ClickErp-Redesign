<?php

namespace App\Console\Commands;

use App\Models\OutboundMessageLog;
use App\Support\OutboundMessageRecorder as Recorder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillOutboundMessageLogs extends Command
{
    protected $signature = 'messages:backfill-logs {--before= : Import records older than this timestamp (default: first unified log, or now)}';
    protected $description = 'Copy existing message history into the unified audit without sending any messages.';

    public function handle(): int
    {
        $before = \Carbon\Carbon::parse($this->option('before') ?: (OutboundMessageLog::withoutBranchScope()->whereNull('legacy_key')->min('created_at') ?: now()));
        $imported = 0;
        foreach (['communication_logs', 'notifications', 'admin_notifications', 'rental_contract_expiry_reminders', 'whatsapp_messages'] as $table) {
            if (!Schema::hasTable($table)) continue;
            DB::table($table)->where('created_at', '<', $before)->orderBy('id')->chunkById(200, function ($rows) use ($table, &$imported) {
                foreach ($rows as $row) {
                    $channels = $table === 'rental_contract_expiry_reminders' && ($row->channel ?? '') === 'both' ? ['sms','whatsapp'] : [null];
                    foreach ($channels as $historicalChannel) {
                    $originalChannel = $row->channel ?? null;
                    if ($historicalChannel) $row->channel = $historicalChannel;
                    $attributes = $this->attributes($table, $row);
                    if ($historicalChannel) $row->channel = $originalChannel;
                    if (!$attributes) continue;
                    $key = $table . ':' . $row->id . ($historicalChannel ? ':' . $historicalChannel : '');
                    if (OutboundMessageLog::withoutBranchScope()->where('legacy_key', $key)->exists()) continue;
                    $context = $attributes['context'] ?? [];
                    $sensitive = Recorder::sensitive((string) ($attributes['message'] ?? ''), $context + ['source' => $attributes['source']]);
                    $attributes['purpose'] = $sensitive ? 'verification' : 'message';
                    $attributes['message'] = $sensitive ? '[محتوى التحقق / استعادة الحساب محجوب]' : Recorder::clean($attributes['message'] ?? '');
                    $attributes['error'] = $sensitive && !empty($attributes['error']) ? 'تعذر الإرسال؛ تفاصيل قديمة محجوبة لحماية رمز التحقق.' : Recorder::clean($attributes['error'] ?? null);
                    $attributes['context'] = Recorder::clean($context);
                    $attributes['provider_response'] = $sensitive ? ['redacted' => true] : Recorder::clean($attributes['provider_response'] ?? []);
                    $attributes['legacy_key'] = $key;
                    $attributes['created_at'] = $row->created_at ?: now();
                    $attributes['updated_at'] = $row->updated_at ?? $row->created_at ?? now();
                    OutboundMessageLog::create($attributes);
                    $imported++;
                    }
                }
            });
        }
        $this->info("Imported {$imported} historical records. No messages sent. Cutoff: {$before}");
        return self::SUCCESS;
    }

    private function text($value): string
    {
        $data = is_string($value) ? json_decode($value, true) : $value;
        return is_array($data) ? (string) ($data['ar'] ?? $data['en'] ?? reset($data) ?: '') : (string) $value;
    }

    private function attributes(string $table, object $row): ?array
    {
        $base = ['branch_id' => $row->branch_id ?? null, 'recipient' => '', 'source' => 'legacy:' . $table, 'status' => 'unknown'];
        if ($table === 'communication_logs') {
            $response = json_decode($row->response ?? '', true) ?: [];
            $meta = json_decode($row->meta ?? '', true) ?: [];
            return array_merge($base, [
                'channel' => $row->channel, 'provider' => $row->provider ?? null, 'recipient' => (string) ($row->recipient_phone ?: ('client:' . ($row->user_id ?? 'unknown'))),
                'recipient_name' => $row->recipient_name ?? null, 'admin_id' => $row->admin_id ?? null,
                'message' => $this->text($row->title ?? '') . "\n" . $this->text($row->message ?? ''),
                'status' => match ($row->status ?? '') { 'sent' => 'accepted', 'failed' => 'failed', 'skipped' => 'skipped', default => 'unknown' },
                'error' => in_array($row->status ?? '', ['failed','skipped'], true) ? $this->text(data_get($response, 'error.message') ?: ($response['message'] ?? 'تفاصيل الفشل غير محفوظة في السجل القديم.')) : null,
                'provider_response' => $response,
                'context' => ['legacy_id' => $row->id, 'user_id' => $row->user_id ?? null, 'source' => $meta['source'] ?? null],
            ]);
        }
        if ($table === 'notifications' || $table === 'admin_notifications') {
            if ($table === 'notifications' && Schema::hasTable('communication_logs') && DB::table('communication_logs')->where('notification_id', $row->id)->exists()) return null;
            $isAdmin = $table === 'admin_notifications';
            $channel = !$isAdmin && in_array($row->type, ['sms','whatsapp'], true) ? $row->type : 'notification';
            $recipient = $isAdmin ? \App\Models\auth\Admin::find($row->admin_id) : \App\Models\ingaz\User::withTrashed()->find($row->user_id);
            return array_merge($base, [
                'branch_id' => $recipient?->branch_id, 'channel' => $channel,
                'recipient' => $channel === 'notification' ? ($isAdmin ? 'admin:' . $row->admin_id : 'client:' . $row->user_id) : (string) ($recipient?->phone ?: 'unknown'),
                'recipient_name' => $recipient?->name,
                'message' => $this->text($row->title) . "\n" . $this->text($isAdmin ? $row->message : $row->description),
                'status' => $channel === 'notification' ? 'delivered' : 'unknown',
                'context' => ['legacy_id' => $row->id, 'user_id' => $isAdmin ? null : $row->user_id],
            ]);
        }
        if ($table === 'rental_contract_expiry_reminders') {
            $contract = DB::table('rental_contracts')->find($row->rental_contract_id);
            $user = $contract ? \App\Models\ingaz\User::withTrashed()->find($contract->user_id) : null;
            return array_merge($base, [
                'channel' => $row->channel ?? 'sms',
                'recipient' => (string) ($user?->phone ?: 'unknown'), 'recipient_name' => $user?->name,
                'branch_id' => $contract->branch_id ?? null, 'message' => 'تنبيه انتهاء عقد التأجير (السجل القديم لا يحفظ النص الكامل).',
                'status' => (($row->channel ?? '') === 'sms' ? $row->sms_sent : $row->whatsapp_sent) ? 'accepted' : 'failed', 'error' => $row->send_error ?? null,
                'accepted_at' => $row->sent_at ?? null,
                'context' => ['contract_id' => $row->rental_contract_id, 'reminder_id' => $row->id, 'original_channel' => $row->channel ?? null, 'sms_sent' => $row->sms_sent, 'whatsapp_sent' => $row->whatsapp_sent],
            ]);
        }
        if ($table === 'whatsapp_messages') {
            if ($row->direction !== 'outbound') return null;
            $conversation = DB::table('whatsapp_conversations')->find($row->whatsapp_conversation_id);
            return array_merge($base, [
                'channel' => 'whatsapp', 'provider' => 'meta', 'recipient' => (string) ($conversation->phone ?? 'unknown'),
                'message' => $row->body ?? '', 'admin_id' => $row->sent_by_admin_id ?? null,
                'status' => in_array($row->status, ['delivered','read','failed'], true) ? $row->status : 'accepted',
                'provider_message_id' => $row->meta_message_id, 'error' => $row->error_message ?? null,
                'delivered_at' => $row->delivered_at ?? null, 'read_at' => $row->read_at ?? null,
                'context' => ['conversation_id' => $row->whatsapp_conversation_id],
            ]);
        }
        return null;
    }
}
