<?php

use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\ApiPhoneVerificationController;
use App\Http\Controllers\Api\EagarRequestController;
use App\Http\Controllers\Api\RentalPackageBookingController;
use App\Http\Controllers\Api\ApiUserFirebaseTokenController;
use App\Http\Controllers\APPAPIControllers\AuthController as AppAuthController;
use App\Http\Controllers\APPAPIControllers\AppHomeController;
use App\Http\Controllers\APPAPIControllers\AppOrderController;
use App\Http\Controllers\APPAPIControllers\AppComplaintController;
use App\Http\Controllers\APPAPIControllers\RentalOrderPaymentController;
use App\Http\Middleware\AppApiLang;
use App\Http\Middleware\PreventApiResponseCaching;
use App\Http\Controllers\Webhook\WhatsAppWebhookController;
use App\Http\Controllers\Api\ExternalVisitorWidgetController;
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
use \Api\ApiKnownServiceController;
use \Api\ApiSelectWorkerController;
use App\Http\Controllers\Api\HomeController;
use App\Enums\auth\AdminTypes;
use App\Models\auth\Admin;
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

Route::prefix('visitor-widget/{siteKey}')
    ->where(['siteKey' => '[A-Za-z0-9]{40,64}'])
    ->name('api.visitor-widget.')
    ->group(function () {
        Route::post('heartbeat', [ExternalVisitorWidgetController::class, 'heartbeat'])
            ->middleware('throttle:10,1')
            ->name('heartbeat');
        Route::get('messages', [ExternalVisitorWidgetController::class, 'messages'])
            ->middleware('throttle:240,1')
            ->name('messages');
        Route::post('messages', [ExternalVisitorWidgetController::class, 'send'])
            ->middleware('throttle:20,1')
            ->name('send');
    });

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
            Route::get('orders/tracking', [App\Http\Controllers\Api\ApiOrderController::class, 'tracking']);
            Route::resource('orders', ApiOrderController::class);
            Route::post('eagar-request', [EagarRequestController::class, 'store']);
            Route::post('rental-package-bookings', [RentalPackageBookingController::class, 'store']);
            Route::apiResource('user-addresses', ApiUserAddressController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::get('recruitment-request/{id}', [App\Http\Controllers\Api\ApiOrderController::class, 'showRecruitmentRequest']);
            Route::get('recruitment-request', [App\Http\Controllers\Api\ApiRecruitmentRequest::class, 'index']);
            Route::post('recruitment-request', [App\Http\Controllers\Api\ApiRecruitmentRequest::class, 'store']);
            Route::resource('contracts', ApiContractController::class);
            Route::post('contracts/cancel', [App\Http\Controllers\Api\ApiContractController::class, 'cancel']);
            Route::resource('profile/renew-contracts', ApiRenewContractsController::class);
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
Route::group(['middleware' => ['lang', PreventApiResponseCaching::class], 'prefix' => 'client'], function () {
    Route::get('home', [HomeController::class, 'homeSections']);
    Route::get('recruitment-requirements', [HomeController::class, 'recruitmentRequirements']);
    Route::get('about', [HomeController::class, 'about']);
    Route::get('renew-contracts', [App\Http\Controllers\Api\ApiRenewContractsController::class, 'page']);
    Route::post('renew-contracts', [App\Http\Controllers\Api\ApiRenewContractsController::class, 'store']);
    Route::resource('daily-lebor-demand', ApiDailyLabotDemandController::class)->only(['index', 'store']);
    Route::resource('known-service', ApiKnownServiceController::class)->only(['index', 'store']);
    Route::resource('select-worker', ApiSelectWorkerController::class)->only(['index']);
    Route::resource('contact-us', ApiContactController::class);
    Route::get('settings/policies', [App\Http\Controllers\Api\ApiSettingController::class, 'policies']);
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
    Route::get('latest-worker-cvs', [App\Http\Controllers\Api\LatestWorkerCvController::class, 'index']);
    Route::get('offers', [App\Http\Controllers\Api\ApiOfferController::class, 'index']);
    Route::get('offers/{id}', [App\Http\Controllers\Api\ApiOfferController::class, 'show'])->whereNumber('id');
    Route::get('guidelines', [App\Http\Controllers\Api\ApiGuidelineController::class, 'index']);
    Route::resource('all-workers', ApiMaidController::class);
    Route::get('arrival-worker', [App\Http\Controllers\Api\ApiMaidController::class, 'arrivalWorker']);
    Route::get('questions', [App\Http\Controllers\Api\ApiQuestionController::class, 'index']);
    Route::get('customer-service-employees', [App\Http\Controllers\Api\ApiCustomerController::class, 'index']);
    Route::post('simplified-orders', [App\Http\Controllers\Api\ApiSimplifiedOrderController::class, 'store'])
        ->middleware('throttle:5,1');
});

// *************************************************************************************************************
// APIs For Application
Route::group(
    [
        'middleware' => AppApiLang::class,
        'prefix' => 'app'
    ],
    function () {
        Route::group(['prefix' => 'auth'], function ($router) {
            Route::post('/login', [AppAuthController::class, 'login']);
            Route::post('/register', [AppAuthController::class, 'register']);
            Route::post('/send-code', [AppAuthController::class, 'sendCode']);
            Route::post('/confirm-code', [AppAuthController::class, 'confirmCode']);
            Route::post('/reset-password', [AppAuthController::class, 'resetPassword']);
            Route::post('/update/profile', [AppAuthController::class, 'updateProfile'])->middleware('auth:api');
            Route::post('/logout', [AppAuthController::class, 'logout'])->middleware('auth:api');
            Route::delete('/delete/profile', [AppAuthController::class, 'delete'])->middleware('auth:api');

            Route::post('/firebase/update', [ApiUserFirebaseTokenController::class, 'store'])->middleware('auth:api');
        });
    }
);

Route::group(['middleware' => AppApiLang::class, 'prefix' => 'user'], function () {
    Route::get('sliders', [AppHomeController::class, 'sliders']);
    Route::get('jobs', [AppHomeController::class, 'jobs']);
    Route::get('nationalities', [AppHomeController::class, 'nationalities']);
    Route::get('customers', [AppHomeController::class, 'customers']);
    Route::get('job-types', [AppHomeController::class, 'jobTypes']);
    Route::get('religions', [AppHomeController::class, 'religions']);
    Route::get('packages', [AppHomeController::class, 'packages']);
    Route::get('notifications', [AppHomeController::class, 'notifications']);
    Route::get('maids', [AppHomeController::class, 'maids']);
    Route::get('maidssearch', [AppHomeController::class, 'maidssearch']);
    Route::get('maidssearch={search}', [AppHomeController::class, 'maidssearch']);
});

Route::group(['middleware' => AppApiLang::class, 'prefix' => 'user'], function () {
    Route::get('address', [AppHomeController::class, 'address'])->middleware('auth:api');
    Route::post('address', [AppHomeController::class, 'addAddress'])->middleware('auth:api');
    Route::put('address/{id}', [AppHomeController::class, 'editAddress'])->middleware('auth:api');
    Route::delete('address/{id}', [AppHomeController::class, 'deleteAddress'])->middleware('auth:api');
    Route::post('contact-us', [AppHomeController::class, 'contactUs']);
    Route::get('settings/policies', [AppHomeController::class, 'settingsPolicies']);
    Route::get('settings', [AppHomeController::class, 'settings']);

    Route::get('orders', [AppOrderController::class, 'orders'])->middleware('auth:api');
    Route::get('orders/{id}', [AppOrderController::class, 'orderDetails'])->middleware('auth:api');
    Route::post('special-requests', [AppOrderController::class, 'createSpecialRequest'])->middleware('auth:api');
    Route::post('complaints', [AppComplaintController::class, 'addComplaint'])->middleware('auth:api');
    Route::post('orders/{order}/payments/moyasar', [RentalOrderPaymentController::class, 'store'])
        ->whereNumber('order')
        ->middleware('auth:api');
    Route::get('orders/{order}/payments/moyasar', [RentalOrderPaymentController::class, 'show'])
        ->whereNumber('order')
        ->middleware('auth:api');
});

Route::get('app/payments/moyasar/callback', [RentalOrderPaymentController::class, 'callback'])
    ->name('api.app.moyasar.callback');
Route::post('app/payments/moyasar/webhook', [RentalOrderPaymentController::class, 'webhook'])
    ->name('api.app.moyasar.webhook');

Route::group(['middleware' => AppApiLang::class, 'prefix' => 'home'], function () {
    Route::post('complete_request/{id}', [AppOrderController::class, 'createOrder'])->middleware('auth:api');
});

// *************************************************************************************************************
