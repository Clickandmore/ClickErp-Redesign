<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class HourlyAutoCompleteVisits extends Command
{
    protected $signature = 'hourly:auto-complete-visits';
    protected $description = 'Legacy command: visit completion requires recorded execution, never elapsed time';
    public const OPEN_STATUSES = [];

    public function handle(): int
    {
        // Retain compatibility with existing schedulers without manufacturing performed service.
        $this->info('Automatic completion is disabled. Missed visits require rescheduling or cancellation.');
        return self::SUCCESS;
    }
}
