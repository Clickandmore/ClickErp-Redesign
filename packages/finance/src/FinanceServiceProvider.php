<?php

namespace Nami\Finance;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Nami\Finance\Http\Middleware\CheckActiveYear;

class FinanceServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $helperFile = __DIR__ . '/Helpers/FinanceHtmlHelper.php';
        if (file_exists($helperFile)) {
            require_once $helperFile;
        }
        $this->mergeConfigFrom(__DIR__ . '/../config/finance.php', 'finance');

        $this->loadRoutesFrom(__DIR__ . '/../routes/finance.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/zatca.php');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'finance');

        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'finance');


        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/finance'),
        ], 'views');

        $this->publishes([
            __DIR__ . '/../lang' => resource_path('lang/vendor/finance'),
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
