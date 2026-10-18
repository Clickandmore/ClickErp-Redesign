<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Services\Hourly\ServiceTime;
use App\Support\HourlyRentalSettings;
class HourlyServiceTimeChecks extends Command
{
    protected $signature='hourly:service-time';
    protected $description='Remind customers before service ends and record unapproved overruns without charging';
    public function handle(ServiceTime $service): int {
        if(!HourlyRentalSettings::isEnabled() || !ServiceTime::ready())return self::SUCCESS;
        $this->info('Checked '.$service->scan().' worker service windows. No automatic charges.');return self::SUCCESS;
    }
}
