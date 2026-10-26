<?php

use App\Http\Controllers\Hourly\DriverAuthController;
use App\Http\Controllers\Hourly\DriverPortalController;
use App\Http\Middleware\EnsureHourlyDriverAccess;
use Illuminate\Support\Facades\Route;

Route::prefix('hourly-driver')->name('hourly-driver.')->middleware('feature.hourly_rental')->group(function () {
    Route::get('login', [DriverAuthController::class, 'loginForm'])->name('login');
    Route::post('login', [DriverAuthController::class, 'webLogin'])->middleware('throttle:5,1')->name('authenticate');
    Route::middleware(EnsureHourlyDriverAccess::class.':web')->group(function () {
        Route::get('/', [DriverPortalController::class, 'index'])->name('home');
        Route::get('cash/{collection}/receipt',[\App\Http\Controllers\Hourly\CashCollectionController::class,'driverReceipt'])->whereNumber('collection')->name('cash.receipt');
        Route::post('visits/{visit}/collect-cash',[\App\Http\Controllers\Hourly\CashCollectionController::class,'driverWebCollect'])->whereNumber('visit')->name('cash.collect');
        Route::post('location', [\App\Http\Controllers\Hourly\LocationPingController::class, 'driverWeb'])->middleware('throttle:60,1')->name('location');
        Route::post('visits/{visit}/incidents',[\App\Http\Controllers\Hourly\ServiceIncidentController::class,'driverWeb'])->whereNumber('visit')->middleware('throttle:10,1')->name('visits.incidents');
        Route::post('visits/{visit}/action', [DriverPortalController::class, 'action'])->whereNumber('visit')->name('visits.action');
        Route::post('logout', [DriverAuthController::class, 'webLogout'])->name('logout');
    });
});
