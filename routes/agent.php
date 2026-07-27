<?php

use App\Http\Controllers\Agent\AgentBulkImportController;
use App\Http\Controllers\Agent\AgentRecruitmentProcedureController;
use App\Http\Controllers\Agent\AuthController;
use App\Http\Controllers\Agent\DashboardController;
use App\Http\Middleware\AgentLocalizationMiddleware;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => [AgentLocalizationMiddleware::class, 'localeViewPath'],
    ],
    function () {
        Route::prefix('agent')->name('agent.')->group(function () {
            Route::get('login', [AuthController::class, 'showLoginForm'])->name('login.form');
            Route::post('login', [AuthController::class, 'login'])->name('login');

            Route::middleware('agent')->group(function () {
                Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
                Route::get('maids', [DashboardController::class, 'maids'])->name('maids.index');
                Route::get('maids/create', [DashboardController::class, 'createMaid'])->name('maids.create');
                Route::post('maids/store', [DashboardController::class, 'storeMaid'])->name('maids.store');
                Route::get('maids/create-rapid', [DashboardController::class, 'createRapidMaid'])->name('maids.create_rapid_cv');
                Route::post('maids/store-rapid', [DashboardController::class, 'storeRapidMaid'])->name('maids.store_rapid_cv');
                Route::post('maids/extract-cv', [DashboardController::class, 'extractCv'])->name('maids.extract-cv');
                Route::get('maids/bulk-import/template', [AgentBulkImportController::class, 'template'])->name('maids.bulk-import.template');
                Route::get('maids/bulk-import/create', [AgentBulkImportController::class, 'create'])->name('maids.bulk-import.create');
                Route::post('maids/bulk-import', [AgentBulkImportController::class, 'store'])->name('maids.bulk-import.store');
                Route::get('maids/bulk-import/{agentMaidBulkImport}', [AgentBulkImportController::class, 'show'])->name('maids.bulk-import.show');
                Route::get('maids/bulk-import/{agentMaidBulkImport}/download', [AgentBulkImportController::class, 'download'])->name('maids.bulk-import.download');
                Route::post('maids/{maid}/notes', [DashboardController::class, 'updateNotes'])->name('maids.update-notes');
                Route::post('maids/status-change-requests/bulk', [DashboardController::class, 'bulkRequestMaidSuspension'])
                    ->name('maids.status-change-requests.bulk');
                Route::post('maids/{maid}/status-change-requests', [DashboardController::class, 'requestMaidStatusChange'])
                    ->name('maids.status-change-requests.store');
                Route::get('maids/{maid}/edit', [DashboardController::class, 'editMaid'])->name('maids.edit');
                Route::put('maids/{maid}', [DashboardController::class, 'updateMaid'])->name('maids.update');
                Route::get('maids/{maid}', [DashboardController::class, 'showMaid'])->name('maids.show');
                Route::get('contracts', [DashboardController::class, 'contracts'])->name('contracts.index');
                Route::get('contracts/{contract}', [DashboardController::class, 'showContract'])->name('contracts.show');
                Route::get('external-office-dispatches', [DashboardController::class, 'dispatches'])->name('dispatches.index');
                Route::get('invoices', [DashboardController::class, 'invoices'])->name('invoices.index');
                Route::get('philippines-tracking', [AgentRecruitmentProcedureController::class, 'index'])->name('philippines-tracking.index');
                Route::post('philippines-tracking/{maid}/update-step', [AgentRecruitmentProcedureController::class, 'updateStep'])->name('philippines-tracking.update-step');
                Route::post('philippines-tracking/{maid}/bulk-update', [AgentRecruitmentProcedureController::class, 'bulkUpdate'])->name('philippines-tracking.bulk-update');
                Route::get('notifications', [DashboardController::class, 'notifications'])->name('notifications.index');
                Route::post('notifications/mark-all-read', [DashboardController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
                Route::post('notifications/{id}/read', [DashboardController::class, 'markAsRead'])->name('notifications.read');
                Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            });
        });
    }
);
