<?php

namespace App\Providers;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\Country;
use App\Models\Feature;
use App\Models\ingaz\AboutUs;
use App\Models\ingaz\Skill;
use App\Models\NewSettings;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Setting;
use App\Models\ingaz\DevelopSetting;
use App\Models\ingaz\FastNew;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
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

        if ($this->app->runningInConsole()) {
            return;
        }

        $setting = [
            'bank_transfer_data' => null,
            'sadad_transfer_data' => null,
            'show_popup' => false,
            'popup_image' => null,
        ];

        if (Schema::hasTable('new_settings')) {
            $new_settings = new NewSettings();
            $setting['bank_transfer_data'] = $new_settings->getSetting('bank_transfer_data');
            $setting['sadad_transfer_data'] = $new_settings->getSetting('sadad_transfer_data');
            $setting['show_popup'] = $new_settings->getSetting('show_popup') === '1';
            $setting['popup_image'] = $new_settings->getSetting('popup_image');
        }
        View::share('setting', $setting);


        View::composer('*', function ($view) {
            $settings = Schema::hasTable('settings')
                ? Setting::with('translations')->first()
                : null;
            $view->with('settings', $settings);
        });
        View::composer('*', function ($view) {
            $jobTypes = Schema::hasTable('job_types')
                ? Cache::remember('jobTypes', 60, function () {
                    return JobType::with('translations')->get();
                })
                : collect();
            $view->with('jobTypes', $jobTypes);
        });
        View::composer('*', function ($view) {
            $whatsappNumber = Cache::remember('whatsappNumber', 60, function () {
                return getWhatsAppNumber();
            });
            $view->with('whatsappNumber', $whatsappNumber);
        });
        View::composer('*', function ($view) {
            $printSettings = Schema::hasTable('print_setting')
                ? Cache::remember('settings', 60, function () {
                    return PrintSetting::with('printDetails')->first();
                })
                : null;
            $view->with('printSettings', $printSettings);
        });
        View::composer('*', function ($view) {
            $developSettings = Schema::hasTable('develop_settings')
                ? DevelopSetting::first()
                : null;
            $view->with('developSettings', $developSettings);
        });
        View::composer('*', function ($view) {
            $skills = Schema::hasTable('skills') ? Skill::all() : collect();
            $view->with('skills', $skills);
        });
        View::composer('*', function ($view) {
            $countries = Schema::hasTable('countries') ? Country::all() : collect();
            $view->with('countries', $countries);
        });

        View::composer('*', function ($view) {
            $features = Feature::all(); // Assuming you have a row of settings with ID 1
            $view->with('features', $features);
        });

        View::composer('*', function ($view) {
            $abouts = AboutUs::first(); // Assuming you have a row of settings with ID 1
            $view->with('abouts', $abouts);
        });


        View::composer('*', function ($view) {
            $fastnews = Schema::hasTable('fast_news')
                ? FastNew::with('translations')->get()
                : collect();
            $view->with('fastnews', $fastnews);
        });

        // count contracts by status
        $contractsCount = [
            'new' => 0,
            'msaned' => 0,
            'tafwed' => 0,
            'taffez' => 0,
            'tazker' => 0,
            'under_warranty' => 0,
            'back' => 0,
            'arrival' => 0,
            'closed' => 0,
            'completed' => 0,
            'pending_maid_selection' => 0,
            'all' => 0,
        ];

        if (Schema::hasTable('contracts')) {
            // $contractsCount['new'] = Contract::where('status', 'new')->count();
            // $contractsCount['msaned'] = Contract::where('status', 'msaned')->count();
            // $contractsCount['tafwed'] = Contract::where('status', 'tafwed')->count();
            // $contractsCount['taffez'] = Contract::where('status', 'taffez')->count();
            // $contractsCount['tazker'] = Contract::where('status', 'tazker')->count();
            $contractsCount['under_warranty'] = Contract::where('status', 'arrival')->where('warranty_status', 'active')->count();
            $contractsCount['back'] = Contract::where('status', 'back')->count();
            // $contractsCount['arrival'] = Contract::where('status', 'arrival')->count();
            $contractsCount['closed'] = Contract::where('status', 'closed')->count();
            $contractsCount['pending_maid_selection'] = Contract::where('status', 'pending_maid_selection')->count();
            // $contractsCount['all'] = Contract::count();
            $contractsCount['are_valid'] = Contract::whereIn('status', [ContractStatusEnum::PendingMaidSelection, ContractStatusEnum::New, ContractStatusEnum::Msaned, ContractStatusEnum::Tafwed, ContractStatusEnum::Taffez, ContractStatusEnum::Tazker])->count();
            $contractsCount['completed'] = Contract::whereIn('status', [ContractStatusEnum::Completed, ContractStatusEnum::Arrival])->count();
        }

        View::share('contractsCount', $contractsCount);

        RateLimiter::for('register', function ($request) {
            return Limit::perMinute(1)->by($request->phone);
        });
        RateLimiter::for('login', function ($request) {
            return Limit::perMinute(1)->by($request->phone);
        });
        RateLimiter::for('daily-lebor-demand', function ($request) {
            return Limit::perMinute(1)->by($request->phone);
        });

    }
}
