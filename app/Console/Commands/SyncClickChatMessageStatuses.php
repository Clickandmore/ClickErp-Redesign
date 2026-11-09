<?php

namespace App\Console\Commands;

use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\ClickChatAlertService;
use App\Services\WhatsApp\ClickChatMessageStore;
use App\Services\WhatsApp\ClickChatWhatsAppService;
use App\Services\WhatsApp\WhatsAppMessagingService;
use Illuminate\Console\Command;

/**
 * Backup for lost webhooks: asks the platform for messages stuck on
 * queued/sent for more than an hour. A message may stay "delivered" for good
 * when the customer turned off read receipts; that is expected.
 */
class SyncClickChatMessageStatuses extends Command
{
    protected $signature = 'whatsapp:clickchat-sync-statuses {--limit=100}';

    protected $description = 'Refresh the status of Click Live Chat WhatsApp messages that did not get a webhook update.';

    public function handle(WhatsAppMessagingService $messaging, ClickChatWhatsAppService $clickChat, ClickChatMessageStore $store): int
    {
        if ($messaging->driver() !== 'clickchat' || !$clickChat->isConfigured()) {
            $this->line('Click Live Chat is not the active WhatsApp provider; nothing to do.');

            return self::SUCCESS;
        }

        $this->checkHealth($clickChat);

        $checked = 0;
        $updated = 0;
        WhatsAppMessage::query()
            ->where('provider', ClickChatMessageStore::PROVIDER)
            ->where('direction', 'outbound')
            ->whereIn('status', ['queued', 'sent'])
            ->whereNotNull('platform_message_id')
            ->where('updated_at', '<=', now()->subHour())
            ->where('created_at', '>=', now()->subDays(3))
            ->orderBy('id')
            ->limit(max(1, (int) $this->option('limit')))
            ->get(['id', 'platform_message_id', 'status'])
            ->each(function (WhatsAppMessage $message) use ($clickChat, $store, &$checked, &$updated) {
                $checked++;
                $remote = $clickChat->message((int) $message->platform_message_id);
                if (!$remote || empty($remote['status'])) {
                    return;
                }
                if ((string) $remote['status'] !== (string) $message->status) {
                    $updated++;
                }
                $store->applyStatus((int) $message->platform_message_id, (string) $remote['status'], $remote['error'] ?? null);
                // Touch the row so it waits another hour before the next check.
                WhatsAppMessage::query()->whereKey($message->id)->update(['updated_at' => now()]);
            });

        $this->info("Checked {$checked} message(s), {$updated} changed.");

        return self::SUCCESS;
    }

    /** Alert the chosen admins when the key is refused, the platform is down or a sending number is offline. */
    private function checkHealth(ClickChatWhatsAppService $clickChat): void
    {
        $alerts = app(ClickChatAlertService::class);
        $status = $clickChat->connectionStatus();
        if (empty($status['success'])) {
            ($status['error'] ?? '') === 'auth_error'
                ? $alerts->credentialsRejected((string) data_get($status, 'response.message', ''))
                : $alerts->platformUnreachable();
            return;
        }

        $inUse = array_filter(array_merge(
            [$clickChat->defaultChannelAccountId()],
            array_map('intval', array_values((array) config('services.whatsapp.clickchat.branch_senders', [])))
        ));
        $offline = array_values(array_filter($status['channels'],
            fn (array $channel) => in_array($channel['id'], $inUse, true) && $channel['status'] !== 'active'));
        if ($offline !== []) {
            $alerts->numbersDisconnected($offline);
        }
    }
}
