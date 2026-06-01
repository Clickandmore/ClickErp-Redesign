<?php


use Illuminate\Support\Facades\Route;

use Nami\Hr\Http\Controllers\AllowanceController;
use Nami\Hr\Http\Controllers\ChangeYearStatusController;
use Nami\Hr\Http\Controllers\DepartmentController;
use Nami\Hr\Http\Controllers\Employee\DailyWorkReportController;
use Nami\Hr\Http\Controllers\Employee\HomeController;
use Nami\Hr\Http\Controllers\Employee\PermissionController;
use Nami\Hr\Http\Controllers\Employee\VacationController;
use Nami\Hr\Http\Controllers\EmployeeAdvanceController;
use Nami\Hr\Http\Controllers\EmployeeAttendancesController;
use Nami\Hr\Http\Controllers\EmployeeController;
use Nami\Hr\Http\Controllers\EmployeeCustodyController;
use Nami\Hr\Http\Controllers\EmployeeCustodyLocationController;
use Nami\Hr\Http\Controllers\EmployeeExecelFileController;
use Nami\Hr\Http\Controllers\EmployeePermissionController;
use Nami\Hr\Http\Controllers\EmployeeRewardController;
use Nami\Hr\Http\Controllers\EmployeeSanctionController;
use Nami\Hr\Http\Controllers\EmployeeShiftController;
use Nami\Hr\Http\Controllers\EmployeeVacationBalanceController;
use Nami\Hr\Http\Controllers\EmployeeVacationController;
use Nami\Hr\Http\Controllers\getAllowanceDataController;
use Nami\Hr\Http\Controllers\InsuranceController;
use Nami\Hr\Http\Controllers\JobController;
use Nami\Hr\Http\Controllers\MlutiEmployeeController;
use Nami\Hr\Http\Controllers\OfficialVacationController;
use Nami\Hr\Http\Controllers\PermissionSettingController;
use Nami\Hr\Http\Controllers\PayrollController;
use Nami\Hr\Http\Controllers\RewardsController;
use Nami\Hr\Http\Controllers\SalariesController;
use Nami\Hr\Http\Controllers\SanctionController;
use Nami\Hr\Http\Controllers\ShiftController;
use Nami\Hr\Http\Controllers\TaxController;
use Nami\Hr\Http\Controllers\ToggleStatusController;
use Nami\Hr\Http\Controllers\ToggleStatusEmployeesController;
use Nami\Hr\Http\Controllers\ToggleStatusEmployeeVacationController;
use Nami\Hr\Http\Controllers\UserDashboardController;
use Nami\Hr\Http\Controllers\VacationYearController;
use Nami\Hr\Models\Employee;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

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
//Route::group(["middleware" => ["web", "admin"], "prefix" => "hr"], function () {
//    Route::resource("home", HomeController::class)->names(['index' => 'hr.home.index']);
//});
Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ["adminLocale", 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath']
    ], function () {
    Route::group(["middleware" => ["web", "admin", "package.route.permission:hr"], "prefix" => "hr"], function () {
        Route::resource("home", HomeController::class)->names(['index' => 'hr.home.index']);
        Route::resource('departments', DepartmentController::class);
        Route::resource('jobs', JobController::class);
        Route::resource('allowances', AllowanceController::class);
        Route::resource('sanctions', SanctionController::class);
        Route::resource('vacation-year', VacationYearController::class);
        Route::resource('year-status', ChangeYearStatusController::class);
        Route::resource('vacations', VacationController::class);
        Route::resource('permissions', \Nami\Hr\Http\Controllers\PermissionController::class);
        Route::resource('permissionsSettings', PermissionSettingController::class);
        Route::resource('insurances', InsuranceController::class);
        Route::resource('tax', TaxController::class);
        Route::resource('rewards', RewardsController::class);
        Route::post('toggleStatus', [ToggleStatusController::class, 'toggleStatus'])->name('toggleStatus');
        Route::resource('employees', EmployeeController::class);
        Route::get('maids', [EmployeeController::class, 'maids'])->name('hr.maids.index');
        Route::post('maids/{maidId}/employ', [EmployeeController::class, 'employMaid'])->name('hr.maids.employ');
        Route::get('/get-allowance-data/{id}', [getAllowanceDataController::class, 'getAllowanceData'])->name('getAllowanceData');
        Route::resource('employeeVacations', EmployeeVacationController::class);
        Route::resource('employeeVacationBalance', EmployeeVacationBalanceController::class);
        Route::resource('ToggleStatusEmployees', ToggleStatusEmployeesController::class);
        Route::resource('ToggleStatusEmployeeVacation', ToggleStatusEmployeeVacationController::class);
        Route::resource('employeeSanctions', EmployeeSanctionController::class);
        Route::resource('employeePermissions', EmployeePermissionController::class);
        Route::resource('officialVacations', OfficialVacationController::class);
        Route::resource('shifts', ShiftController::class);
        Route::resource('employeeShifts', EmployeeShiftController::class);
        Route::resource('employeeAttendances', EmployeeAttendancesController::class);
        Route::resource('salaries', SalariesController::class);
        Route::resource('payrolls', PayrollController::class)->only(['index', 'create', 'store', 'show']);
        Route::resource('employeeProfile', UserDashboardController::class);
        Route::resource('employeeExecelFile', EmployeeExecelFileController::class);
        Route::resource('mluti-employee', MlutiEmployeeController::class);
        Route::resource('daily-work-reports', DailyWorkReportController::class);
        Route::resource('employee-rewards', EmployeeRewardController::class);
        Route::resource('employee-advances', EmployeeAdvanceController::class);
        Route::resource('employee-custodies', EmployeeCustodyController::class);
        Route::resource('employee-custody-locations', EmployeeCustodyLocationController::class);
    });
});
