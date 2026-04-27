<?php


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

// Route::get('/user', [UserController::class, 'index']);

use Nami\Finance\Http\Controllers\Zatca\ShareZatcaController;
use Nami\Finance\Http\Controllers\Zatca\ZatcaSettingController;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath']
    ], function () {
    Route::group(["middleware" => ["web","admin"], "prefix" => "zatca", "as" => "zatca."], function () {

        Route::resource('zatca-settings', ZatcaSettingController::class);
        Route::resource('share-invoice', ShareZatcaController::class);

    });

});


