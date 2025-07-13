<?php
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
use App\Http\Controllers\ingaz\OrderController as AdminOrderController;

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
        Route::group(["prefix" => "admin"], function () {

            Route::get('getMaidByNationality/{nationalityId}', 'RecruitmentContractController@getMaidByNationality')->name('getMaidByNationality');

            //** NEW */
            Route::get('getContractEdit/{id}', [AdminContractController::class, 'getContractEdit'])->name('getContractEdit');
            Route::post('updateContractInfo', [AdminContractController::class, 'updateContractInfo'])->name('updateContractInfo');
            //** */
            Route::get('viewFullOrderContract', [AdminFullOrderContractController::class, 'viewFullOrderContract'])->name('viewFullOrderContract');
            Route::post('createFullOrderContract', [AdminFullOrderContractController::class, 'createFullOrderContract'])->name('createFullOrderContract');
            /** */
            Route::get('printRentContract/{contractId}', [AdminContractController::class, 'printRentContract'])->name('printRentContract');
            //** */
            Route::get('/shelter_workers', [ShelterWorkersController::class, 'index'])->name('shelter_workers');
            // Route::get('/shelter_workers/{shelter_worker}', [ShelterWorkersController::class, 'show'])->name('shelter_workers.show');
            Route::get('/shelter_workers/create', [ShelterWorkersController::class, 'create'])->name('shelter_workers.control');
            Route::get('/shelter_workers/{id}/edit', [ShelterWorkersController::class, 'edit'])->name('shelter_workers.edit');
            Route::get('/shelter_workers/{id}', [ShelterWorkersController::class, 'show'])->name('shelter_workers.show');
            Route::post('/shelter_workers', [ShelterWorkersController::class, 'store'])->name('shelter_workers.store');
            Route::post('/shelter_workers/{shelter_worker}', [ShelterWorkersController::class, 'update'])->name('shelter_workers.update');
            Route::delete('/shelter_workers/{shelter_worker}', [ShelterWorkersController::class, 'destroy'])->name('shelter_workers.delete');
            Route::get('shelter_workers/{id}/download/{field}', [ShelterWorkersController::class, 'download'])->name('shelter_workers.download');
            Route::delete('shelter_workers/{id}/delete-file/{field}', [ShelterWorkersController::class, 'deleteFile'])->name('shelter_workers.delete_file');
            //** */
            Route::get('viewFullShelterWorkerContracts', [AdminShelterWorkersContractCTLR::class, 'viewFullShelterWorkerContract'])->name('viewFullShelterWorkerContract');
            Route::post('createFullShelterWorkerContract', [AdminShelterWorkersContractCTLR::class, 'createFullShelterWorkerContract'])->name('createFullShelterWorkerContract');
            //** */
            Route::get('/travel', [AdminTravelHistoryController::class, 'index'])->name('travel');
            Route::get('/travel/create/{id?}', [AdminTravelHistoryController::class, 'create'])->name('travel.control');
            Route::get('/travel/{id}/edit', [AdminTravelHistoryController::class, 'edit'])->name('travel.edit');
            Route::post('/travel', [AdminTravelHistoryController::class, 'store'])->name('travel.store');
            Route::post('/travel/{shelter_worker}', [AdminTravelHistoryController::class, 'update'])->name('travel.update');
            Route::delete('/travel/{shelter_worker}', [AdminTravelHistoryController::class, 'destroy'])->name('travel.delete');
            Route::post('/store-travel-history', [AdminTravelHistoryController::class, 'storeTravelHistory'])->name('storeTravelHistory');
            Route::get('/travel/download/{id}', [AdminTravelHistoryController::class, 'downloadFile'])->name('travel.file.download');
            Route::delete('/travel/file/delete/{id}', [AdminTravelHistoryController::class, 'deleteFile'])->name('travel.file.delete');
            //** */
            Route::post('/settings/store', [PrintContractController::class, 'store'])->name('new_settings.store');
            Route::get('/contract_settings', [PrintContractController::class, 'index'])->name('contract.settings');
            //** PRINT CONTRACT **//
            Route::get('/print_contract/{contractId?}', [PrintContractController::class, 'contract'])->name('print.contract');
            //** END NEW *//

            Route::resource('settings', SettingController::class);
            Route::resource('develop-settings', DevelopSettingController::class);
            Route::resource('job-titles', JobTitleController::class);
            Route::resource('nationalities', NationalityController::class);
            Route::resource('sliders', SliderController::class);
            Route::resource('religions', ReligionController::class);
            Route::resource('questions', QuestionController::class);
            Route::resource('contact', ContactController::class);
            Route::resource('services', ServiceController::class);
            Route::resource('about-us', AboutUsController::class);
            Route::resource('clients', UserController::class);
            Route::get('show-maid-details/{id}', [\App\Http\Controllers\ingaz\MaidController::class, 'showMaidDetails'])->name('showMaidDetails');
            Route::get('maids-not-available', [\App\Http\Controllers\ingaz\MaidController::class, 'noneAvailableMaids'])->name('noneAvailableMaids');
            Route::resource('maids', MaidController::class);
            Route::get('convert_page/{id}', [App\Http\Controllers\ingaz\MaidController::class, 'convert_page'])->name('convert_page');
            Route::post('update_cv/{id}', [App\Http\Controllers\ingaz\MaidController::class, 'update_cv'])->name('maids.update_cv');

            Route::resource('maids-test', MaidTestController::class);
            Route::resource('packages', PackageController::class);
            Route::resource('complaints', ComplaintController::class);
            Route::post('completeOrder/{id}', [AdminOrderController::class, 'completeOrder'])->name('orders.completeOrder');
            Route::post('getOrderById/{id}', [AdminOrderController::class, 'getOrderById'])->name('orders.getOrderById');
            Route::resource('orders', OrderController::class);
            Route::get('orders/current', [OrderController::class, 'index'])->name('orders.current');
            Route::get('orders/ended', [OrderController::class, 'index'])->name('orders.ended');
            Route::resource('RecruitmentContract', RecruitmentContractController::class);

            Route::get('RecruitmentContractBack/{id}', [App\Http\Controllers\ingaz\RecruitmentContractController::class, 'back'])->name('RecruitmentContract.back');
            Route::get('contractsDetails/{id}', [App\Http\Controllers\ingaz\RecruitmentContractController::class, 'details'])->name('contracts.details');
            Route::post('RecruitmentContract/{contractId}/add-maid', [App\Http\Controllers\ingaz\RecruitmentContractController::class, 'addMaidToContract'])->name('RecruitmentContract.addMaid');
            Route::get('RecruitmentContract/available-maids', [App\Http\Controllers\ingaz\RecruitmentContractController::class, 'getAvailableMaids'])->name('RecruitmentContract.availableMaids');
            Route::get('RecruitmentContract/filter-options', [App\Http\Controllers\ingaz\RecruitmentContractController::class, 'getFilterOptions'])->name('RecruitmentContract.filterOptions');
            Route::resource('RentContract', RentContractController::class);
            Route::get('showDataOfOrder', [App\Http\Controllers\ingaz\OrderController::class, 'showDataOfOrder'])->name('showDataOfOrder');
            Route::get('showDataOfMaid', [App\Http\Controllers\ingaz\OrderController::class, 'showDataOfMaid'])->name('showDataOfMaid');
            Route::get('showUserFromContract', [App\Http\Controllers\ingaz\OrderController::class, 'showUserFromContract'])->name('showUserFromContract');
            Route::resource('SponsorshipTransfer', SponsorshipTransfer::class);
            Route::resource('notifications', NotificationController::class);
            Route::resource('statistics', StatisticController::class);
            Route::resource('steps', StepController::class);
            Route::resource('return_requests', ReturnRequestController::class);
            Route::resource('recruitment-offices', RecruitmentOffice::class);
            Route::resource('shelter-places', ShelterPlaces::class);
            // Route::resource('roles', RoleController::class);
            Route::resource('ShelterContract', ShelterContractController::class);

            //** New */
            Route::get('show_single_order/{orderId}', [App\Http\Controllers\ingaz\OrderController::class, 'showSingleOrder'])->name('showSingleOrder');
            Route::get('/order_file/{id}/download', [App\Http\Controllers\ingaz\OrderController::class, 'download'])->name('order_files.download');
            Route::get('/cancel_contracts', [App\Http\Controllers\ingaz\ContractsController::class, 'cancel_contracts'])->name('cancel_contracts');

        });

    }
);

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
    ],
    function () {

        Route::name('dash.')->prefix('dash')->group(function () {

            //** SETTINGS **//
            Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings');
            Route::post('/settings/store', [AdminSettingsController::class, 'store'])->name('settings.store');

            //** ROLES **//
            Route::name('roles.')->prefix('roles')->group(function () {
                Route::get('/{id?}', [RolesController::class, 'index'])->name('index');
                Route::post('store', [RolesController::class, 'store'])->name('store');
                Route::delete('delete/{id}', [RolesController::class, 'delete'])->name('delete');
            });

            //** PERMISSIONS **//
            Route::name('perms.')->prefix('perms')->group(function () {
                Route::get('/{id}', [PermissionsController::class, 'setPerms'])->name('setPerms');
                Route::post('storePerms', [PermissionsController::class, 'storePerms'])->name('storePerms');
            });

            //** DEVELOPERS ONLY PERMISSIONS **//
            Route::name('developers.perms.')->prefix('developers')->group(function () {
                Route::get('perms/{id?}', [PermissionsController::class, 'index'])->name('index');
                Route::post('store', [PermissionsController::class, 'store'])->name('store');
                Route::delete('delete/{id}', [PermissionsController::class, 'delete'])->name('delete');
            });

            //** ACTIVITY LOG **//
            Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity.log');

        });

    }
);
