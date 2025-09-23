<?php


use App\Http\Controllers\site\AllWorkerController;
use App\Http\Controllers\site\ArrivalWorkerController;
use App\Http\Controllers\site\auth\ChangePasswordController;
use App\Http\Controllers\site\auth\ForgetPasswordController;
use App\Http\Controllers\site\auth\LoginController;
use App\Http\Controllers\site\auth\LogoutController;
use App\Http\Controllers\site\auth\OtpController;
use App\Http\Controllers\site\auth\OtpRegisterController;
use App\Http\Controllers\site\auth\RegisterController;
use App\Http\Controllers\site\ContactUsController;
use App\Http\Controllers\site\CustomerServiceController;
use App\Http\Controllers\site\DailyLabotDemandController;
use App\Http\Controllers\site\HomeController;
use App\Http\Controllers\site\MusanedController;
use App\Http\Controllers\site\profile\ChatController;
use App\Http\Controllers\site\profile\ComplaintController;
use App\Http\Controllers\site\profile\ContractReturnRequestController;
use App\Http\Controllers\site\profile\ContractsController;
use App\Http\Controllers\site\profile\NotificationController;
use App\Http\Controllers\site\profile\OrderController;
use App\Http\Controllers\site\profile\ProfileController;
use App\Http\Controllers\site\profile\SettingController;
use App\Http\Controllers\site\profile\SponsorshipTransferController;
use App\Http\Controllers\site\RecruitmentOrderController;
use App\Http\Controllers\site\RecruitmentRequestController;
use App\Http\Controllers\site\RenewContractController;
use App\Http\Controllers\site\SelectWorkerController;
use App\Http\Controllers\site\SupportController;
use App\Http\Controllers\site\VisaController;
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
/*
Route::get('/', function () {
    return view('welcome');
});*/


Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath']
    ], function () {
    Route::group(['as' => 'site.'], function () {
        Route::get('/', [HomeController::class, 'index'])->name('home.index');
        Route::resource('login', LoginController::class);
        Route::resource('register', RegisterController::class);
        Route::resource('forget-password', ForgetPasswordController::class);
        Route::resource('change-password', ChangePasswordController::class);
        Route::resource('otp', OtpController::class);
        Route::resource('otp-register', OtpRegisterController::class);
        Route::resource('all-workers', AllWorkerController::class);
        Route::resource('musaned', MusanedController::class);
        Route::resource('arrival-worker', ArrivalWorkerController::class);
        Route::resource('select-worker', SelectWorkerController::class);
        Route::resource('visa', VisaController::class);
        Route::resource('daily-lebor-demand', DailyLabotDemandController::class);
        Route::resource('renew-contract', RenewContractController::class);
        Route::resource('support', SupportController::class);
        Route::resource('contact-us', ContactUsController::class)->names('contact-us');

        Route::middleware('site_auth')->group(function () {
            Route::resource('profile', ProfileController::class);
            Route::resource('last-orders', OrderController::class);
            Route::resource('chats', ChatController::class);
            Route::resource('notifications', NotificationController::class);
            Route::resource('settings', SettingController::class);
            Route::resource('recruitment-request', RecruitmentRequestController::class);
            Route::resource('recruitment-orders', RecruitmentOrderController::class);
            Route::resource('customer-service', CustomerServiceController::class);
            Route::resource('logout', LogoutController::class);
            Route::post('/contracts/cancel', [ContractsController::class, 'cancel'])->name('contracts.cancel');
            Route::resource('contracts', ContractsController::class);
            Route::resource('sponsorship-transfers', SponsorshipTransferController::class);
            Route::get('return-requests', [ContractReturnRequestController::class, 'index'])->name('contract-return-requests.index');
            Route::resource('complaints', ComplaintController::class);
        });
    });


});



