<?php
use App\Http\Controllers\Hourly\MaidAuthController;
use App\Http\Middleware\EnsureHourlyMaidAccess;
use Illuminate\Support\Facades\Route;
Route::prefix('hourly-maid')->name('hourly-maid.')->middleware('feature.hourly_rental')->group(function () {
    Route::get('login',[MaidAuthController::class,'loginForm'])->name('login');
    Route::post('login',[MaidAuthController::class,'webLogin'])->middleware('throttle:5,1')->name('authenticate');
    Route::middleware(EnsureHourlyMaidAccess::class.':web')->group(function () {
        Route::get('/',[MaidAuthController::class,'home'])->name('home');
        Route::post('visits/{visit}/collect-cash',[\App\Http\Controllers\Hourly\CashCollectionController::class,'webCollect'])->whereNumber('visit')->name('cash.collect');
        Route::post('visits/{visit}/action',[\App\Http\Controllers\Hourly\CashCollectionController::class,'webAction'])->whereNumber('visit')->name('visits.action');
        Route::get('cash/{collection}/receipt',[\App\Http\Controllers\Hourly\CashCollectionController::class,'staffReceipt'])->whereNumber('collection')->name('cash.receipt');
        Route::get('offers',[\App\Http\Controllers\Hourly\WorkerVisitOffersController::class,'index'])->name('offers');
        Route::post('offers/{visit}/claim',[\App\Http\Controllers\Hourly\WorkerVisitOffersController::class,'claim'])->whereNumber('visit')->name('offers.claim');
        Route::post('logout',[MaidAuthController::class,'webLogout'])->name('logout');
    });
});
