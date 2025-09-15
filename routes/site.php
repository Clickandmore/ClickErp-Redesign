<?php


use App\Http\Controllers\site\AllWorkerController;
use App\Http\Controllers\site\ArrivalWorkerController;
use App\Http\Controllers\site\auth\ChangePasswordController;
use App\Http\Controllers\site\auth\ForgetPasswordController;
use App\Http\Controllers\site\auth\LoginController;
use App\Http\Controllers\site\auth\LogoutController;
use App\Http\Controllers\site\auth\OtpController;
use App\Http\Controllers\site\auth\RegisterController;
use App\Http\Controllers\site\ContactUsController;
use App\Http\Controllers\site\DailyLabotDemandController;
use App\Http\Controllers\site\HomeController;
use App\Http\Controllers\site\MusanedController;
use App\Http\Controllers\site\profile\ChatController;
use App\Http\Controllers\site\profile\NotificationController;
use App\Http\Controllers\site\profile\OrderController;
use App\Http\Controllers\site\profile\ProfileController;
use App\Http\Controllers\site\profile\SettingController;
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
        Route::resource('all-workers', AllWorkerController::class);
        Route::resource('musaned', MusanedController::class);
        Route::resource('arrival-worker', ArrivalWorkerController::class);
        Route::resource('select-worker', SelectWorkerController::class);
        Route::resource('visa', VisaController::class);
        Route::resource('daily-lebor-demand', DailyLabotDemandController::class);
        Route::resource('renew-contract', RenewContractController::class);
        Route::resource('support', SupportController::class);
        Route::resource('contact-us', ContactUsController::class);

        Route::resource('profile', ProfileController::class);
        Route::resource('last-orders', OrderController::class);
        Route::resource('chats', ChatController::class);
        Route::resource('notifications', NotificationController::class);
        Route::resource('settings', SettingController::class);
        Route::resource('logout', LogoutController::class);

    });


});



