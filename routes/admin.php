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
        Route::get('dashboard', 'admin\\DashboardController@index')->name('dashboard.index')->middleware(['admin', 'admin.route.permission']);
        Route::get('dashboard-pro', 'admin\\DashboardController@professional')->name('dashboard.professional')->middleware(['admin', 'admin.route.permission']);
        Route::get('dashboard-old', 'admin\\DashboardController@indexOld')->name('dashboard.old')->middleware(['admin', 'admin.route.permission']);
        Route::get('dashboard/employees/{employee}', 'admin\\DashboardController@employeeShow')->name('dashboard.employees.show')->middleware(['admin', 'admin.route.permission']);
        Route::get('admin/no-permissions', function () {
            return view('admin.no-permissions');
        })->name('admin.no-permissions')->middleware('admin');
//        Route::get('/', 'admin\\auth\AdminController@formLogin')->name("admin.form.login");
        Route::group(["namespace" => "admin\\auth",'prefix' =>"dashboard"], function () {
            Route::get('login', 'AdminController@formLogin')->name("admin.form.login");
            Route::post('/login', 'AdminController@login')->name('admin.login');
            Route::get('forgot-password', 'ForgotPasswordController@create')->name('admin.password.request');
            Route::post('forgot-password', 'ForgotPasswordController@store')->name('admin.password.email');
            Route::get('reset-password', 'ResetPasswordController@edit')->name('admin.password.reset');
            Route::post('reset-password', 'ResetPasswordController@update')->name('admin.password.update');
            Route::get('/logout', 'AdminController@logout')->name('admin.logout');
        });
    Route::group(["middleware" => ["admin", "admin.route.permission"],'prefix'=>"dashboard"], function () {
        Route::get('reports', function () {
            return redirect()->route('reports.index');
        })->name('dashboard.reports.index');

        Route::get('support/tickets', 'admin\SupportCenterController@tickets')->name('support.tickets');
        Route::get('support/tickets/create', 'admin\SupportCenterController@createTicket')->name('support.tickets.create');
        Route::post('support/tickets', 'admin\SupportCenterController@storeTicket')->name('support.tickets.store');
        Route::delete('support/attachments/{attachment}', 'admin\SupportCenterController@destroyAttachment')->name('support.attachments.destroy');
        Route::resource('support', 'admin\SupportCenterController')->parameters(['support' => 'article']);

        // تحديثات النظام — متاحة لكل موظف؛ الإدارة في لوحة Super Admin
        Route::get('system-updates', 'admin\SystemUpdatesController@index')->name('system-updates.index');
        Route::post('system-updates/acknowledge-all', 'admin\SystemUpdatesController@acknowledgeAll')->name('system-updates.acknowledge-all');
        Route::post('system-updates/{systemUpdate}/acknowledge', 'admin\SystemUpdatesController@acknowledge')->whereNumber('systemUpdate')->name('system-updates.acknowledge');

        Route::group(["namespace" => "admin"], function () {

            Route::group(["namespace" => "auth"], function () {
                Route::resource('profile', 'ProfileController');
                Route::resource('change-password', 'ChangePasswordController');
                Route::post('admins/{id}/reset-password', 'AdminController@resetPassword')->name('admins.reset-password');
                Route::post('admins/{id}/send-reset-link', 'AdminController@sendResetLink')->name('admins.send-reset-link');
                Route::post('admins/{id}/terminate-session', 'AdminController@terminateSession')->name('admins.terminate-session');
                Route::post('admins/{id}/impersonate', 'AdminController@impersonate')->name('admins.impersonate');
                // يجب أن يسبق resource حتى لا يلتقط admins/{admin} كلمة trashed
                Route::get('admins/trashed', 'AdminController@trashed')->name('admins.trashed');
                Route::resource('admins', 'AdminController');
                Route::post('admins/{id}/toggle-status', 'AdminController@toggleStatus')->name('admins.toggle-status');
                Route::post('admins/{id}/restore', 'AdminController@restore')->name('admins.restore');
                Route::delete('admins/{id}/force-delete', 'AdminController@forceDelete')->name('admins.force-delete');
                Route::post('admins/{id}/quick-assign-role', 'AdminController@quickAssignRole')->name('admins.quick-assign-role');
                Route::post('admins/{id}/quick-assign-branch', 'AdminController@quickAssignBranch')->name('admins.quick-assign-branch');
                Route::post('admins/{id}/quick-assign-office', 'AdminController@quickAssignOffice')->name('admins.quick-assign-office');
            });

        });
    });
        //--------------------------------------------------------
});
