<?php

namespace App\Providers;

use App\Http\Middleware\EnsureHourlyRentalEnabled;
use App\Models\Hourly\HourlyDriver;
use App\Models\Hourly\HourlyMaidAccount;
use App\Services\Hourly\ShelterPresence;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

/** All registrations are additive and namespaced to the hourly module. */
class HourlyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ShelterPresence::class);
        foreach (['driver' => HourlyDriver::class, 'maid' => HourlyMaidAccount::class] as $role => $model) {
            $provider = 'hourly_'.$role.'s';
            config(['auth.providers.'.$provider => ['driver' => 'eloquent', 'model' => $model]]);
            config(['auth.guards.hourly_'.$role => ['driver' => 'session', 'provider' => $provider]]);
            config(['auth.guards.hourly_'.$role.'_api' => ['driver' => 'jwt', 'provider' => $provider]]);
        }
    }

    public function boot(): void
    {
        $this->app['router']->aliasMiddleware('feature.hourly_rental', EnsureHourlyRentalEnabled::class);
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class)->prependToMiddlewarePriority(EnsureHourlyRentalEnabled::class);
        $this->loadRoutesFrom(base_path('routes/hourly.php'));

        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\InstallHourlyModule::class,
                \App\Console\Commands\ExpireHourlyOrders::class,
                \App\Console\Commands\HourlyAutoCompleteVisits::class,
                \App\Console\Commands\HourlyRenewalReminders::class,
                \App\Console\Commands\HourlyVisitNotices::class,
                \App\Console\Commands\HourlyClosureNotices::class,
                \App\Console\Commands\PruneHourlyLocationPings::class,
            ]);
            $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
                $schedule->command('hourly:expire-orders')->dailyAt('00:30')->withoutOverlapping()->when(fn () => \App\Support\HourlyRentalSettings::isEnabled());
                $schedule->command('hourly:auto-complete-visits')->everyFiveMinutes()->withoutOverlapping()->when(fn () => \App\Support\HourlyRentalSettings::isEnabled());
                $schedule->command('hourly:visit-notices')->everyMinute()->withoutOverlapping()->when(fn () => \App\Support\HourlyRentalSettings::isEnabled());
                $schedule->command('hourly:closure-notices')->everyFiveMinutes()->withoutOverlapping()->when(fn () => \App\Support\HourlyRentalSettings::isEnabled());
                $schedule->command('hourly:renewal-reminders')->dailyAt('08:00')->withoutOverlapping()->when(fn () => \App\Support\HourlyRentalSettings::isEnabled());
                $schedule->command('hourly:prune-location-pings')->dailyAt('02:30')->withoutOverlapping()->when(fn () => \App\Support\HourlyRentalSettings::isEnabled());
            });
        }
    }
}
