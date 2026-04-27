<?php

use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\ApiPhoneVerificationController;
use App\Http\Controllers\Api\ApiUserFirebaseTokenController;
use App\Http\Controllers\Webhook\WhatsAppWebhookController;
use \Api\ApiSettingController;
use \Api\ApiUserAddressController;
use \Api\ApiSliderController;
use \Api\ApiNationalityController;
use \Api\ApiJobTitleController;
use \Api\ApiJobTypeController;
use \Api\ApiMaidController;
use \Api\ApiReligionController;
use \Api\ApiCustomerController;
use \Api\ApiPackageController;
use \Api\ApiClientDashboard;
use \Api\ApiOrderController;
use \Api\ApiContractController;
use \Api\ApiRenewContractsController;
use \Api\ApiSponsorshipTransferController;
use \Api\ApiNotificationController;
use \Api\ApiComplaintController;
use \Api\ApiContactController;
use \Api\ApiReturnRequestController;
use \Api\ApiDailyLabotDemandController;
use \Api\ApiSelectWorkerController;
use App\Http\Controllers\Api\HomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('api.webhooks.whatsapp.verify');
Route::post('webhooks/whatsapp', [WhatsAppWebhookController::class, 'receive'])->name('api.webhooks.whatsapp.receive');

Route::group(
    [
        'middleware' => 'lang'
    ],
    function () {
        Route::group(['prefix' => 'auth'], function ($router) {

            Route::post('/confirm-code', [ApiPhoneVerificationController::class, 'confirm_code']);
            Route::post('/reset-password-otp', [ApiPhoneVerificationController::class, 'reset_password_otp']);
            Route::post('/reset-password', [ApiPhoneVerificationController::class, 'reset_password']);
            Route::post('/change-password', [ApiAuthController::class, 'change_password']);
            Route::post('/resend-code', [ApiPhoneVerificationController::class, 'resend_code']);
            Route::post('/register', [ApiAuthController::class, 'register']);
            Route::post('/login', [ApiAuthController::class, 'login']);
            Route::post('/logout', [ApiAuthController::class, 'logout'])->middleware('auth:api');
            
            // *****************

            Route::post('/update/password', [ApiAuthController::class, 'update_password']);
            Route::post('/refresh', [ApiAuthController::class, 'refresh']);
            Route::get('/profile', [ApiAuthController::class, 'me']);
            Route::delete('/delete/profile', [ApiAuthController::class, 'delete']);
            Route::post('/firebase/update', [ApiUserFirebaseTokenController::class, 'store'])->middleware('auth:api');

            Route::resource('address', ApiUserAddressController::class)->middleware('auth:api');
        });

        //--------------------------------stores------------------------
        Route::group(['middleware' => 'auth:api', 'prefix' => 'client'], function ($router) {
            Route::resource('dashboard', ApiClientDashboard::class);
            Route::post('/update-profile', [App\Http\Controllers\Api\ApiClientDashboard::class, 'update_profile']);
            Route::resource('notifications', ApiNotificationController::class);
            Route::resource('complaints', ApiComplaintController::class);
            Route::get('complaints-types', [App\Http\Controllers\Api\ApiComplaintController::class, 'complaints_types']);
            Route::resource('orders', ApiOrderController::class);
            Route::get('recruitment-request/{id}', [App\Http\Controllers\Api\ApiOrderController::class, 'showRecruitmentRequest']);
            Route::get('recruitment-request', [App\Http\Controllers\Api\ApiRecruitmentRequest::class, 'index']);
            Route::post('recruitment-request', [App\Http\Controllers\Api\ApiRecruitmentRequest::class, 'store']);
            Route::resource('contracts', ApiContractController::class);
            Route::post('contracts/cancel', [App\Http\Controllers\Api\ApiContractController::class, 'cancel']);
            Route::resource('profile/renew-contracts', ApiRenewContractsController::class);
            Route::get('renew-contracts', [App\Http\Controllers\Api\ApiRenewContractsController::class, 'page']);
            Route::resource('sponsorship-transfers', ApiSponsorshipTransferController::class);
            Route::resource('return-requests', ApiReturnRequestController::class);
            Route::resource('customers', ApiCustomerController::class);

            // *****************

            Route::get('search-maids', [App\Http\Controllers\Api\ApiMaidController::class, 'search']);
            Route::resource('packages', ApiPackageController::class);
        });

        // Admin API routes
        Route::group(['prefix' => 'admin'], function ($router) {
            Route::get('check-trip-exists', [App\Http\Controllers\admin\AdminTravelHistoryController::class, 'checkTripExists']);
        });
        //--------------------------------------------------------
    }
);


Route::controller(Api\HomeController::class)->group(function ($router) {
    Route::post('contract_status', 'contractStatus');
    Route::get('nationalites', 'nationalites');
    Route::get('all_staff', 'allStaff');
    Route::post('store_ticket', 'storeTicket');
});

// Public API routes (3mer)
Route::group(['middleware' => 'lang', 'prefix' => 'client'], function () {
    Route::get('home', [HomeController::class, 'homeSections']);
    Route::resource('daily-lebor-demand', ApiDailyLabotDemandController::class)->only(['index', 'store']);
    Route::resource('known-service', \App\Http\Controllers\Api\ApiKnownServiceController::class)->only(['index', 'store']);
    Route::resource('select-worker', ApiSelectWorkerController::class)->only(['index']);
    Route::resource('contact-us', ApiContactController::class);
    Route::resource('settings', ApiSettingController::class);
    // Route::resource('maids', ApiMaidController::class);
    Route::resource('jobs', ApiJobTitleController::class);
    Route::get('why-us', [HomeController::class, 'whyUs']);
    Route::get('testimonials', [HomeController::class, 'testimonials']);
    Route::post('testimonials', [HomeController::class, 'storeTestimonial']);
    Route::resource('sliders', ApiSliderController::class);
    Route::resource('nationalities', ApiNationalityController::class);
    Route::resource('religions', ApiReligionController::class);
    Route::resource('job-types', ApiJobTypeController::class);
    Route::resource('all-workers', ApiMaidController::class);
    Route::get('arrival-worker', [App\Http\Controllers\Api\ApiMaidController::class, 'arrivalWorker']);
    Route::get('questions', [App\Http\Controllers\Api\ApiQuestionController::class, 'index']);
});

