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
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\ingaz\RecruitmentContractController;
use App\Http\Controllers\ingaz\RecruitmentController;

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
Route::get('edit-profile', [AuthController::class, 'editProfile'])->name('edit-profile');
                Route::get('deleteAccount', [AuthController::class, 'deleteAccount'])->name('deleteAccount');
                Route::get("logout", [AuthController::class, "logout"])->name("logout");
                Route::resource('orders', OrderController::class);

                Route::resource('contracts', ContractsController::class);
                Route::post('/contracts/cancel', [\App\Http\Controllers\Web\ContractsController::class, 'cancel'])->name('contracts.cancel');



                Route::post('bank_transfer_submit', [App\Http\Controllers\Web\OrderController::class, 'submitBankTransfer'])->name('bank_transfer.submit');
                Route::resource('addresses', UserAddressController::class);
                Route::resource('complaints', ComplaintController::class);
                Route::resource('notifications', NotificationController::class);

                // طلبات استرجاع العقود
                Route::get('contracts/{contract}/return-request', [\App\Http\Controllers\Web\ContractReturnRequestController::class, 'create'])->name('contract-return-requests.create');
                Route::post('contracts/{contract}/return-request', [\App\Http\Controllers\Web\ContractReturnRequestController::class, 'store'])->name('contract-return-requests.store');
                Route::get('return-requests', [\App\Http\Controllers\Web\ContractReturnRequestController::class, 'index'])->name('contract-return-requests.index');
                Route::get('return-requests/{returnRequest}', [\App\Http\Controllers\Web\ContractReturnRequestController::class, 'show'])->name('contract-return-requests.show');
                Route::delete('return-requests/{returnRequest}', [\App\Http\Controllers\Web\ContractReturnRequestController::class, 'cancel'])->name('contract-return-requests.cancel');
            }
        );
        Route::resource('contact-us', ContactUs::class);
        Route::get('/about-us', [\App\Http\Controllers\Web\AboutUsController::class, 'index'])->name('about-us');
        Route::get('/clients/export/pdf', [UserController::class, 'exportPdf'])->name('clients.export.pdf');
        Route::get('/clients/export/excel', [UserController::class, 'exportExcel'])->name('clients.export.excel');
        Route::resource('skills', 'ingaz\SkillController');
        Route::get('/get-offices-by-nationality', [App\Http\Controllers\ingaz\MaidController::class, 'getOfficesByNationality'])->name('getOfficesByNationality');

        // Dashboard route for clients
        Route::get('dashboard', [\App\Http\Controllers\Web\AuthController::class, 'dashboard'])->name('dashboard')->middleware('auth_web');
    }
);

