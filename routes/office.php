<?php

use App\Http\Controllers\office\OfficeController;
use App\Http\Controllers\office\OfficeFileController;
use App\Http\Controllers\office\OfficeStatusController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\admin\ActivityLogController;
use App\Http\Controllers\Permissions\RolesController;
use App\Http\Controllers\admin\AdminContractController;
use App\Http\Controllers\admin\AdminSettingsController;
use App\Http\Controllers\admin\PrintContractController;
use App\Http\Controllers\admin\ShelterWorkersController;
use App\Http\Controllers\Permissions\PermissionsController;
use App\Http\Controllers\admin\AdminTravelHistoryController;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use App\Http\Controllers\admin\AdminShelterWorkersContractCTLR;
use App\Http\Controllers\admin\AdminFullOrderContractController;
use App\Http\Controllers\ingaz\OrderController;
use App\Http\Controllers\ingaz\RentalOrderController;
use App\Http\Controllers\ingaz\ShelterContractController;
use App\Http\Controllers\ingaz\RecruitmentContractController;
use App\Http\Controllers\ingaz\SponsorshipTransferController;
use App\Http\Controllers\ingaz\RentContractController;
use App\Http\Controllers\ingaz\NotificationController;
use App\Http\Controllers\ingaz\StatisticController;
use App\Http\Controllers\ingaz\DeportationTravelController;
use App\Http\Controllers\ingaz\UserController;
use App\Http\Controllers\ingaz\MaidController;
use App\Http\Controllers\ingaz\SettingController;
use App\Http\Controllers\ingaz\DevelopSettingController;
use App\Http\Controllers\ingaz\JobTitleController;
use App\Http\Controllers\ingaz\NationalityController;
use App\Http\Controllers\ingaz\SliderController;
use App\Http\Controllers\ingaz\ReligionController;
use App\Http\Controllers\ingaz\QuestionController;
use App\Http\Controllers\ingaz\ContactController;
use App\Http\Controllers\ingaz\ServiceController;
use App\Http\Controllers\ingaz\AboutUsController;
use App\Http\Controllers\ingaz\SkillController;
use App\Http\Controllers\ingaz\MaidTestController;
use App\Http\Controllers\ingaz\PackageController;
use App\Http\Controllers\ingaz\ComplaintController;
use App\Http\Controllers\ingaz\StepController;
use App\Http\Controllers\ingaz\ReturnRequestController;
use App\Http\Controllers\ingaz\ComplaintTypeController;
use App\Http\Controllers\ingaz\RentalContractTermsController;


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
        Route::group(["prefix" => "admin", "middleware" => "auth:admin"], function () {

            Route::resource('offices', OfficeController::class);
            Route::resource('offices-status', OfficeStatusController::class);
            Route::resource('offices-file', OfficeFileController::class);

        });

    }
);
