<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Hourly\ClientController;
use App\Http\Controllers\Hourly\{DriverAppController, DriverAuthController, MaidAuthController, WorkerAppController};
use App\Http\Controllers\Hourly\{LocationPingController, StaffNotesController, WorkerVisitOffersController};
use App\Http\Middleware\{EnsureHourlyDriverAccess, EnsureHourlyMaidAccess, HourlyStaffApiResponse};

/*
|--------------------------------------------------------------------------
| تطبيق الفريق الميداني: السائق والعاملة
|--------------------------------------------------------------------------
| كل مجموعة مسجَّلة ببادئتين: البادئة الأصلية للموديول (api/app/hourly/…)
| والبادئة التي يستدعيها التطبيق (api/driver, api/worker). نفس المتحكمات
| ونفس الحارس، فلا يوجد عقد مزدوج ولا يحتاج التطبيق تعديل ثوابته.
| الغلاف الموحد { code, status, message, data } يضيفه HourlyStaffApiResponse.
*/

$driverRoutes = function () {
    Route::post('login', [DriverAuthController::class, 'mobileLogin'])->middleware('throttle:5,1');
    Route::post('verify-otp', [DriverAuthController::class, 'otpVerify'])->middleware('throttle:10,1');
    Route::middleware(EnsureHourlyDriverAccess::class.':mobile')->group(function () {
        Route::get('me', [DriverAuthController::class, 'me']);
        Route::post('devices', [DriverAuthController::class, 'devices']);
        Route::post('refresh', [DriverAuthController::class, 'refresh']);
        Route::post('logout', [DriverAuthController::class, 'mobileLogout']);
        Route::post('location', [LocationPingController::class, 'driver'])->middleware('throttle:60,1');
        Route::post('shift', [DriverAppController::class, 'shift']);
        Route::get('offers', [DriverAppController::class, 'offers']);
        Route::get('trips/today', [DriverAppController::class, 'today']);
        // مشاوير مدى تواريخ (سابقة/حالية/قادمة) + تشخيص سبب الفراغ.
        Route::get('trips', [DriverAppController::class, 'trips']);
        Route::get('trips/diagnostics', [DriverAppController::class, 'diagnostics']);
        Route::post('trips/{trip}/accept', [DriverAppController::class, 'accept'])->whereNumber('trip');
        Route::post('trips/{trip}/reject', [DriverAppController::class, 'reject'])->whereNumber('trip');
        Route::post('trips/{trip}/start', [DriverAppController::class, 'start'])->whereNumber('trip');
        foreach (['arrived' => 'arrived', 'shelter-pickup' => 'shelterPickup', 'deliver' => 'deliver',
            'pick-up' => 'pickUp', 'shelter-return' => 'shelterReturn', 'fail' => 'fail'] as $uri => $method) {
            Route::post('stops/{stop}/'.$uri, [DriverAppController::class, $method])->whereNumber('stop');
        }
        Route::get('pickup-requests', [DriverAppController::class, 'pickupRequests']);
        Route::get('earnings', [DriverAppController::class, 'earnings']);
        Route::get('notifications', [DriverAppController::class, 'notifications']);
        Route::post('notifications/read', [DriverAppController::class, 'readNotifications']);
        Route::get('sos', [DriverAppController::class, 'sos']);
        Route::post('sos', [DriverAppController::class, 'storeSos'])->middleware('throttle:10,1');
        Route::post('sos/{alert}/cancel', [DriverAppController::class, 'cancelSos'])->whereNumber('alert')->middleware('throttle:10,1');
        Route::get('visits/{visit}/notes', [StaffNotesController::class, 'driver'])->whereNumber('visit');
    });
};

$workerRoutes = function () {
    Route::post('login', [MaidAuthController::class, 'mobileLogin'])->middleware('throttle:5,1');
    Route::post('verify-otp', [MaidAuthController::class, 'otpVerify'])->middleware('throttle:10,1');
    Route::middleware(EnsureHourlyMaidAccess::class.':mobile')->group(function () {
        Route::get('me', [MaidAuthController::class, 'me']);
        Route::post('devices', [MaidAuthController::class, 'devices']);
        Route::post('refresh', [MaidAuthController::class, 'refresh']);
        Route::post('logout', [MaidAuthController::class, 'mobileLogout']);
        Route::post('location', [LocationPingController::class, 'maid'])->middleware('throttle:60,1');
        Route::get('hours', [WorkerAppController::class, 'hours']);
        Route::get('visits/today', [WorkerAppController::class, 'today']);
        Route::post('visits/{visit}/arrived', [WorkerAppController::class, 'arrived'])->whereNumber('visit');
        Route::post('visits/{visit}/finish', [WorkerAppController::class, 'finish'])->whereNumber('visit');
        Route::get('leaves', [WorkerAppController::class, 'leaves']);
        Route::post('leaves', [WorkerAppController::class, 'storeLeave']);
        Route::get('sos', [WorkerAppController::class, 'sos']);
        Route::post('sos', [WorkerAppController::class, 'storeSos'])->middleware('throttle:10,1');
        Route::post('sos/{alert}/cancel', [WorkerAppController::class, 'cancelSos'])->whereNumber('alert')->middleware('throttle:10,1');
        Route::get('earnings', [WorkerAppController::class, 'earnings']);
        Route::get('notifications', [WorkerAppController::class, 'notifications']);
        Route::post('notifications/read', [WorkerAppController::class, 'readNotifications']);
        // عروض الزيارات للعاملة (مسار الموديول القائم قبل التطبيق).
        Route::get('offers', [WorkerVisitOffersController::class, 'index']);
        Route::post('offers/{visit}/claim', [WorkerVisitOffersController::class, 'claim'])->whereNumber('visit');
        Route::get('visits/{visit}/notes', [StaffNotesController::class, 'worker'])->whereNumber('visit');
    });
};

