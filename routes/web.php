<?php

use Web\MaidController;
use Web\OrderController;
use Web\ContractsController;
use Illuminate\Support\Str;
use Web\ComplaintController;
use App\Models\ingaz\Service;
use Web\UserAddressController;
use Web\NotificationController;
use Web\ReturnRequestController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Web\ContactUsController as ContactUs;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Admin\AdminContractController;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use App\Http\Controllers\ingaz\MaidController as IngazMaidController;
use App\Http\Controllers\ingaz\UserController;

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

//Route::get('controllers-generate',function (){
//    $projectName = 'ingaz';
//    $migrations = getMigrations($projectName);
//
//    foreach ($migrations as $index => $migration) {
//        $modelName = extractModelName($migration);
//
//        if ($modelName === null) {
//            continue;
//        }
//        Artisan::call("make:controller ".$projectName."/".$modelName."Controller --model=".$modelName);
//        echo $modelName."\n";
//    }
//});
//
//Route::get('requests-generate',function (){
//    $projectName = 'ingaz';
//    $migrations = getMigrations($projectName);
//    foreach ($migrations as $index => $migration) {
//        $modelName = extractModelName($migration);
//
//        if ($modelName === null) {
//            continue;
//        }
//        Artisan::call("make:request ".$projectName."/".$modelName."Request");
//        echo $modelName."\n";
//    }
//});

/** NEW ROUTES */
Route::get('/getMaidDetails/{maidId}', [IngazMaidController::class, 'getMaidDetails'])->name('is.getMaidDetails');
Route::get('/maidsLoopContractsAndSetAvailableWhenDoneCronJob', [AdminContractController::class, 'maidsLoopContractsAndSetAvailableWhenDoneCronJob'])->name('maidsLoopContractsAndSetAvailableWhenDoneCronJob');
Route::get('/check-tazker-contracts', [AdminContractController::class, 'checkTazkerContracts'])->name('check.tazker.contracts');
Route::get('/check-warranty-contracts', [AdminContractController::class, 'checkWarrantyContracts'])->name('check.warranty.contracts');

/** END NEW ROUTES */
Route::get('apis-generate', function () {


    $projectName = 'ingaz';
    $migrations = getMigrations($projectName);
    foreach ($migrations as $index => $migration) {
        $modelName = extractModelName($migration);

        if ($modelName === null) {
            continue;
        }
        // Artisan::call("make:controller Api/Api{$modelName}Controller --model={$projectName}/{$modelName}  --api");
        Artisan::call("make:resource Api/{$modelName}Resource");
        echo $modelName . "\n";
    }
});

// update contracts when status is tazker and travel_histories


Route::group(
    [
        'as' => 'web.',
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ["webLocale", 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath']
    ],
    function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::get('/rights-maids', [HomeController::class, 'rightsMaids'])->name('rights-maids');
        Route::get('/rights-users', [HomeController::class, 'rightsUsers'])->name('rights-users');
        Route::get('/privacy-policy', [HomeController::class, 'privacyPolicy'])->name('privacyPolicy');
        Route::get('/terms-conditions', [HomeController::class, 'termsConditions'])->name('termsConditions');
        Route::get('/faqs', [\App\Http\Controllers\Web\FAQsController::class, 'index'])->name('faqs');
        Route::resource('return_requests', ReturnRequestController::class);
        Route::resource('maids', MaidController::class);

        //================ authanticaion  =============
        Route::get('auth', [AuthController::class, 'index'])->name('auth');
        Route::get('reset-password', [AuthController::class, 'resetPassword'])->name('reset_password');
        Route::get('sendOtp', [AuthController::class, 'sendOtp'])->name('sendOtp');
        Route::get('otp', [AuthController::class, 'otp'])->name('otp');
        Route::post('confirm-code', [AuthController::class, 'confirm_code'])->name('confirm-code');
        Route::post('sendLogin', [AuthController::class, 'sendLogin'])->name('sendLogin');
        Route::group(
            [
                'middleware' => ["auth_web"]
            ],
            function () {
                Route::get('profile', [AuthController::class, 'profile'])->name('profile');
                Route::post('update-profile', [AuthController::class, 'updateProfile'])->name('update-profile');
                Route::get('deleteAccount', [AuthController::class, 'deleteAccount'])->name('deleteAccount');
                Route::get("logout", [AuthController::class, "logout"])->name("logout");
                Route::resource('orders', OrderController::class);

                Route::resource('contracts', ContractsController::class);
                Route::post('/contracts/cancel', [\App\Http\Controllers\Web\ContractsController::class, 'cancel'])->name('contracts.cancel');



                Route::post('bank_transfer_submit', [App\Http\Controllers\Web\OrderController::class, 'submitBankTransfer'])->name('bank_transfer.submit');
                Route::resource('addresses', UserAddressController::class);
                Route::resource('complaints', ComplaintController::class);
                Route::resource('notifications', NotificationController::class);
            }
        );
        Route::resource('contact-us', ContactUs::class);
        Route::get('/about-us', [\App\Http\Controllers\Web\AboutUsController::class, 'index'])->name('about-us');
        Route::get('/clients/export/pdf', [UserController::class, 'exportPdf'])->name('clients.export.pdf');
        Route::get('/clients/export/excel', [UserController::class, 'exportExcel'])->name('clients.export.excel');
        Route::resource('skills', 'ingaz\SkillController');
        Route::get('/get-offices-by-nationality', [App\Http\Controllers\ingaz\MaidController::class, 'getOfficesByNationality'])->name('getOfficesByNationality');
    }
);

Route::group(['prefix' => 'admin', 'middleware' => ['auth:admin']], function () {
    Route::group(['prefix' => 'ingaz'], function () {
        Route::get('/clients/export/pdf', [UserController::class, 'exportPdf'])->name('clients.export.pdf');
        Route::get('/clients/export/excel', [UserController::class, 'exportExcel'])->name('clients.export.excel');
        Route::get('/get-offices-by-nationality', [App\Http\Controllers\ingaz\MaidController::class, 'getOfficesByNationality'])->name('getOfficesByNationality');
    });
});

// Notification routes
Route::prefix('admin')->middleware(['auth:admin'])->group(function () {
    Route::get('/notifications', [App\Http\Controllers\Admin\NotificationController::class, 'index']);
    Route::post('/notifications/{id}/mark-as-read', [App\Http\Controllers\Admin\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [App\Http\Controllers\Admin\NotificationController::class, 'markAllAsRead']);
});