Route::group(['prefix' => 'admin', 'middleware' => ['auth:admin']], function () {
    Route::group(['prefix' => 'ingaz'], function () {
        Route::get('/clients/export/pdf', [UserController::class, 'exportPdf'])->name('clients.export.pdf');
        Route::get('/clients/export/excel', [UserController::class, 'exportExcel'])->name('clients.export.excel');
        Route::get('/get-offices-by-nationality', [App\Http\Controllers\ingaz\MaidController::class, 'getOfficesByNationality'])->name('getOfficesByNationality');

        // تحديث مسارات الإعدادات
        Route::get('settings', [App\Http\Controllers\ingaz\SettingController::class, 'index'])->name('admin.ingaz.settings.index');
        Route::put('settings', [App\Http\Controllers\ingaz\SettingController::class, 'update'])->name('admin.ingaz.settings.update');

        // مسارات الدول
        Route::get('countries', [CountryController::class, 'index'])->name('countries.index');
        Route::get('countries/create', [CountryController::class, 'create'])->name('countries.create');
        Route::post('countries', [CountryController::class, 'store'])->name('countries.store');
        Route::get('countries/{country}/edit', [CountryController::class, 'edit'])->name('countries.edit');
        Route::put('countries/{country}', [CountryController::class, 'update'])->name('countries.update');
        Route::delete('countries/{country}', [CountryController::class, 'destroy'])->name('countries.destroy');
        Route::patch('countries/{country}/toggle-status', [CountryController::class, 'toggleStatus'])->name('countries.toggle-status');
        Route::post('countries/update-order', [CountryController::class, 'updateOrder'])->name('countries.update-order');

        // Recruitment routes
        Route::get('/recruitment/select-maid/{client_id}', [RecruitmentController::class, 'create'])->name('admin.recruitment.select.maid');
        Route::get('/recruitment/show-maid-form/{client_id}/{maid_id}', [RecruitmentController::class, 'showMaidForm'])->name('admin.recruitment.show.maid.form');
        Route::post('/recruitment/store', [RecruitmentController::class, 'store'])->name('admin.recruitment.store');
        Route::get('/recruitment/show-employee-modal/{maid_id}/{client_id}', [RecruitmentController::class, 'showEmployeeModal'])->name('admin.recruitment.show.employee.modal');
    });

    // Add the travel complete route inside the admin group
    Route::post('/travel/complete/{tripId}', [RecruitmentContractController::class, 'completeTrip'])->name('travel.complete');
    Route::post('/RecruitmentContract/completeTrip', [RecruitmentContractController::class, 'completeTrip'])->name('RecruitmentContract.completeTrip');
    Route::post('/travel/add', [RecruitmentContractController::class, 'addTravel'])->name('travel.add');
    Route::post('/RecruitmentContract/updateStatus', [RecruitmentContractController::class, 'updateStatus'])->name('RecruitmentContract.updateStatus');
    Route::post('/RecruitmentContract/cancel', [RecruitmentContractController::class, 'cancel'])->name('RecruitmentContract.cancel');
});

// Notification routes
Route::prefix('admin')->middleware(['auth:admin'])->group(function () {
    Route::get('/notifications', [App\Http\Controllers\Admin\NotificationController::class, 'index']);
    Route::post('/notifications/{id}/mark-as-read', [App\Http\Controllers\Admin\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [App\Http\Controllers\Admin\NotificationController::class, 'markAllAsRead']);

    // SMS Templates Management
    Route::get('sms-templates', [\App\Http\Controllers\admin\SmsTemplateController::class, 'index'])->name('admin.sms-templates.index');
    Route::get('sms-templates/create', [\App\Http\Controllers\admin\SmsTemplateController::class, 'create'])->name('admin.sms-templates.create');
    Route::post('sms-templates', [\App\Http\Controllers\admin\SmsTemplateController::class, 'store'])->name('admin.sms-templates.store');
    Route::get('sms-templates/{smsTemplate}', [\App\Http\Controllers\admin\SmsTemplateController::class, 'show'])->name('admin.sms-templates.show');
    Route::get('sms-templates/{smsTemplate}/edit', [\App\Http\Controllers\admin\SmsTemplateController::class, 'edit'])->name('admin.sms-templates.edit');
    Route::put('sms-templates/{smsTemplate}', [\App\Http\Controllers\admin\SmsTemplateController::class, 'update'])->name('admin.sms-templates.update');
    Route::delete('sms-templates/{smsTemplate}', [\App\Http\Controllers\admin\SmsTemplateController::class, 'destroy'])->name('admin.sms-templates.destroy');
    Route::post('sms-templates/{smsTemplate}/test', [\App\Http\Controllers\admin\SmsTemplateController::class, 'test'])->name('admin.sms-templates.test');
});

// روابط التقييم العامة (بدون تسجيل دخول)
Route::get('/feedback/{token}', [\App\Http\Controllers\ComplaintFeedbackController::class, 'show'])->name('complaint.feedback');
Route::post('/feedback/submit', [\App\Http\Controllers\ComplaintFeedbackController::class, 'submit'])->name('complaint.feedback.submit');

