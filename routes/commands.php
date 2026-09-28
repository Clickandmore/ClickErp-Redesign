<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// Cache routes per locale (route:cache alone drops the /ar and /en prefixed routes):
Route::get('route-cacher', function () {
    $exitCode = Artisan::call('route:trans:cache');
    return 'Routes cached for all locales';
});

//Clear config cache:
Route::get('config-cache', function () {
    $exitCode = Artisan::call('config:cache');
    return 'Config cache cleared';
});

// Clear application cache:
Route::get('clear-cache', function () {
    $exitCode = Artisan::call('cache:clear');
    return 'Application cache cleared';
});

// Clear view cache:
Route::get('view-clear', function () {
    $exitCode = Artisan::call('view:clear');
    return 'View cache cleared';
});
// Clear view cache:
Route::get('optimize-clear', function () {
    $exitCode = Artisan::call('optimize:clear');
    return 'View cache cleared';
});
// Migrate Database
Route::get('migrate', function () {
    $exitCode = Artisan::call('migrate');
    return 'Database Migrated';
});
//Storage Link
Route::get('storage-link', function () {
    $exitCode = Artisan::call('storage:link');
    return 'Storage linked to Public Folder';
});
//Migrate Fresh Seed
Route::get('migrate-fresh-seed', function () {
    $exitCode = Artisan::call('migrate:fresh --seed');
    return 'Migrated Fresh and Seed , Done';
});
//Generate Key
Route::get('generate-key', function () {
    $exitCode = Artisan::call('key:generate');
    return 'Key Generated, Done';
});
//DB Seed LaratrustSeeder
Route::get('db-seed', function () {
    $exitCode = Artisan::call('db:seed --class=LaratrustSeeder');
    return 'DB Seed LaratrustSeeder, Done';
});
//php artisan jwt:secret
Route::get('jwt-secret', function () {
    $exitCode = Artisan::call('jwt:secret');
    return 'jwt secret, Done';
});
