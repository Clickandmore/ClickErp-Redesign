<?php
namespace App\Console\Commands;

use App\Models\Hourly\HourlyVisit;
use App\Services\Hourly\VisitSearch;
use App\Support\HourlyRentalSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Cache, Schema};

class HourlyPastVisitReminder extends Command
{
    public const CACHE_KEY = 'hourly.past_visit_reminder';
    protected $signature = 'hourly:past-visit-reminder';
    protected $description = 'Refresh the daily admin reminder for past visits requiring operational review';

    public function handle(): int
    {
        HourlyRentalSettings::flush();
        if (!HourlyRentalSettings::isEnabled() || !Schema::hasTable('hourly_visits')) {
            Cache::forget(self::CACHE_KEY);
            return self::SUCCESS;
        }
        $query = HourlyVisit::where('scheduled_end_at', '<', now())->whereNotIn('status', VisitSearch::CLOSED)
            ->whereHas('order', fn($q) => $q->whereNotIn('status', ['cancelled','expired']));
        $count = (clone $query)->count();
        if (!$count) {
            Cache::forget(self::CACHE_KEY);
        } else {
            Cache::put(self::CACHE_KEY, ['count'=>$count, 'oldest_end_at'=>(clone $query)->min('scheduled_end_at'),
                'reviewed_at'=>now()->toIso8601String()], now()->addDays(2));
        }
        $this->info('Past visits requiring review: '.$count.'. No visit or shelter movement was changed.');
        return self::SUCCESS;
    }
}
