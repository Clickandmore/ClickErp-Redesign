<?php

use App\Http\Controllers\admin\office\OfficeController;
use App\Http\Controllers\admin\office\OfficeFileController;
use App\Http\Controllers\admin\office\OfficeManagementController;
use App\Http\Controllers\admin\office\OfficeStatusController;
use App\Http\Controllers\admin\office\ResetPasswordController;
use App\Http\Controllers\admin\office\SendEmailResetPasswordController;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
    ],
    function () {
        Route::group(["prefix" => "admin", "middleware" => ["auth:admin", "admin", "admin.route.permission"]], function () {

            Route::get('offices/{office}/login-as-agent', [OfficeController::class, 'loginAsAgent'])
                ->middleware('signed')
                ->name('offices.login-as-agent');
            Route::get('offices/users', [OfficeManagementController::class, 'users'])
                ->name('offices.users.index');
            Route::get('offices/files', [OfficeManagementController::class, 'files'])
                ->name('offices.files.index');
            Route::resource('offices', OfficeController::class);
            Route::resource('offices-status', OfficeStatusController::class);
            Route::resource('offices-file', OfficeFileController::class);
            Route::resource('send-email-reset-password', SendEmailResetPasswordController::class);
            Route::resource('reset-password', ResetPasswordController::class);

        });

    }
);
