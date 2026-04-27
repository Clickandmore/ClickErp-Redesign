<?php

namespace Nami\Hr;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Nami\Hr\Http\Middleware\CheckActiveYear;

class HrServiceProvider extends ServiceProvider
{

    public function boot()
    {
        require_once __DIR__ . '/Helpers/HrHtmlHelper.php';
        require_once __DIR__ . '/Helpers/HrConstantHelper.php';

//        $target = base_path('packages/hr/public');
//        $link = public_path('hr-assets');
//        if (!file_exists($link)) {
//            symlink($target, $link);
//        }
        $this->mergeConfigFrom(__DIR__ . '/../config/hr.php', 'hr');

        $this->loadRoutesFrom(__DIR__ . '/../routes/hr.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/employee.php');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'hr');

        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'hr');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/hr'),
        ], 'views');

        $this->publishes([
            __DIR__ . '/../lang' => resource_path('lang/vendor/hr'),
        ], 'lang');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'migrations');
    }


    public function register()
    {
        $this->registerNamespaces();
    }

    /**
     * Register package namespaces for autoload.
     */
    protected function registerNamespaces()
    {
        // تقدر تضيف bindings أو singletons هنا لو عايز
        // مثلاً:
        // $this->app->bind('AccountService', \Nami\Finance\Services\AccountService::class);
    }
}
