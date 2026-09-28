<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Hourly\PanelBookingController;

Route::prefix('panel/orders')->middleware(['auth:admin','admin.route.permission','feature.hourly_rental'])->group(function(){
    Route::get('index/act/{act}',[\App\Http\Controllers\Hourly\OrderController::class,'index'])->where('act','all|new')->name('admin.hourly-orders.panel-index');
    Route::get('details/id/{order}',[\App\Http\Controllers\Hourly\OrderController::class,'show'])->name('admin.hourly-orders.panel-details');
});

Route::prefix('panel/add-order')->middleware(['auth:admin','admin.route.permission','feature.hourly_rental'])->group(function () {
    Route::get('/',[PanelBookingController::class,'create'])->name('admin.hourly-orders.wizard');
    Route::get('customers',[PanelBookingController::class,'customers'])->name('admin.hourly-orders.customers');
    Route::get('addresses',[PanelBookingController::class,'addresses'])->name('admin.hourly-orders.addresses');
    Route::post('map-link',[PanelBookingController::class,'mapLink'])->middleware('throttle:15,1')->name('admin.hourly-orders.map-link');
    Route::post('geocode',[PanelBookingController::class,'geocode'])->middleware('throttle:30,1')->name('admin.hourly-orders.geocode');
    foreach (['catalog','quote','availability','store'] as $action) {
        Route::post($action,[PanelBookingController::class,$action])->name('admin.hourly-orders.wizard-'.$action);
    }
});
