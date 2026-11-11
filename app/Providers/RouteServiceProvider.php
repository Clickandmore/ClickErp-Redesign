<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Traits\LoadsTranslatedCachedRoutes;

class RouteServiceProvider extends ServiceProvider
{
    use LoadsTranslatedCachedRoutes;

    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    protected $namespace = 'App\\Http\\Controllers';
    protected $worldedge_namespace = 'App\Http\Controllers\ingaz';


    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/admin.php'));

            Route::middleware('web')
                ->group(base_path('routes/ingaz.php'));

            Route::middleware('web')
                ->group(base_path('routes/agent.php'));

            Route::middleware('web')
                ->group(base_path('routes/office.php'));

            Route::middleware('web')
                ->group(base_path('routes/site.php'));

            // أوامر الصيانة (migrate / cache / key …) للمطوّر فقط؛ كانت متاحة لأي زائر دون تسجيل دخول
            Route::middleware(['web', 'dev.superadmin'])
                ->namespace($this->namespace)
                ->group(base_path('routes/commands.php'));

            Route::group([], base_path('routes/mcp.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        // يعمل قبل التحقق من التوكن، فيحدّ محاولات التخمين أيضاً.
        RateLimiter::for('mcp', function (Request $request) {
            $key = $request->bearerToken() ? hash('sha256', $request->bearerToken()) : $request->ip();

            return Limit::perMinute(\App\Support\McpSettings::rateLimitPerMinute())->by('mcp:' . $key);
        });

        // تسجيل العملاء والتوكن وشاشة الموافقة: حد لكل IP يكفي للربط ويوقف الإغراق.
        // واجهات البوت: كل الطلبات تأتي من عنوان البوت نفسه، فالحد على القيمة المطلوبة لا على العنوان
        RateLimiter::for('bot-contract-status', function (Request $request) {
            $value = (string) ($request->input('search_value') ?? $request->input('inpuut_id') ?? $request->input('inpuut_passport_id') ?? '');

            return Limit::perMinute(10)->by('bot-status:' . hash('sha256', $value));
        });

        RateLimiter::for('bot-store-ticket', function (Request $request) {
            // يمنع إغراق نفس الرقم برسائل SMS
            return Limit::perMinute(3)->by('bot-ticket:' . hash('sha256', (string) $request->input('phone')));
        });

        RateLimiter::for('mcp-oauth', function (Request $request) {
            return Limit::perMinute(30)->by('mcp-oauth:' . $request->ip());
        });
    }
}
