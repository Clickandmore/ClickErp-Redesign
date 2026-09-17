<?php

namespace App\Console\Commands;

use App\Models\RentalContract;
use App\Services\RentalContractExpiryReminderService;
use Illuminate\Console\Command;

class SendRentalContractExpiryReminders extends Command
{
    protected $signature = 'rental-contracts:send-expiry-reminders
                            {--type=all : Notification type: advance, expired, or all}';

    protected $description = 'Send rental contract expiry reminders to customers before contract end date.';

    public function handle(RentalContractExpiryReminderService $reminderService): int
    {
        if (!$reminderService->isEnabled()) {
            $this->info('Rental contract expiry reminders are disabled.');

            return self::SUCCESS;
        }

        $targetDate = now()->addDays($reminderService->reminderDays())->toDateString();
        $today = now()->toDateString();
        $sent = 0;

        $type = strtolower((string) $this->option('type'));
        if (!in_array($type, ['advance', 'expired', 'all'], true)) {
            $this->error('Invalid notification type. Use advance, expired, or all.');

            return self::FAILURE;
        }

        $notificationDates = match ($type) {
            'advance' => ['advance' => $targetDate],
            'expired' => ['expired' => $today],
            default => ['advance' => $targetDate, 'expired' => $today],
        };

        foreach ($notificationDates as $notificationType => $date) {
            RentalContract::query()
                ->with(['user', 'info', 'marketer'])
                ->whereIn('status', ['active', 'maid_delivered', 'contract_signed'])
                ->whereHas('info', fn ($query) => $query->whereDate('ingaz_to', $date))
                ->whereDoesntHave('expiryReminders', function ($query) use ($date, $notificationType) {
                    $query->whereDate('expires_on', $date)
                        ->where('notification_type', $notificationType);
                })
                ->chunkById(100, function ($contracts) use ($reminderService, $notificationType, &$sent) {
                foreach ($contracts as $contract) {
                    $reminder = $reminderService->createAndSendReminder($contract, $notificationType);
                    if ($reminder && ($reminder->sms_sent || $reminder->whatsapp_sent)) {
                        $sent++;
                    }
                }
            });
        }

        $this->info("Rental contract {$type} notifications sent: {$sent}");

        return self::SUCCESS;
    }
}
