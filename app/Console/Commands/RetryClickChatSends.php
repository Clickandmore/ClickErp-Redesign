<?php

namespace App\Console\Commands;

use App\Models\OutboundMessageLog;
use App\Models\WhatsAppSendRetry;
use App\Services\WhatsApp\ClickChatWhatsAppService;
use App\Support\OutboundMessageRecorder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Re-sends Click Live Chat messages refused for a temporary reason (rate limit
 * or platform down) after 1, 5 and 15 minutes. After the last try the message
 * goes through the backup channel when one is set, otherwise it is marked failed.
 * Only refusals are retried: a timeout may hide a sent message, so it never is.
 */
class RetryClickChatSends extends Command
{
    /** Minutes to wait after the 1st and 2nd retry; the first retry is set one minute after the send. */
    private const BACKOFF_MINUTES = [1 => 5, 2 => 15];

    private const MAX_RETRIES = 3;

    protected $signature = 'whatsapp:clickchat-retry {--limit=30}';

    protected $description = 'Retry Click Live Chat WhatsApp sends that were refused for a temporary reason.';

    public function handle(ClickChatWhatsAppService $clickChat): int
    {
        if (!Schema::hasTable('whatsapp_send_retries')) {
            return self::SUCCESS;
        }

        $done = 0;
        WhatsAppSendRetry::query()
            ->where('status', 'pending')
            ->where('next_attempt_at', '<=', now())
            ->orderBy('next_attempt_at')
            ->limit(max(1, (int) $this->option('limit')))
            ->get()
            ->each(function (WhatsAppSendRetry $retry) use ($clickChat, &$done) {
                // Claim the row so two overlapping runs never send it twice.
                if (!WhatsAppSendRetry::query()->whereKey($retry->id)->where('status', 'pending')->update(['status' => 'processing'])) {
                    return;
                }
                $this->process($retry->refresh(), $clickChat);
                $done++;
            });

        $this->info("Processed {$done} retry(ies).");

        return self::SUCCESS;
    }

    private function process(WhatsAppSendRetry $retry, ClickChatWhatsAppService $clickChat): void
    {
        $context = (array) $retry->context + ['_retry' => true];
        $result = $clickChat->deliver($retry->recipient, (string) $retry->message, $context);
        $attempts = $retry->attempts + 1;
        $log = $retry->outbound_message_log_id
            ? OutboundMessageLog::withoutBranchScope()->find($retry->outbound_message_log_id) : null;

        if (!empty($result['success'])) {
            OutboundMessageRecorder::finish($log, 'accepted', $result);
            $retry->update(['status' => 'sent', 'attempts' => $attempts, 'last_error' => null]);
            return;
        }

        $error = (string) data_get($result, 'response.message', $result['error'] ?? '');
        $temporary = in_array($result['error'] ?? '', ClickChatWhatsAppService::RETRYABLE_ERRORS, true);
        if ($temporary && $attempts < self::MAX_RETRIES) {
            $retry->update(['status' => 'pending', 'attempts' => $attempts, 'last_error' => $error,
                'next_attempt_at' => now()->addMinutes(self::BACKOFF_MINUTES[$attempts] ?? 15)]);
            return;
        }

        if ($clickChat->canFallback($result, $context)) {
            $fallback = $clickChat->fallback($retry->recipient, (string) $retry->message, $context);
            if (!empty($fallback['success'])) {
                OutboundMessageRecorder::finish($log, 'failed', $result + ['error_message' => 'فشل عبر Click Live Chat بعد '
                    . $attempts . ' محاولات، وأُرسلت عبر القناة الاحتياطية (' . $fallback['fallback_provider'] . ').']);
                $retry->update(['status' => 'fallback', 'attempts' => $attempts, 'last_error' => $error]);
                return;
            }
        }

        OutboundMessageRecorder::finish($log, 'failed', $result + ['error_message' => 'فشل بعد ' . $attempts . ' محاولات: ' . $error]);
        $retry->update(['status' => 'failed', 'attempts' => $attempts, 'last_error' => $error]);
    }
}
