<?php

namespace App\Console\Commands;

use App\Models\RentalContract;
use App\Services\RentalContractExpiryReminderService;
use Illuminate\Console\Command;

class SendRentalContractExpiryReminders extends Command
{
    protected $signature = 'rental-contracts:send-expiry-reminders
                            {--type=all : Notification type: advance, expired, or all}
                            {--dry-run : Inspect settings and eligible contracts without creating reminders or sending messages}';

    protected $description = 'Send rental contract expiry reminders to customers before contract end date.';

    public function handle(RentalContractExpiryReminderService $reminderService): int
    {
        $targetDate = now()->addDays($reminderService->reminderDays())->toDateString();
        $today = now()->toDateString();
        $sent = 0;
        $eligible = 0;

        $type = strtolower((string) $this->option('type'));
        if (!in_array($type, ['advance', 'expired', 'all'], true)) {
            $this->error('Invalid notification type. Use advance, expired, or all.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run: no reminders will be created and no messages will be sent.');
            $this->line('Timezone: ' . config('app.timezone') . '; now: ' . now()->toDateTimeString());
            $this->line('Customer reminders enabled: ' . ($reminderService->isEnabled() ? 'yes' : 'no'));
            $this->line('Channels: ' . $reminderService->channel() . '; lead days: ' . $reminderService->reminderDays());
            $this->line('SMS configured: ' . (\App\Support\CommunicationSettings::smsIsConfigured() ? 'yes' : 'no'));
            $this->line('WhatsApp configured: ' . (app(\App\Services\WhatsApp\WhatsAppMessagingService::class)->isConfigured() ? 'yes' : 'no'));
        }

        if (!$reminderService->isEnabled()) {
            $this->warn('Customer reminders disabled: enable rent_contract_expiry, select client/both and SMS/WhatsApp in notification alert settings.');

            return self::SUCCESS;
        }

        $notificationDates = match ($type) {
            'advance' => ['advance' => $targetDate],
            'expired' => ['expired' => $today],
            default => ['advance' => $targetDate, 'expired' => $today],
        };

        foreach ($notificationDates as $notificationType => $date) {
            RentalContract::query()
                ->with(['user', 'info', 'marketer'])
                ->eligibleForExpiryNotifications()
                ->whereHas('info', function ($query) use ($date, $today) {
                    // Catch up missed advance runs, but never send an advance notice after expiry.
                    $query->whereDate('ingaz_to', '<=', $date)
                        ->whereDate('ingaz_to', '>=', $today);
                })
                ->whereDoesntHave('expiryReminders', function ($query) use ($notificationType) {
                    $query->whereHas('rentalContract.info', function ($info) {
                        $info->whereRaw('DATE(' . $info->getModel()->qualifyColumn('ingaz_to') . ') = DATE(rental_contract_expiry_reminders.expires_on)');
                    })->where('notification_type', $notificationType)
                        ->where(function ($query) {
                            $query->where('sms_sent', true)->orWhere('whatsapp_sent', true)->orWhereNotNull('responded_at');
                        });
                })
                ->chunkById(100, function ($contracts) use ($reminderService, $notificationType, &$sent, &$eligible) {
                    foreach ($contracts as $contract) {
                        $eligible++;
                        if ($this->option('dry-run')) {
                            $this->line("Eligible {$notificationType}: contract #{$contract->id}; expires " . $contract->info->ingaz_to->toDateString()
                                . '; client record: ' . ($contract->user ? 'yes' : 'no')
                                . '; phone present: ' . (trim((string) $contract->user?->phone) !== '' ? 'yes' : 'no'));
                            continue;
                        }
                        $reminder = $reminderService->createAndSendReminder($contract, $notificationType);
                        if ($reminder && ($reminder->sms_sent || $reminder->whatsapp_sent)) {
                            $sent++;
                        } else {
                            $this->warn("Contract #{$contract->id}: " . ($reminder?->send_error ?: 'No message accepted; check client and contract details.'));
                        }
                    }
                });
        }

        $this->info($this->option('dry-run')
            ? "Eligible {$type} notifications: {$eligible}; no messages sent."
            : "Rental contract {$type} notifications accepted by provider: {$sent}; eligible: {$eligible}");

        return self::SUCCESS;
    }
}
