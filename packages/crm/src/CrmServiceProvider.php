<?php

namespace Nami\Crm;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class CrmServiceProvider extends ServiceProvider
{

    public function boot()
    {
        require_once __DIR__ . '/Helpers/CrmHtmlHelper.php';

//        $target = base_path('packages/hr/public');
//        $link = public_path('hr');
//        if (!file_exists($link)) {
//            symlink($target, $link);
//        }
        $this->mergeConfigFrom(__DIR__ . '/../config/crm.php', 'crm');

        $this->loadRoutesFrom(__DIR__ . '/../routes/crm.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'crm');

        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'crm');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/crm'),
        ], 'views');

        $this->publishes([
            __DIR__ . '/../lang' => resource_path('lang/vendor/crm'),
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
