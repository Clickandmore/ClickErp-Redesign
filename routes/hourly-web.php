<?php

use App\Http\Controllers\Web\HourlyRentalController;
use App\Http\Controllers\Hourly\ClientController;
use App\Http\Middleware\{HourlyWebsiteCustomer, EnsureHourlyCommerceReady, EnsureHourlyWebsiteEnabled};
use Illuminate\Support\Facades\Route;

// Included inside the localized web.* group; all mutations retain web CSRF protection.
Route::prefix('hourly-rental')->name('hourly.')->middleware(['feature.hourly_rental', EnsureHourlyCommerceReady::class])->group(function () {
    // العودة من ميسر تبقى متاحة حتى لو أُخفيت الخدمة عن الموقع أثناء دفع العميل.
    Route::get('payment-return', [HourlyRentalController::class,'paymentReturn'])->middleware('throttle:30,1')->name('payment-return');
    Route::middleware(EnsureHourlyWebsiteEnabled::class)->group(function () {
    Route::get('/', [HourlyRentalController::class,'index'])->name('index');
    Route::middleware(HourlyWebsiteCustomer::class)->group(function () {
        Route::get('book', [HourlyRentalController::class,'catalog'])->name('catalog');
        Route::get('packages/{package}', [HourlyRentalController::class,'package'])->whereNumber('package')->name('package');
        Route::get('checkout/{package}', [HourlyRentalController::class,'checkout'])->whereNumber('package')->name('checkout');
        Route::get('orders', [HourlyRentalController::class,'orders'])->name('orders');
        Route::get('cash-receipts/{collection}',[\App\Http\Controllers\Hourly\CashCollectionController::class,'customerReceipt'])->whereNumber('collection')->name('cash-receipt');
        Route::post('orders', [HourlyRentalController::class,'store'])->middleware('throttle:20,1')->name('store');
        Route::get('orders/{id}', [HourlyRentalController::class,'order'])->whereNumber('id')->name('order');
        Route::post('orders/{id}/pay', [HourlyRentalController::class,'pay'])->whereNumber('id')->middleware('throttle:10,1')->name('pay');
        Route::post('orders/{id}/bank-transfer', [ClientController::class,'bankTransfer'])->whereNumber('id')->middleware('throttle:10,1')->name('bank-transfer');
        Route::get('orders/{id}/visits/{visit}/additional-worker',[\App\Http\Controllers\Hourly\AdditionalWorkerController::class,'preview'])->whereNumber('id')->whereNumber('visit')->name('visit.additional-worker.preview');
        Route::post('orders/{id}/visits/{visit}/additional-worker',[\App\Http\Controllers\Hourly\AdditionalWorkerController::class,'store'])->whereNumber('id')->whereNumber('visit')->middleware('throttle:10,1')->name('visit.additional-worker.store');
        Route::get('orders/{id}/visits/{visit}/extra-hour', [ClientController::class,'extraHourQuote'])->whereNumber('id')->whereNumber('visit')->middleware('throttle:30,1')->name('visit.extra-hour.quote');
        Route::post('orders/{id}/visits/{visit}/extra-hour', [ClientController::class,'extraHour'])->whereNumber('id')->whereNumber('visit')->middleware('throttle:10,1')->name('visit.extra-hour');
        Route::post('orders/{id}/visits/{visit}/choose-replacement',[\App\Http\Controllers\Hourly\ServiceIncidentController::class,'choose'])->whereNumber('id')->whereNumber('visit')->middleware('throttle:10,1')->name('visit.disruption.choose');
        Route::post('orders/{id}/visits/{visit}/recovery', [\App\Http\Controllers\Hourly\MissedVisitRecoveryController::class,'store'])->whereNumber('id')->whereNumber('visit')->middleware('throttle:10,1')->name('visit.recovery');
        Route::get('orders/{id}/visits/{visit}/recovery-slots', [\App\Http\Controllers\Hourly\MissedVisitRecoveryController::class,'customerSlots'])->whereNumber('id')->whereNumber('visit')->middleware('throttle:30,1')->name('visit.recovery-slots');
        Route::post('orders/{id}/visits/{visit}/early-finishes/{finish}/respond', [\App\Http\Controllers\Hourly\EarlyFinishController::class,'respond'])->whereNumber('id')->whereNumber('visit')->whereNumber('finish')->middleware('throttle:10,1')->name('visit.early-finish');
        Route::get('data/slots', [HourlyRentalController::class,'slots'])->name('slots');
        Route::get('data/quote/{package}', [ClientController::class,'package'])->whereNumber('package')->name('quote');
        Route::post('data/address', [HourlyRentalController::class,'address'])->middleware('throttle:20,1')->name('address');
        Route::post('data/geocode', [HourlyRentalController::class,'geocode'])->middleware('throttle:15,1')->name('geocode');
    });
    });
});
