<?php

namespace App\Console\Commands;

use App\Models\RentalContract;
use App\Services\RentalContractExpiryReminderService;
use Illuminate\Console\Command;

class SendRentalContractExpiryReminders extends Command
{
    protected $signature = 'rental-contracts:send-expiry-reminders';

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

        RentalContract::query()
            ->with(['user', 'info', 'marketer'])
            ->whereIn('status', ['active', 'maid_delivered', 'contract_signed'])
            ->whereHas('info', function ($query) use ($today, $targetDate) {
                $query->whereNotNull('ingaz_to')
                    ->whereDate('ingaz_to', '>=', $today)
                    ->whereDate('ingaz_to', '<=', $targetDate);
            })
            ->whereDoesntHave('expiryReminders', function ($query) use ($today, $targetDate) {
                $query->whereDate('expires_on', '>=', $today)
                    ->whereDate('expires_on', '<=', $targetDate);
            })
            ->chunkById(100, function ($contracts) use ($reminderService, &$sent) {
                foreach ($contracts as $contract) {
                    $reminder = $reminderService->createAndSendReminder($contract);
                    if ($reminder && ($reminder->sms_sent || $reminder->whatsapp_sent)) {
                        $sent++;
                    }
                }
            });

        $this->info("Rental contract expiry reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
