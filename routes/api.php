<?php

use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\ApiPhoneVerificationController;
use App\Http\Controllers\Api\ApiUserFirebaseTokenController;
use \Api\ApiContactController;
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
use \Api\ApiOrderController;
use \Api\ApiNotificationController;
use \Api\ApiComplaintController;
use \Api\ApiReturnRequestController;
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

Route::group(
    [
        'middleware' => 'lang'
    ],
    function () {
        Route::group(['prefix' => 'auth'], function ($router) {
            Route::post('/send-code', [ApiPhoneVerificationController::class, 'store']);
            Route::post('/confirm-code', [ApiPhoneVerificationController::class, 'confirm_code']);
            Route::post('/reset-password', [ApiPhoneVerificationController::class, 'reset_password'])->middleware('auth:api');

            Route::post('/register', [ApiAuthController::class, 'register']);
            Route::post('/login', [ApiAuthController::class, 'login']);
            Route::post('/logout', [ApiAuthController::class, 'logout']);
            Route::post('/refresh', [ApiAuthController::class, 'refresh']);
            Route::post('/update/profile', [ApiAuthController::class, 'update']);
            Route::get('/profile', [ApiAuthController::class, 'me']);
            Route::delete('/delete/profile', [ApiAuthController::class, 'delete']);
            Route::post('/update/password', [ApiAuthController::class, 'update_password']);
            Route::post('/firebase/update', [ApiUserFirebaseTokenController::class, 'store'])->middleware('auth:api');

            Route::resource('address', ApiUserAddressController::class)->middleware('auth:api');
        });

        //--------------------------------stores------------------------
        Route::group(['middleware' => 'auth:api', 'prefix' => 'user'], function ($router) {
            Route::resource('contact-us', ApiContactController::class);
            Route::resource('settings', ApiSettingController::class);
            Route::resource('sliders', ApiSliderController::class);
            Route::resource('nationalities', ApiNationalityController::class);
            Route::resource('religions', ApiReligionController::class);
            Route::resource('jobs', ApiJobTitleController::class);
            Route::resource('job-types', ApiJobTypeController::class);
            Route::resource('maids', ApiMaidController::class);
            Route::get('search-maids', [App\Http\Controllers\Api\ApiMaidController::class, 'search']);
            Route::resource('packages', ApiPackageController::class);
            Route::resource('customers', ApiCustomerController::class);
            Route::resource('orders', ApiOrderController::class);
            Route::resource('notifications', ApiNotificationController::class);
            Route::resource('complaints', ApiComplaintController::class);
            Route::resource('return-requests', ApiReturnRequestController::class);
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
