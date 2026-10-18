<?php

use App\Http\Controllers\Api\Hourly\WebsiteHourlyController;
use App\Http\Controllers\Hourly\{CashCollectionController, ClientController};
use App\Http\Middleware\{EnsureHourlyWebsiteEnabled, PreventApiResponseCaching};
use Illuminate\Support\Facades\Route;

/*
 * حجز الساعات للموقع الخارجي: /api/client/hourly/*
 * منفصلة عن واجهات التطبيق (api/app/hourly/*) وتُغلق وحدها من «العرض في الموقع الخارجي» في إعدادات الساعات.
 * ما يتطابق مع التطبيق (السعر، الطلبات، الزيارات، الفاتورة، التحويل البنكي) يعيد استخدام ClientController كما هو.
 */
Route::prefix('client/hourly')->name('api.client.hourly.')
    ->middleware(['lang', PreventApiResponseCaching::class, 'feature.hourly_rental'])
    ->group(function () {
        // العودة من ميسر تبقى متاحة حتى لو أُخفيت الخدمة عن الموقع أثناء دفع العميل.
        Route::get('payments/moyasar/return', [WebsiteHourlyController::class, 'paymentReturn'])->middleware('throttle:30,1')->name('payment-return');

        Route::middleware(EnsureHourlyWebsiteEnabled::class)->group(function () {
            Route::get('location/options', [WebsiteHourlyController::class, 'locationOptions'])->middleware('throttle:30,1')->name('location.options');
            Route::post('location', [WebsiteHourlyController::class, 'location'])->middleware('throttle:20,1')->name('location');
            Route::post('geocode', [WebsiteHourlyController::class, 'geocode'])->middleware('throttle:40,1')->name('geocode');
            Route::get('catalog', [WebsiteHourlyController::class, 'catalog'])->name('catalog');
            Route::get('packages/{package}', [WebsiteHourlyController::class, 'package'])->whereNumber('package')->name('package');
            Route::get('packages/{package}/quote', [ClientController::class, 'package'])->whereNumber('package')->name('quote');

            Route::middleware('auth:api')->group(function () {
                Route::get('checkout/{package}', [WebsiteHourlyController::class, 'checkout'])->whereNumber('package')->name('checkout');
                Route::post('addresses', [WebsiteHourlyController::class, 'address'])->middleware('throttle:20,1')->name('addresses.store');
                Route::get('slots', [WebsiteHourlyController::class, 'slots'])->name('slots');
                Route::get('orders', [ClientController::class, 'index'])->name('orders.index');
                Route::post('orders', [WebsiteHourlyController::class, 'store'])->middleware('throttle:20,1')->name('orders.store');
                Route::get('orders/{id}', [WebsiteHourlyController::class, 'show'])->whereNumber('id')->name('orders.show');
                Route::get('orders/{id}/invoice', [ClientController::class, 'invoice'])->whereNumber('id')->middleware('throttle:20,1')->name('orders.invoice');
                Route::post('orders/{id}/pay', [WebsiteHourlyController::class, 'pay'])->whereNumber('id')->middleware('throttle:10,1')->name('orders.pay');
                Route::post('orders/{id}/bank-transfer', [ClientController::class, 'bankTransfer'])->whereNumber('id')->middleware('throttle:10,1')->name('orders.bank-transfer');
                Route::post('orders/{id}/visits/{visit}/schedule', [ClientController::class, 'schedule'])->whereNumber('id')->whereNumber('visit')->middleware('throttle:20,1')->name('visits.schedule');
                Route::post('orders/{id}/visits/{visit}/reschedule', [ClientController::class, 'reschedule'])->whereNumber('id')->whereNumber('visit')->middleware('throttle:20,1')->name('visits.reschedule');
                Route::post('orders/{id}/visits/{visit}/cancel', [ClientController::class, 'cancel'])->whereNumber('id')->whereNumber('visit')->middleware('throttle:10,1')->name('visits.cancel');
                Route::get('orders/{id}/visits/{visit}/extra-hour', [ClientController::class, 'extraHourQuote'])->whereNumber('id')->whereNumber('visit')->middleware('throttle:30,1')->name('visits.extra-hour.quote');
                Route::post('orders/{id}/visits/{visit}/extra-hour', [ClientController::class, 'extraHour'])->whereNumber('id')->whereNumber('visit')->middleware('throttle:10,1')->name('visits.extra-hour');
                Route::get('orders/{id}/visits/{visit}/additional-worker', [\App\Http\Controllers\Hourly\AdditionalWorkerController::class, 'preview'])->whereNumber('id')->whereNumber('visit')->middleware('throttle:30,1')->name('visits.additional-worker.preview');
                Route::post('orders/{id}/visits/{visit}/additional-worker', [\App\Http\Controllers\Hourly\AdditionalWorkerController::class, 'store'])->whereNumber('id')->whereNumber('visit')->middleware('throttle:10,1')->name('visits.additional-worker.store');
                Route::get('orders/{id}/visits/{visit}/alternatives',[\App\Http\Controllers\Hourly\ServiceIncidentController::class,'alternatives'])->whereNumber('id')->whereNumber('visit')->name('visits.alternatives');
                Route::post('orders/{id}/visits/{visit}/choose-replacement',[\App\Http\Controllers\Hourly\ServiceIncidentController::class,'choose'])->whereNumber('id')->whereNumber('visit')->middleware('throttle:10,1')->name('visits.disruption.choose');
                Route::post('orders/{id}/visits/{visit}/recovery',[\App\Http\Controllers\Hourly\MissedVisitRecoveryController::class,'store'])->whereNumber('id')->whereNumber('visit')->middleware('throttle:10,1')->name('visits.recovery');
                Route::post('orders/{id}/visits/{visit}/early-finishes/{finish}/respond',[\App\Http\Controllers\Hourly\EarlyFinishController::class,'respond'])->whereNumber('id')->whereNumber('visit')->whereNumber('finish')->middleware('throttle:10,1')->name('visits.early-finish');
                Route::get('cash-receipts/{collection}', [CashCollectionController::class, 'customerReceipt'])->whereNumber('collection')->name('cash-receipts.show');
            });
        });
    });
