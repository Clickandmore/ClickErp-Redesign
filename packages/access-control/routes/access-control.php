<?php

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['adminLocale', 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
    ],
    function () {
        Route::group(['prefix' => 'dashboard'], function () {
            Route::group(['middleware' => ['web', 'admin']], function () {
                Route::group([
                    'prefix' => 'packages/access-control',
                    'as' => 'packages.access-control.',
                ], function () {
                    Route::get('admins/{any?}', function () {
                        return redirect()->route('admins.index');
                    })->where('any', '.*')->name('admins.index');

                    Route::get('roles/{any?}', function () {
                        return redirect()->route('dash.roles.index');
                    })->where('any', '.*')->name('roles.index');

                    Route::get('role-permissions/{any?}', function () {
                        return redirect()->route('dash.developers.perms.index');
                    })->where('any', '.*')->name('role-permissions.index');
                });
            });
        });
    }
);
