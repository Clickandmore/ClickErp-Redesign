<?php

use Illuminate\Support\Facades\Route;
use Nami\Crm\Http\Controllers\SitePollController;

Route::get('start-poll-show/{poll_id}',[SitePollController::class,'indexShow'])->name('start-poll-show');
