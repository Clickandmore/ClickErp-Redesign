<?php

use App\Http\Controllers\admin\HourlyRentalSettingsController;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::prefix('api')->middleware('api')->group(__DIR__.'/hourly-api.php');

Route::middleware('web')->group(function () {
    Route::group([
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
    ], function () {
        Route::prefix('dev-super-admin')->name('dev-superadmin.')->middleware('dev.superadmin')->group(function () {
            Route::get('hourly-app-review-accounts', [\App\Http\Controllers\admin\HourlyAppReviewAccountsController::class, 'index'])->name('hourly-app-review-accounts.index');
            Route::put('hourly-app-review-accounts', [\App\Http\Controllers\admin\HourlyAppReviewAccountsController::class, 'update'])->name('hourly-app-review-accounts.update');
            Route::get('hourly-rental-settings', [HourlyRentalSettingsController::class, 'index'])->name('hourly-rental-settings.index');
            Route::put('hourly-rental-settings', [HourlyRentalSettingsController::class, 'update'])->name('hourly-rental-settings.update');
        });
        Route::prefix('admin')->middleware(['admin', 'check_system_subscription', 'admin.route.permission'])->group(__DIR__.'/hourly-admin.php');
        Route::middleware(['admin', 'check_system_subscription'])->group(function () {
            require __DIR__.'/hourly-booking.php';
            require __DIR__.'/hourly-visits.php';
        });
        Route::name('web.')->middleware(['check_system_subscription', 'public_site_enabled'])->group(__DIR__.'/hourly-web.php');
        require __DIR__.'/hourly-driver.php';
        require __DIR__.'/hourly-maid.php';
    });
});
