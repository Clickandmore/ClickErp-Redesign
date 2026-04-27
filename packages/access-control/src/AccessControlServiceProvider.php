<?php

namespace Nami\AccessControl;

use Illuminate\Support\ServiceProvider;
use Nami\AccessControl\Console\Commands\AddPermissionCommand;
use Nami\AccessControl\Console\Commands\AddPermissionResourceCommand;
use Nami\AccessControl\Console\Commands\SyncPackagePermissionsCommand;

class AccessControlServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/laratrust_seeder.php', 'laratrust_seeder');
        $this->mergeConfigFrom(__DIR__ . '/../config/permission_registry.php', 'access_control_permission_registry');

        if ($this->app->runningInConsole()) {
            $this->commands([
                AddPermissionCommand::class,
                AddPermissionResourceCommand::class,
                SyncPackagePermissionsCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/access-control.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'access-control');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'access-control');
    }
}
