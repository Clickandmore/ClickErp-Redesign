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
