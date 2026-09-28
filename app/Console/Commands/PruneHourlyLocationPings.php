<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Schema};

class PruneHourlyLocationPings extends Command
{
    protected $signature = 'hourly:prune-location-pings {--days=30 : Number of days to retain detailed location points}';
    protected $description = 'Delete expired hourly-rental location points';

    public function handle(): int
    {
        if (!Schema::hasTable('location_pings')) return self::SUCCESS;

        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);
        $deleted = 0;

        do {
            $ids = DB::table('location_pings')->where('recorded_at', '<', $cutoff)
                ->orderBy('id')->limit(5000)->pluck('id');
            $count = $ids->isEmpty() ? 0 : DB::table('location_pings')->whereIn('id', $ids)->delete();
            $deleted += $count;
        } while ($count === 5000);

        $this->info("Deleted {$deleted} expired location point(s). Retention: {$days} days.");
        return self::SUCCESS;
    }
}
