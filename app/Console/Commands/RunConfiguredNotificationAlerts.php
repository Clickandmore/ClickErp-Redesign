<?php

namespace App\Console\Commands;

use App\Services\ConfiguredNotificationAlertService;
use Illuminate\Console\Command;

class RunConfiguredNotificationAlerts extends Command
{
    protected $signature = 'notifications:run-configured-alerts';

    protected $description = 'Run enabled notification rules with their selected templates and channels.';

    public function handle(ConfiguredNotificationAlertService $alerts): int
    {
        $result = $alerts->run();

        $this->info(
            "Configured alerts processed: {$result['processed']}; sent: {$result['sent']}; failed: {$result['failed']}."
        );

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
