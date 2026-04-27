<?php


use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Nami\Crm\Http\Controllers\AreaController;
use Nami\Crm\Http\Controllers\AttachmentCategoryController;
use Nami\Crm\Http\Controllers\CategoryController;
use Nami\Crm\Http\Controllers\ClientAttachmentController;
use Nami\Crm\Http\Controllers\ClientController;
use Nami\Crm\Http\Controllers\ClientReportController;
use Nami\Crm\Http\Controllers\ConfirmClientController;
use Nami\Crm\Http\Controllers\FollowClientController;
use Nami\Crm\Http\Controllers\FollowClientsCategoryController;
use Nami\Crm\Http\Controllers\HomeController;
use Nami\Crm\Http\Controllers\MessageController;
use Nami\Crm\Http\Controllers\MyFollowClientController;
use Nami\Crm\Http\Controllers\PollClientController;
use Nami\Crm\Http\Controllers\PollController;
use Nami\Crm\Http\Controllers\PriceOfferController;
use Nami\Crm\Http\Controllers\PriceOfferPrintController;
use Nami\Crm\Http\Controllers\QuestionController;
use Nami\Crm\Http\Controllers\SendPollClientController;
use Nami\Crm\Http\Controllers\SendPollUserController;
use Nami\Crm\Http\Controllers\SocialContactController;

/*
|--------------------------------------------------------------------------
| Finance Routes
|--------------------------------------------------------------------------
|
| Here is where you can register user routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "user" middleware group. Now create something great!
|
*/


Route::group(
    [
        'as' => 'crm.',
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ["adminLocale", 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath']
    ], function () {
    Route::group(["middleware" => ['web', 'admin'], "prefix" => "crm"], function () {

        Route::resource("/notifications", HomeController::class);
        Route::resource('categories', CategoryController::class);
        Route::resource('areas', AreaController::class);
        Route::resource('social_contacts', SocialContactController::class);
        Route::resource('attachment_categories', AttachmentCategoryController::class);
        Route::resource('client_attachments', ClientAttachmentController::class);
        Route::resource('clients', ClientController::class);
        Route::resource('sendPoll', SendPollClientController::class);
        Route::resource('sendPollUser', SendPollUserController::class);
        Route::resource('clients_report', ClientReportController::class);
        Route::resource('follow-clients', FollowClientController::class);
        Route::resource('my-followups', MyFollowClientController::class);
//        Route::get('follow-clients/{id}/print', [\App\Http\Controllers\Crm\FollowClientPrintController::class, 'print'])->name('follow-clients.print');
        Route::resource('print-price-offers', PriceOfferPrintController::class);

        Route::resource('price-offers', PriceOfferController::class);
        Route::resource('polls', PollController::class);

        Route::resource('poll-clients', PollClientController::class);
        Route::resource('questions', QuestionController::class);
        Route::resource('follow_clients_categories', FollowClientsCategoryController::class);
        Route::resource('confirm-clients', ConfirmClientController::class);
        Route::resource('messages-reports', MessageController::class);

    });
});
