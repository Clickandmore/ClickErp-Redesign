<?php
namespace App\Console\Commands;

use App\Jobs\NotifyHourlyClosure;
use App\Models\Hourly\HourlyCategoryClosure;
use App\Support\HourlyRentalSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class HourlyClosureNotices extends Command
{
    protected $signature='hourly:closure-notices';
    protected $description='Notify existing bookings affected by service closures, without repeating delivered notices';
    public function handle(): int
    {
        if(!HourlyRentalSettings::isEnabled() || !Schema::hasTable('hourly_category_closures'))return self::SUCCESS;
        HourlyCategoryClosure::where('ends_at','>=',now()->startOfDay())->orderBy('id')->chunkById(100,function($closures){
            foreach($closures as $closure)NotifyHourlyClosure::dispatch($closure->id);
        });
        return self::SUCCESS;
    }
}
