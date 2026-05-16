<?php

use App\Http\Controllers\Agent\AuthController;
use App\Http\Controllers\Agent\DashboardController;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
    ],
    function () {
        Route::prefix('agent')->name('agent.')->group(function () {
            Route::get('login', [AuthController::class, 'showLoginForm'])->name('login.form');
            Route::post('login', [AuthController::class, 'login'])->name('login');

            Route::middleware('agent')->group(function () {
                Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
                Route::get('maids', [DashboardController::class, 'maids'])->name('maids.index');
                Route::get('maids/create', [DashboardController::class, 'createMaid'])->name('maids.create');
                Route::post('maids/store', [DashboardController::class, 'storeMaid'])->name('maids.store');
                Route::post('maids/extract-cv', [DashboardController::class, 'extractCv'])->name('maids.extract-cv');
                Route::get('contracts', [DashboardController::class, 'contracts'])->name('contracts.index');
                Route::get('contracts/{contract}', [DashboardController::class, 'showContract'])->name('contracts.show');
                Route::get('external-office-dispatches', [DashboardController::class, 'dispatches'])->name('dispatches.index');
                Route::get('invoices', [DashboardController::class, 'invoices'])->name('invoices.index');
                Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            });
        });
    }
);