foreach (['app/hourly/driver', 'driver'] as $prefix) {
    Route::prefix($prefix)->middleware(['feature.hourly_rental', HourlyStaffApiResponse::class])->group($driverRoutes);
}
foreach (['app/hourly/maid', 'app/hourly/worker', 'worker'] as $prefix) {
    Route::prefix($prefix)->middleware(['feature.hourly_rental', HourlyStaffApiResponse::class])->group($workerRoutes);
}

/*
|--------------------------------------------------------------------------
| تطبيق العميل: التأجير بالساعة
|--------------------------------------------------------------------------
*/
Route::prefix('app/hourly')->middleware('feature.hourly_rental')->group(function () {
    Route::get('payments/moyasar/callback',[ClientController::class,'callback'])->middleware('throttle:30,1')->name('api.hourly.moyasar.callback');
    Route::post('payments/moyasar/webhook',[ClientController::class,'webhook'])->middleware('throttle:60,1')->name('api.hourly.moyasar.webhook');
    Route::middleware('auth:api')->group(function () {
        Route::middleware(\App\Http\Middleware\HourlyApiLang::class)->group(function () {
        Route::get('cities',[ClientController::class,'cities']);
        Route::get('addresses',[ClientController::class,'addresses']);
        Route::post('addresses',[ClientController::class,'address']);
        Route::get('slots',[ClientController::class,'slots']);
        Route::get('package-tiers',[ClientController::class,'tiers']);
        Route::get('packages',[ClientController::class,'packages']);
        Route::get('packages/{package}',[ClientController::class,'package'])->whereNumber('package');
        Route::get('packages/{package}/config',[\App\Http\Controllers\Hourly\BookingConfigController::class,'show'])->whereNumber('package');
        Route::get('packages/{package}/workers',[ClientController::class,'availableWorkers'])->whereNumber('package');
        Route::get('categories',[\App\Http\Controllers\Hourly\CategoryApiController::class,'index']);
        Route::get('categories/{id}/config',[\App\Http\Controllers\Hourly\CategoryApiController::class,'config'])->whereNumber('id');
        Route::put('addresses/{id}/location',[ClientController::class,'location'])->whereNumber('id');
        Route::post('location',[LocationPingController::class,'customer'])->middleware('throttle:30,1');
        Route::get('orders',[ClientController::class,'index']);
        Route::post('orders',[ClientController::class,'store']);
        Route::get('orders/{id}',[ClientController::class,'show'])->whereNumber('id');
        Route::get('orders/{id}/invoice',[ClientController::class,'invoice'])->whereNumber('id');
        Route::get('orders/{id}/visits/{visit}',[ClientController::class,'visit'])->whereNumber('id')->whereNumber('visit');
        Route::get('orders/{id}/visits/{visit}/tracking',[ClientController::class,'tracking'])->whereNumber('id')->whereNumber('visit')->middleware('throttle:60,1');
        Route::post('orders/{id}/visits/{visit}/schedule',[ClientController::class,'schedule'])->whereNumber('id')->whereNumber('visit');
        Route::post('orders/{id}/visits/{visit}/reschedule',[ClientController::class,'reschedule'])->whereNumber('id')->whereNumber('visit');
        Route::post('orders/{id}/visits/{visit}/cancel',[ClientController::class,'cancel'])->whereNumber('id')->whereNumber('visit');
        Route::post('orders/{id}/visits/{visit}/extra-hour',[ClientController::class,'extraHour'])->whereNumber('id')->whereNumber('visit');
        Route::post('orders/{id}/visits/{visit}/rate',[ClientController::class,'rate'])->whereNumber('id')->whereNumber('visit');
        Route::post('orders/{id}/payments/moyasar',[ClientController::class,'pay'])->whereNumber('id');
        Route::get('orders/{id}/payments/moyasar',[ClientController::class,'payment'])->whereNumber('id');
        Route::post('orders/{id}/payments/bank-transfer',[ClientController::class,'bankTransfer'])->whereNumber('id');
        });
    });
});
