<?php

namespace App\Support;

use App\Models\OutboundMessageLog;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/** Transport-level audit. Audit failures must never interrupt a business message. */
class OutboundMessageRecorder
{
    public static function source(): string
    {
        $command = app(\App\Support\Audit\ExecutionContext::class)->command();
        if ($command) return $command;
        if (request()->route()) return request()->route()->getName() ?: request()->route()->uri();
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 18) as $frame) {
            $class = $frame['class'] ?? '';
            if (str_starts_with($class, 'App\\') && !in_array($class, [self::class, SmsGateway::class], true)
                && !str_contains($class, 'WhatsApp') && !str_contains($class, 'LoggingMailTransport')) {
                return $class . '::' . ($frame['function'] ?? '');
            }
        }
        return 'system';
    }

    public static function sensitive(string $message, array $context = []): bool
    {
        $hints = array_intersect_key($context, array_flip(['source', 'purpose', 'template_name']));
        return !empty($context['verification']) || preg_match('/otp|verification|verify|password|reset|staff_login|رمز|تحقق|كلمة المرور/i', $message . ' ' . implode(' ', array_filter($hints, 'is_scalar'))) === 1;
    }

    public static function clean($value)
    {
        if (is_array($value)) {
            $safe = [];
            foreach ($value as $key => $item) {
                $safe[$key] = preg_match('/token|secret|password|api.?key|authorization|otp|cookie|body|content|parameters/i', (string) $key)
                    ? '[محجوب]' : self::clean($item);
            }
            return $safe;
        }
        if (!is_string($value)) return $value;
        $value = preg_replace('~https?://[^\s<>"\']+~u', '[رابط محجوب]', $value);
        $value = preg_replace('/(Bearer\s+)[^\s]+/i', '$1[محجوب]', $value);
        return Str::limit($value, 12000, '…');
    }

    public static function begin(string $channel, string $recipient, string $message, string $provider, array $context = []): ?OutboundMessageLog
    {
        try {
            if (!Schema::hasTable('outbound_message_logs')) return null;
            $source = (string) ($context['source'] ?? self::source());
            $sensitive = self::sensitive($message, $context + ['source' => $source]);
            $admin = auth('admin')->user();
            $client = !empty($context['user_id']) ? \App\Models\ingaz\User::withTrashed()->find($context['user_id']) : null;
            if (!$client && in_array($channel, ['sms', 'whatsapp'], true)) {
                $digits = preg_replace('/\D/', '', $recipient);
                if (strlen($digits) >= 9) {
                    $candidates = [$recipient, $digits, '+' . $digits];
                    if (str_starts_with($digits, '966') || (strlen($digits) === 10 && str_starts_with($digits, '05')) || (strlen($digits) === 9 && str_starts_with($digits, '5'))) {
                        $lastNine = substr($digits, -9);
                        $candidates = array_merge($candidates, [$lastNine, '0' . $lastNine, '966' . $lastNine, '+966' . $lastNine]);
                    }
                    $matches = \App\Models\ingaz\User::withTrashed()->whereIn('phone', array_unique($candidates))->limit(2)->get();
                    if ($matches->count() === 1) $client = $matches->first();
                }
            }
            return OutboundMessageLog::create([
                'channel' => $channel, 'provider' => $provider, 'recipient' => Str::limit($recipient, 255, ''),
                'recipient_name' => $context['recipient_name'] ?? $client?->name,
                'admin_id' => $context['admin_id'] ?? $admin?->id,
                'branch_id' => $context['branch_id'] ?? $client?->branch_id ?? $admin?->branch_id,
                'source' => Str::limit($source, 255, ''), 'purpose' => $sensitive ? 'verification' : 'message',
                'message' => $sensitive ? '[محتوى التحقق / استعادة الحساب محجوب]' : self::clean($message),
                'context' => self::clean(array_intersect_key($context, array_flip([
                    'source', 'contract_id', 'order_id', 'reminder_id', 'conversation_id', 'user_id', 'admin_id', 'branch_id', 'alert_key', 'template_name', 'language', 'sender_key', 'sender', 'batch_id', 'notification_id',
                ])) + ['command' => app(\App\Support\Audit\ExecutionContext::class)->command()]),
            ]);
        } catch (\Throwable $e) {
            logger()->error('Message audit could not be written.', ['exception_class' => get_class($e)]);
            return null;
        }
    }

    public static function finish(?OutboundMessageLog $log, string $status, array $result = []): void
    {
        if (!$log) return;
        try {
            $response = $result['response'] ?? [];
            if (!is_array($response)) $response = ['message' => (string) $response];
            $error = $result['error_message'] ?? data_get($response, 'error.message') ?? data_get($response, 'message') ?? $result['error'] ?? null;
            $messageId = $result['message_id'] ?? data_get($response, 'messages.0.id') ?? ($response['messageId'] ?? $response['id'] ?? null);
            $log->update([
                'status' => $status,
                'http_status' => $result['status_code'] ?? null,
                'provider_message_id' => is_scalar($messageId) ? Str::limit((string) $messageId, 255, '') : null,
                'error' => in_array($status, ['failed', 'skipped'], true) ? self::clean((string) ($error ?: 'لم يؤكد المزوّد قبول الرسالة.')) : null,
                'provider_response' => $log->purpose === 'verification'
                    ? ['redacted' => true, 'error_code' => self::clean($result['error'] ?? null)] : self::clean($response),
                'accepted_at' => in_array($status, ['accepted', 'sent', 'delivered', 'read'], true) ? now() : null,
                'failed_at' => $status === 'failed' ? now() : null,
                'delivered_at' => in_array($status, ['delivered','read'], true) ? now() : null,
            ]);
            if ($log->purpose === 'verification') {
                // Keep the diagnostic reason, but remove codes echoed in a rejected OTP body.
                $safeError = self::clean((string) ($result['safe_error'] ?? $error ?? 'تعذر الإرسال؛ راجع إعدادات القناة.'));
                $safeError = preg_replace('/(?<!\p{N})\p{N}{4,8}(?!\p{N})/u', '[محجوب]', $safeError);
                $log->update(['error' => in_array($status, ['failed','skipped'], true) ? $safeError : null]);
            }
        } catch (\Throwable $e) {
            logger()->error('Message audit result could not be written.', ['exception_class' => get_class($e)]);
        }
    }

    public static function attempt(string $channel, string $to, string $message, string $provider, callable $send, array $context = []): array
    {
        $log = self::begin($channel, $to, $message, $provider, $context);
        try {
            $result = $send();
            self::finish($log, !empty($result['success']) ? 'accepted' : (($result['error'] ?? '') === 'not_configured' ? 'skipped' : 'failed'), $result);
            return $result;
        } catch (\Throwable $e) {
            self::finish($log, 'failed', ['error_message' => $e->getMessage()]);
            throw $e;
        }
    }

    public static function smsAccepted($raw, string $recipient = ''): bool
    {
        $data = is_array($raw) ? $raw : json_decode((string) $raw, true);
        if (!is_array($data)) return in_array(strtolower(trim((string) $raw)), ['1', '200', 'success', 'sent', 'accepted'], true);
        if (array_key_exists('success', $data)) return filter_var($data['success'], FILTER_VALIDATE_BOOLEAN);
        $digits = preg_replace('/\D/', '', $recipient);
        if (isset($data['rejected']) && !empty($data['rejected'])) {
            if (!$digits || str_contains(json_encode($data['rejected']), $digits)) return false;
            if (empty($data['accepted'])) return false;
        }
        if (array_key_exists('accepted', $data)) return !empty($data['accepted']) && (!$digits || str_contains(json_encode($data['accepted']), $digits));
        if (isset($data['code'])) return in_array((string) $data['code'], ['1', '200', 'M0000'], true);
        return in_array((int) ($data['statusCode'] ?? 0), [200, 201, 202], true);
    }

    public static function whatsAppStatus(string $messageId, string $state, array $payload): void
    {
        try {
            if (!Schema::hasTable('outbound_message_logs')) return;
            $ranks = ['pending' => 0, 'accepted' => 1, 'sent' => 2, 'delivered' => 3, 'read' => 4, 'failed' => 5];
            foreach (OutboundMessageLog::withoutBranchScope()->where('channel', 'whatsapp')->where('provider', 'meta')->where('provider_message_id', $messageId)->get() as $log) {
                if ($state !== 'failed' && ($ranks[$state] ?? 0) <= ($ranks[$log->status] ?? 0)) continue;
                $at = isset($payload['timestamp']) && is_numeric($payload['timestamp']) ? \Carbon\Carbon::createFromTimestamp((int) $payload['timestamp']) : now();
                $updates = ['status' => $state];
                if ($state === 'delivered' || $state === 'read') $updates['delivered_at'] = $log->delivered_at ?: $at;
                if ($state === 'read') $updates['read_at'] = $at;
                if ($state === 'failed') {
                    $updates['failed_at'] = $at;
                    $updates['error'] = $log->purpose === 'verification' ? 'فشل التسليم لدى المزوّد.' : self::clean((string) (data_get($payload, 'errors.0.error_data.details') ?: data_get($payload, 'errors.0.message', 'فشل التسليم لدى المزوّد.')));
                    $updates['provider_response'] = $log->purpose === 'verification' ? ['error_code' => data_get($payload, 'errors.0.code')] : self::clean(['errors' => $payload['errors'] ?? []]);
                }
                $log->update($updates);
            }
        } catch (\Throwable $e) {
            logger()->error('Message delivery audit could not be updated.', ['exception_class' => get_class($e)]);
        }
    }
}
