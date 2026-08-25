<?php

namespace App\Providers;

use App\Models\Country;
use App\Models\Feature;
use App\Models\ingaz\AboutUs;
use App\Models\ingaz\Skill;
use App\Models\NewSettings;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Setting;
use App\Models\ingaz\DevelopSetting;
use App\Models\ingaz\FastNew;
use App\Support\CommunicationSettings;
use App\Support\ZohoSalesIqSettings;
use App\Validation\CustomValidator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
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
        Validator::resolver(function ($translator, $data, $rules, $messages, $attributes) {
            return new CustomValidator($translator, $data, $rules, $messages, $attributes);
        });
//        Register custom migration paths
        $migrationsPath = database_path('migrations');
        $directories = glob($migrationsPath . '/*', GLOB_ONLYDIR);
        $paths = array_merge([$migrationsPath], $directories);
        $this->loadMigrationsFrom($paths);

        // The developer control plane must remain reachable when the tenant
        // database is unavailable or only partially migrated. Nothing below
        // this point is needed by its standalone views.
        if (! $this->app->runningInConsole() && $this->isDevSuperAdminRequest()) {
            return;
        }

        CommunicationSettings::applyRuntimeConfig();

        if ($this->app->runningInConsole()) {
            if (filter_var(env('CLICKERP_SCHEDULER_RUN', false), FILTER_VALIDATE_BOOL)) {
                @ini_set('memory_limit', (string) config('scheduler.memory_limit', '192M'));
            }

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
        View::share('zohoSalesIqWidgetUrl', ZohoSalesIqSettings::publicWidgetUrl());


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

    private function isDevSuperAdminRequest(): bool
    {
        return request()->is(
            'superadmin',
            '*/superadmin',
            'dev-super-admin',
            'dev-super-admin/*',
            '*/dev-super-admin',
            '*/dev-super-admin/*'
        );
    }
}
