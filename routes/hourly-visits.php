<?php
use Illuminate\Support\Facades\Route;
Route::middleware(['auth:admin','admin.route.permission','feature.hourly_rental'])->group(function(){
    foreach(['panel/orders/visit-list'=>'index','orders/unassigned-visits'=>'unassigned','orders/past-uncompleted-visits'=>'past'] as $path=>$target){
        Route::get($path,fn(\Illuminate\Http\Request $request)=>redirect()->route('admin.hourly-visits.'.$target,$request->query()))->name('admin.hourly-visits.alias-'.$target);
    }
    // مسارات النظام المرجعي لإدارة المسار وتتبع GPS.
    Route::get('panel/orders/routing',fn(\Illuminate\Http\Request $request)=>redirect()->route('admin.hourly-routing.index',$request->query()))->name('admin.hourly-routing.alias');
    Route::get('panel/customer',fn(\Illuminate\Http\Request $request)=>redirect()->route('admin.hourly-customers.index',$request->query()))->name('admin.hourly-customers.alias');
    Route::get('panel/gps-tracking',fn(\Illuminate\Http\Request $request)=>redirect()->route('admin.hourly-gps.index',$request->query()))->name('admin.hourly-gps.alias');
});

Route::prefix('admin/hourly')->middleware(['auth:admin','admin.route.permission','feature.hourly_rental'])->group(function(){
    Route::get('cash-remittances/{remittance}/receipt',[\App\Http\Controllers\Hourly\CashCollectionController::class,'remittanceReceipt'])->whereNumber('remittance')->name('admin.hourly-cash.remittance-receipt');
    Route::get('cash-custody',[\App\Http\Controllers\Hourly\CashCollectionController::class,'index'])->name('admin.hourly-cash.index');
    Route::post('cash-custody/{collection}/remit',[\App\Http\Controllers\Hourly\CashCollectionController::class,'remit'])->whereNumber('collection')->name('admin.hourly-cash.remit');
    Route::get('cash-custody/{collection}/receipt',[\App\Http\Controllers\Hourly\CashCollectionController::class,'adminReceipt'])->whereNumber('collection')->name('admin.hourly-cash.receipt');
    Route::post('orders/{order}/visits/{visit}/cash-collector',[\App\Http\Controllers\Hourly\CashCollectionController::class,'collector'])->name('admin.hourly-cash.collector');
});