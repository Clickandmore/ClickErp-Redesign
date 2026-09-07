<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        \Illuminate\Auth\Events\Login::class => [\App\Listeners\RecordAuthenticationActivity::class],
        \Illuminate\Auth\Events\Logout::class => [\App\Listeners\RecordAuthenticationActivity::class],
        \Illuminate\Auth\Events\Failed::class => [\App\Listeners\RecordAuthenticationActivity::class],
        \Spatie\Permission\Events\RoleAttached::class => [\App\Listeners\RecordPermissionActivity::class],
        \Spatie\Permission\Events\RoleDetached::class => [\App\Listeners\RecordPermissionActivity::class],
        \Spatie\Permission\Events\PermissionAttached::class => [\App\Listeners\RecordPermissionActivity::class],
        \Spatie\Permission\Events\PermissionDetached::class => [\App\Listeners\RecordPermissionActivity::class],
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton(\App\Support\Audit\ExecutionContext::class);
        Event::listen(\Illuminate\Console\Events\CommandStarting::class, function ($event) {
            app(\App\Support\Audit\ExecutionContext::class)->start($event->command);
        });
        Event::listen(\Illuminate\Console\Events\CommandFinished::class, function () {
            app(\App\Support\Audit\ExecutionContext::class)->finish();
        });
    }
}
