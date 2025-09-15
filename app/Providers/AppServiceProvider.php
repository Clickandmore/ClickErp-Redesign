<?php

namespace App\Providers;


use App\Models\Contract;
use App\Models\NewSettings;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Setting;
use App\Models\ingaz\DevelopSetting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use App\Models\printSetting\PrintSetting;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(255);
//        Register custom migration paths
        $migrationsPath = database_path('migrations');
        $directories = glob($migrationsPath . '/*', GLOB_ONLYDIR);
        $paths = array_merge([$migrationsPath], $directories);
        $this->loadMigrationsFrom($paths);

        $new_settings = new NewSettings();
        $setting['bank_transfer_data'] = $new_settings->getSetting('bank_transfer_data');
        $setting['sadad_transfer_data'] = $new_settings->getSetting('sadad_transfer_data');
        $setting['show_popup'] = $new_settings->getSetting('show_popup') === '1';
        $setting['popup_image'] = $new_settings->getSetting('popup_image');
        //now make this setting vars accisible everywhere
        View::share('setting', $setting);


        View::composer('*', function ($view) {
            $settings = Setting::with('translations')->first();
            $view->with('settings', $settings);
        });
        View::composer('*', function ($view) {
            $jobTypes = Cache::remember('jobTypes', 60, function () {
                return JobType::with('translations')->get();
            });
            $view->with('jobTypes', $jobTypes);
        });
        View::composer('*', function ($view) {
            $whatsappNumber = Cache::remember('whatsappNumber', 60, function () {
                return getWhatsAppNumber();
            });
            $view->with('whatsappNumber', $whatsappNumber);
        });
        View::composer('*', function ($view) {
            $printSettings = Cache::remember('settings', 60, function () {
                return PrintSetting::with('printDetails')->first();
            });
            $view->with('printSettings', $printSettings);
        });
        View::composer('*', function ($view) {
            $developSettings = DevelopSetting::first(); // Assuming you have a row of settings with ID 1
            $view->with('developSettings', $developSettings);
        });

        // count contracts by status
        $contractsCount['new'] = Contract::where('status', 'new')->count();
        $contractsCount['msaned'] = Contract::where('status', 'msaned')->count();
        $contractsCount['tafwed'] = Contract::where('status', 'tafwed')->count();
        $contractsCount['taffez'] = Contract::where('status', 'taffez')->count();
        $contractsCount['tazker'] = Contract::where('status', 'tazker')->count();
        // العقود تحت الضمان = العقود المكتملة
        $contractsCount['under_warranty'] = Contract::where('status', 'completed')->count();
        $contractsCount['back'] = Contract::where('status', 'back')->count();
        $contractsCount['arrival'] = Contract::where('status', 'arrival')->count();
        $contractsCount['closed'] = Contract::where('status', 'closed')->count();
        $contractsCount['completed'] = Contract::where('status', 'completed')->count();
        $contractsCount['pending_maid_selection'] = Contract::where('status', 'pending_maid_selection')->count();
        $contractsCount['all'] = Contract::count();
        View::share('contractsCount', $contractsCount);
    }
}
