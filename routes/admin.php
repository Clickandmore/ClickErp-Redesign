<?php

use App\Http\Controllers\web\HomeController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
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
/*
Route::get('/', function () {
    return view('welcome');
});*/


Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ["adminLocale",'localeSessionRedirect', 'localizationRedirect', 'localeViewPath']
    ], function () {
        //--------------------------------------------------------
        Route::get('dashboard', 'admin\\DashboardController@index')->name('dashboard.index')->middleware('admin');
        Route::get('dashboard-pro', 'admin\\DashboardController@professional')->name('dashboard.professional')->middleware('admin');
Route::get('dashboard-old', 'admin\\DashboardController@indexOld')->name('dashboard.old')->middleware('admin');
//        Route::get('/', 'admin\\auth\AdminController@formLogin')->name("admin.form.login");
        Route::group(["namespace" => "admin\\auth",'prefix' =>"dashboard"], function () {
            Route::get('login', 'AdminController@formLogin')->name("admin.form.login");
            Route::post('/login', 'AdminController@login')->name('admin.login');
            Route::get('/logout', 'AdminController@logout')->name('admin.logout');
        });
    Route::group(["middleware" => "admin",'prefix'=>"dashboard"], function () {

        Route::group(["namespace" => "admin"], function () {

            Route::group(["namespace" => "auth"], function () {
                Route::resource('profile', 'ProfileController');
                Route::resource('change-password', 'ChangePasswordController');
                Route::resource('admins', 'AdminController');
                Route::post('admins/{id}/toggle-status', 'AdminController@toggleStatus')->name('admins.toggle-status');
                Route::post('admins/{id}/restore', 'AdminController@restore')->name('admins.restore');
            });

        });
    });
        //--------------------------------------------------------
});
