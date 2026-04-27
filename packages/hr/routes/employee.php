<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Hr\ToggleStatusController;
use App\Http\Controllers\Hr\getAllowanceDataController;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Nami\Hr\Http\Controllers\Employee\AdvanceController;
use Nami\Hr\Http\Controllers\Employee\ComplaintReplyController;
use Nami\Hr\Http\Controllers\Employee\DailyWorkReportController;
use Nami\Hr\Http\Controllers\Employee\EmployeeComplaintController;
use Nami\Hr\Http\Controllers\Employee\EmployeeProfileController;
use Nami\Hr\Http\Controllers\Employee\FollowDailyReportController;
use Nami\Hr\Http\Controllers\Employee\HomeController;
use Nami\Hr\Http\Controllers\Employee\HrTaskReplyController;
use Nami\Hr\Http\Controllers\Employee\MyComplaintController;
use Nami\Hr\Http\Controllers\Employee\MyTaskController;
use Nami\Hr\Http\Controllers\Employee\PermissionController;
use Nami\Hr\Http\Controllers\Employee\RewadController;
use Nami\Hr\Http\Controllers\Employee\TaskController;
use Nami\Hr\Http\Controllers\Employee\VacationController;
use Nami\Hr\Http\Controllers\Employee\VacationReturnDateController;

/*
|--------------------------------------------------------------------------
| Finance Routes
|--------------------------------------------------------------------------
|
| Here is where you can register user routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "user" middleware group. Now create something great!
|
*/

Route::group(
    [
        'as' => 'employee.',
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ["adminLocale",'localeSessionRedirect', 'localizationRedirect', 'localeViewPath']
    ], function () {
    Route::group(["middleware" => "admin", "prefix" => "employee"], function () {

        Route::resource('advances', AdvanceController::class);
        Route::resource('vacations',VacationController::class);
        Route::resource('vacations-return-date', VacationReturnDateController::class);
        Route::resource('permissions', PermissionController::class);
        Route::resource('daily-work-reports', DailyWorkReportController::class);
        Route::resource('my-complaints', MyComplaintController::class);
        Route::resource('complaint-replies', ComplaintReplyController::class);
        Route::resource('tasks', TaskController::class);
        Route::resource('my-tasks', MyTaskController::class);
        Route::get('tasks-replies/{id}', [TaskController::class, 'replies'])->name('tasks.replies');
        Route::resource('hr-task-replies', HrTaskReplyController::class);
        Route::resource('complaints', EmployeeComplaintController::class);
        Route::resource('follow-daily-reports', FollowDailyReportController::class);
        Route::resource('dashboard', HomeController::class);
        Route::resource('rewards', RewadController::class);
        Route::resource('employee-profile', EmployeeProfileController::class);


    });
});
