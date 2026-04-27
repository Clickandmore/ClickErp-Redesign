<?php

use App\Http\Controllers\admin\ActivityLogController;
use App\Http\Controllers\admin\AdminContractController;
use App\Http\Controllers\admin\AdminFullOrderContractController;
use App\Http\Controllers\admin\AdminSettingsController;
use App\Http\Controllers\admin\AdminShelterWorkersContractCTLR;
use App\Http\Controllers\admin\AdminTravelHistoryController;
use App\Http\Controllers\admin\ContractReturnRequestController;
use App\Http\Controllers\admin\FormSettingsController;
use App\Http\Controllers\admin\office\OfficeController;
use App\Http\Controllers\admin\PrintContractController;
use App\Http\Controllers\admin\ShelterWorkersController;
use App\Http\Controllers\ingaz\AboutUsController;
use App\Http\Controllers\ingaz\ArrivalWorkerController;
use App\Http\Controllers\ingaz\ClientController;
use App\Http\Controllers\ingaz\ComplaintController;
use App\Http\Controllers\ingaz\ComplaintTypeController;
use App\Http\Controllers\ingaz\ContactController;
use App\Http\Controllers\ingaz\CountryPoliceController;
use App\Http\Controllers\ingaz\CvConvertController;
use App\Http\Controllers\ingaz\DeportationTravelController;
use App\Http\Controllers\ingaz\DevelopSettingController;
use App\Http\Controllers\ingaz\FastNewController;
use App\Http\Controllers\ingaz\FeatureController;
use App\Http\Controllers\ingaz\InvoiceController;
use App\Http\Controllers\ingaz\JobTitleController;
use App\Http\Controllers\ingaz\MaidBulkImportController;
use App\Http\Controllers\ingaz\MaidController;
use App\Http\Controllers\ingaz\MaidTestController;
use App\Http\Controllers\ingaz\MakeAccountsForClientsController;
use App\Http\Controllers\ingaz\MusanedTransferController;
use App\Http\Controllers\ingaz\NationalityController;
use App\Http\Controllers\ingaz\NotificationController;
use App\Http\Controllers\ingaz\NotificationSettingsController;
use App\Http\Controllers\ingaz\OrderController;
use App\Http\Controllers\ingaz\PackageController;
use App\Http\Controllers\ingaz\PoliceSettingController;
use App\Http\Controllers\ingaz\ProfessionalApplicationController;
use App\Http\Controllers\ingaz\QuestionController;
use App\Http\Controllers\ingaz\RecruitmentContractController;
use App\Http\Controllers\ingaz\RecruitmentContractExternalOfficeDispatchController;
use App\Http\Controllers\ingaz\RecruitmentRequestController;
use App\Http\Controllers\ingaz\ReligionController;
use App\Http\Controllers\ingaz\RenewContractController;
use App\Http\Controllers\ingaz\RentalContractInvoiceController;
use App\Http\Controllers\ingaz\RentalContractTermsController;
use App\Http\Controllers\ingaz\RentalOrderController;
use App\Http\Controllers\ingaz\RentContractController;
use App\Http\Controllers\ingaz\ReportController;
use App\Http\Controllers\ingaz\RequirementsController;
use App\Http\Controllers\ingaz\ReturnRequestController;
use App\Http\Controllers\ingaz\SelectWorkerController;
use App\Http\Controllers\ingaz\SelectWorkerSettingsController;
use App\Http\Controllers\ingaz\ServiceController;
use App\Http\Controllers\ingaz\SettingController;
use App\Http\Controllers\ingaz\ShelterContractController;
use App\Http\Controllers\ingaz\ShelterPlaces;
use App\Http\Controllers\ingaz\SkillController;
use App\Http\Controllers\ingaz\SliderController;
use App\Http\Controllers\ingaz\SocialStatusController;
use App\Http\Controllers\ingaz\SponsorshipTransferController;
use App\Http\Controllers\ingaz\StatisticController;
use App\Http\Controllers\ingaz\StepController;
use App\Http\Controllers\ingaz\TestimonialController;
use App\Http\Controllers\ingaz\TransferContractTermsController;
use App\Http\Controllers\ingaz\TransferServiceContractController;
use App\Http\Controllers\ingaz\UserController;
use App\Http\Controllers\ingaz\WhatDoController;
use App\Http\Controllers\ingaz\WhatDoSettingController;
use App\Http\Controllers\ingaz\WhyUsController;
use App\Http\Controllers\Permissions\PermissionsController;
use App\Http\Controllers\Permissions\RolesController;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
    ],
    function () {
        Route::group(["prefix" => "admin", "middleware" => ["auth:admin", "admin.route.permission"]], function () {

            Route::resource('partenrs', ClientController::class);
            Route::resource('why-us', WhyUsController::class);
            Route::resource('arrival-workers', ArrivalWorkerController::class);
            Route::resource('testimonials', TestimonialController::class);
            Route::resource('select-workers', SelectWorkerController::class);
            Route::resource('select-workers-settings', SelectWorkerSettingsController::class);
            Route::resource('requiremens', RequirementsController::class);
            Route::resource('cv-convert', CvConvertController::class);

            Route::resource('partenrs', ClientController::class);
            Route::resource('requiremens', RequirementsController::class);
            Route::resource('fast-news', FastNewController::class);
            Route::resource('features', FeatureController::class);
            Route::resource('what-do', WhatDoController::class);
            Route::resource('what-do-setting', WhatDoSettingController::class);
            Route::resource('renew-contracts', RenewContractController::class);
            Route::resource('known-service-requests', \App\Http\Controllers\ingaz\KnownServiceRequestController::class)->only(['index', 'show', 'update', 'destroy']);
            Route::resource('professional_application', ProfessionalApplicationController::class);
            Route::resource('recruitment_requests', RecruitmentRequestController::class);

            Route::get('getMaidByNationality/{nationalityId}', [RecruitmentContractController::class, 'getMaidByNationality'])->name('getMaidByNationality');
            Route::get('clients/{id}/details', [RecruitmentContractController::class, 'getClientDetails'])->name('clients.details');

            //** NEW */
            Route::get('getContractEdit/{id}', [AdminContractController::class, 'getContractEdit'])->name('getContractEdit');
            Route::post('updateContractInfo', [AdminContractController::class, 'updateContractInfo'])->name('updateContractInfo');
            Route::get('get-contract-details', [\App\Http\Controllers\ingaz\ShelterContractController::class, 'getContractDetails'])->name('get.contract.details');
            Route::get('search-contracts-by-passport', [\App\Http\Controllers\ingaz\ShelterContractController::class, 'searchByPassport'])->name('search.contracts.by.passport');
            //** */
            Route::get('viewFullOrderContract', [AdminFullOrderContractController::class, 'viewFullOrderContract'])->name('viewFullOrderContract');
            Route::post('createFullOrderContract', [AdminFullOrderContractController::class, 'createFullOrderContract'])->name('createFullOrderContract');
            /** */
            Route::get('print/rental-contract/{id}', [AdminContractController::class, 'printRentalContract'])->name('print.rental.contract');
            Route::post('contracts/{id}/save-signature', [AdminContractController::class, 'saveSignature'])->name('admin.contracts.save-signature');
            //** */
            Route::get('/shelter_workers', [ShelterWorkersController::class, 'index'])->name('shelter_workers');
            // Route::get('/shelter_workers/{shelter_worker}', [ShelterWorkersController::class, 'show'])->name('shelter_workers.show');
            Route::get('/shelter_workers/create', [ShelterWorkersController::class, 'create'])->name('shelter_workers.control');
            Route::get('/shelter_workers/{id}/edit', [ShelterWorkersController::class, 'edit'])->name('shelter_workers.edit');
            Route::get('/shelter_workers/{id}', [ShelterWorkersController::class, 'show'])->name('shelter_workers.show');
            Route::post('/shelter_workers', [ShelterWorkersController::class, 'store'])->name('shelter_workers.store');
            Route::post('/shelter_workers/{shelter_worker}', [ShelterWorkersController::class, 'update'])->name('shelter_workers.update');
            Route::delete('/shelter_workers/{shelter_worker}', [ShelterWorkersController::class, 'destroy'])->name('shelter_workers.delete');
            Route::get('shelter_workers/{id}/download/{field}', [ShelterWorkersController::class, 'download'])->name('shelter_workers.download');
            Route::delete('shelter_workers/{id}/delete-file/{field}', [ShelterWorkersController::class, 'deleteFile'])->name('shelter_workers.delete_file');
            //** */
            Route::get('viewFullShelterWorkerContracts', [AdminShelterWorkersContractCTLR::class, 'viewFullShelterWorkerContract'])->name('viewFullShelterWorkerContract');
            Route::post('createFullShelterWorkerContract', [AdminShelterWorkersContractCTLR::class, 'createFullShelterWorkerContract'])->name('createFullShelterWorkerContract');
            //** */
            Route::get('/travel', [AdminTravelHistoryController::class, 'index'])->name('travel');
            Route::get('/travel/statistics', [AdminTravelHistoryController::class, 'statistics'])->name('travel.statistics');
            Route::get('/travel/calendar/events', [AdminTravelHistoryController::class, 'calendarEvents'])->name('travel.calendar.events');
            Route::get('/travel/assignable-staff', [AdminTravelHistoryController::class, 'assignableStaff'])->name('travel.assignable-staff');
            Route::post('/travel/{id}/assign', [AdminTravelHistoryController::class, 'assignTrip'])->name('travel.assign');
            Route::post('/travel/{id}/complete', [AdminTravelHistoryController::class, 'completeTrip'])->name('travel.complete');
            Route::get('/travel/create/{id?}', [AdminTravelHistoryController::class, 'create'])->name('travel.control');
            Route::get('/travel/{id}/edit', [AdminTravelHistoryController::class, 'edit'])->name('travel.edit');
            Route::post('/travel', [AdminTravelHistoryController::class, 'store'])->name('travel.store');
            Route::post('/travel/{shelter_worker}', [AdminTravelHistoryController::class, 'update'])->name('travel.update');

            Route::delete('/travel/{shelter_worker}', [AdminTravelHistoryController::class, 'destroy'])->name('travel.delete');
            Route::post('/store-travel-history', [AdminTravelHistoryController::class, 'storeTravelHistory'])->name('storeTravelHistory');
            Route::get('/travel/download/{id}', [AdminTravelHistoryController::class, 'downloadFile'])->name('travel.file.download');
            Route::delete('/travel/file/delete/{id}', [AdminTravelHistoryController::class, 'deleteFile'])->name('travel.file.delete');
            //** */
            Route::post('/settings/store', [PrintContractController::class, 'store'])->name('new_settings.store');
            Route::get('/contract_settings', [PrintContractController::class, 'index'])->name('contract.settings');
            //** PRINT CONTRACT **//
            Route::get('/print_contract/{contractId?}', [PrintContractController::class, 'contract'])->name('print.contract');
            //** END NEW *//

            Route::get('form-settings', [FormSettingsController::class, 'index'])->name('form-settings.index');
            Route::put('form-settings', [FormSettingsController::class, 'update'])->name('form-settings.update');
            Route::get('form-settings/preview/{template}', [FormSettingsController::class, 'preview'])->name('form-settings.preview');
            Route::get('form-settings/preview/{template}/print', [FormSettingsController::class, 'print'])->name('form-settings.preview.print');

            Route::resource('settings', SettingController::class);
            Route::get('settings/section/{section}', [SettingController::class, 'section'])->name('settings.section');
            Route::resource('develop-settings', DevelopSettingController::class);
            Route::resource('job-titles', JobTitleController::class);
            Route::post('nationalities/{id}/generate-financial-accounts', [NationalityController::class, 'generateFinancialAccounts'])->name('nationalities.generate-financial-accounts');
            Route::resource('nationalities', NationalityController::class);
            Route::resource('sliders', SliderController::class);
            Route::resource('religions', ReligionController::class);
            Route::resource('social-statuses', SocialStatusController::class);
            Route::resource('questions', QuestionController::class);
            Route::resource('contact', ContactController::class);
            Route::resource('services', ServiceController::class);
            Route::resource('country-polices', CountryPoliceController::class);
            Route::resource('police-settings', PoliceSettingController::class);
            Route::resource('about-us', AboutUsController::class);
            Route::resource('clients', UserController::class);
            Route::resource('make-accounts-for-clients', MakeAccountsForClientsController::class);
            Route::resource('skills', SkillController::class);
            Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
            Route::get('reports/export/{report}', [ReportController::class, 'export'])->name('reports.export');
            Route::get('show-maid-details/{id}', [\App\Http\Controllers\ingaz\MaidController::class, 'showMaidDetails'])->name('showMaidDetails');
            Route::get('maids-not-available', [\App\Http\Controllers\ingaz\MaidController::class, 'noneAvailableMaids'])->name('noneAvailableMaids');
            Route::get('maids/cvs-deleted', [\App\Http\Controllers\ingaz\MaidController::class, 'deletedCvs'])->name('maids.deleted_cvs');
            Route::get('maids/{id}/details', [\App\Http\Controllers\ingaz\MaidController::class, 'getMaidDetails'])->name('maids.details');
            Route::get('maids/rental', [\App\Http\Controllers\ingaz\MaidController::class, 'rentalIndex'])->name('maids.rental.index');
            Route::get('maids/rental/create', [\App\Http\Controllers\ingaz\MaidController::class, 'createRental'])->name('maids.rental.create');
            Route::get('maids/bulk-import', [MaidBulkImportController::class, 'create'])->name('maids.bulk-import.create');
            Route::get('maids/bulk-import/template', [MaidBulkImportController::class, 'template'])->name('maids.bulk-import.template');
            Route::post('maids/bulk-import', [MaidBulkImportController::class, 'store'])->name('maids.bulk-import.store');
            Route::get('maids/bulk-import/{maidBulkImport}', [MaidBulkImportController::class, 'show'])->name('maids.bulk-import.show');
            Route::post('maids/bulk-import/{maidBulkImport}/approve', [MaidBulkImportController::class, 'approve'])->name('maids.bulk-import.approve');
            // Limit maid parameter to numeric IDs to avoid catching custom pages like create_rapid_cv
            Route::resource('maids', MaidController::class)->whereNumber('maid');
            Route::get('maids/create_rapid_cv', [MaidController::class, 'create_rapid_cv'])->name('maids.create_rapid_cv');
            Route::post('maids/store_rapid_cv', [MaidController::class, 'store_rapid_cv'])->name('maids.store_rapid_cv');
            Route::post('maids/{id}/toggle-pin', [\App\Http\Controllers\ingaz\MaidController::class, 'togglePin'])->name('maids.toggle-pin');
            Route::post('maids/{id}/restore', [\App\Http\Controllers\ingaz\MaidController::class, 'restore'])->name('maids.restore');
            Route::post('maids/{id}/offer', [\App\Http\Controllers\ingaz\MaidController::class, 'updateOffer'])->name('maids.offer');
            Route::get('convert_page/{id}', [App\Http\Controllers\ingaz\MaidController::class, 'convert_page'])->name('convert_page');
            Route::post('update_cv/{id}', [App\Http\Controllers\ingaz\MaidController::class, 'update_cv'])->name('maids.update_cv');
            Route::get('maids/export/pdf', [App\Http\Controllers\ingaz\MaidController::class, 'exportPdf'])->name('maids.export.pdf');
            Route::get('maids/export/excel', [App\Http\Controllers\ingaz\MaidController::class, 'exportExcel'])->name('maids.export.excel');
            Route::post('check-passport-unique', [App\Http\Controllers\ingaz\MaidController::class, 'checkPassportUnique'])->name('check.passport.unique');
            Route::get('maids/available', [App\Http\Controllers\ingaz\MaidController::class, 'getAvailableMaids'])->name('maids.available');
            Route::get('maids/search', [App\Http\Controllers\ingaz\MaidController::class, 'searchMaids'])->name('maids.search');
            Route::get('maids/{id}/info', [App\Http\Controllers\ingaz\MaidController::class, 'getMaidInfo'])->name('maids.info');
            Route::post('maids/backout', [App\Http\Controllers\ingaz\MaidController::class, 'backout'])->name('maids.backout');
            Route::post('maids/remove_backout', [App\Http\Controllers\ingaz\MaidController::class, 'clearBackout'])->name('maids.remove_backout');
            Route::get('maids/suspendCv', [App\Http\Controllers\ingaz\MaidController::class, 'suspendCv'])->name('maids.suspendCv');

            Route::resource('maids-test', MaidTestController::class);
            Route::resource('packages', PackageController::class);
            Route::resource('complaints', ComplaintController::class);
            Route::get('complaints/cards/view', [ComplaintController::class, 'cards'])->name('complaints.cards');
            Route::post('complaints/quick-response', [ComplaintController::class, 'quickResponse'])->name('complaints.quick-response');
            Route::post('complaints/add-comment', [ComplaintController::class, 'addComment'])->name('complaints.add-comment');
            Route::post('complaints/update-status', [ComplaintController::class, 'updateStatus'])->name('complaints.update-status');
            Route::post('complaints/assign', [ComplaintController::class, 'assign'])->name('complaints.assign');
            Route::post('complaints/change-priority', [ComplaintController::class, 'changePriority'])->name('complaints.change-priority');
            Route::delete('complaints/complaint-comment/{id}', [ComplaintController::class, 'deleteComment']);
            Route::post('completeOrder/{id}', [OrderController::class, 'completeOrder'])->name('orders.completeOrder');
            Route::post('getOrderById/{id}', [OrderController::class, 'getOrderById'])->name('orders.getOrderById');
            Route::post('orders/quick-recruitment', [OrderController::class, 'quickRecruitment'])->name('orders.quickRecruitment');
            Route::resource('orders', OrderController::class);
            Route::post('orders/{id}/restore', [OrderController::class, 'restore'])->name('orders.restore');

            // Rental Orders Routes
            Route::resource('rental-orders', RentalOrderController::class);
            Route::post('rental-orders/{id}/approve', [RentalOrderController::class, 'approveOrder'])->name('rental-orders.approve');
            Route::post('rental-orders/{id}/reject', [RentalOrderController::class, 'rejectOrder'])->name('rental-orders.reject');

            // Transfer Service Contracts routes
            Route::resource('TransferServiceContract', TransferServiceContractController::class);
            Route::get('print/transfer-service-contract/{transferServiceContract}', [TransferServiceContractController::class, 'print'])->name('print.transfer-service-contract');
            Route::get('print/sponsorship-transfer-contract/{sponsorshipTransfer}', [SponsorshipTransferController::class, 'printContract'])->name('print.sponsorship-transfer-contract');
            Route::post('sponsorship-transfer/{id}/save-signature', [SponsorshipTransferController::class, 'saveSignature'])->name('sponsorship-transfer.save-signature');
            Route::post('sponsorship-transfer/{id}/save-company-signature', [SponsorshipTransferController::class, 'saveCompanySignature'])->name('sponsorship-transfer.save-company-signature');

            // Transfer Contract Terms routes
            Route::resource('transfer-contract-terms', TransferContractTermsController::class);

            // SponsorshipTransfer action routes
            Route::post('SponsorshipTransfer/{id}/send-contract', [SponsorshipTransferController::class, 'sendContract'])->name('SponsorshipTransfer.send-contract');
            Route::post('SponsorshipTransfer/{id}/sign-contract', [SponsorshipTransferController::class, 'signContract'])->name('SponsorshipTransfer.sign-contract');
            Route::post('SponsorshipTransfer/{id}/deliver-maid', [SponsorshipTransferController::class, 'deliverMaid'])->name('SponsorshipTransfer.deliver-maid');
            Route::post('SponsorshipTransfer/{id}/start-trial', [SponsorshipTransferController::class, 'startTrial'])->name('SponsorshipTransfer.start-trial');
            Route::get('orders/current', [OrderController::class, 'index'])->name('orders.current');
            Route::get('orders/ended', [OrderController::class, 'index'])->name('orders.ended');
            Route::get('orders/completed', [OrderController::class, 'index'])->name('orders.completed');
            Route::get('orders/export/pdf', [OrderController::class, 'exportPdf'])->name('orders.export.pdf');
            Route::get('orders/export/excel', [OrderController::class, 'exportExcel'])->name('orders.export.excel');

            // Messages
            Route::get('messages', [\App\Http\Controllers\ingaz\MessageController::class, 'index'])->name('messages.index');

            // Backward-compatible redirect for old contracts URL.
            Route::get('contracts', function (\Illuminate\Http\Request $request) {
                return redirect()->route('RecruitmentContract.index', $request->query());
            });

            // RecruitmentContract specific routes - MUST come before resource route
            Route::get('RecruitmentContract/available-maids', [RecruitmentContractController::class, 'getAvailableMaids'])->name('RecruitmentContract.availableMaids');
            Route::get('RecruitmentContract/search-clients', [RecruitmentContractController::class, 'searchClients'])->name('RecruitmentContract.searchClients');
            Route::get('RecruitmentContract/filter-options', [RecruitmentContractController::class, 'getFilterOptions'])->name('RecruitmentContract.filterOptions');
            Route::get('RecruitmentContract/statistics', [RecruitmentContractController::class, 'statistics'])->name('RecruitmentContract.statistics');
            Route::get('RecruitmentContract/export/pdf', [RecruitmentContractController::class, 'exportPdf'])->name('RecruitmentContract.export.pdf');
            Route::get('RecruitmentContract/export/excel', [RecruitmentContractController::class, 'exportExcel'])->name('RecruitmentContract.export.excel');
            Route::get('RecruitmentContract/external-office-dispatches', [RecruitmentContractExternalOfficeDispatchController::class, 'index'])->name('RecruitmentContract.externalOfficeDispatch.index');
            Route::get('RecruitmentContract/{contract}/external-office-dispatch', [RecruitmentContractExternalOfficeDispatchController::class, 'edit'])->name('RecruitmentContract.externalOfficeDispatch.edit');
            Route::post('RecruitmentContract/{contract}/external-office-dispatch', [RecruitmentContractExternalOfficeDispatchController::class, 'store'])->name('RecruitmentContract.externalOfficeDispatch.store');
            Route::get('RecruitmentContract/{contract}/external-office-dispatch/print', [RecruitmentContractExternalOfficeDispatchController::class, 'print'])->name('RecruitmentContract.externalOfficeDispatch.print');

            Route::resource('RecruitmentContract', RecruitmentContractController::class);
            Route::get('RecruitmentContractBack/{id}', [RecruitmentContractController::class, 'back'])->name('RecruitmentContract.back');
            Route::get('contractsDetails/{id}', [RecruitmentContractController::class, 'details'])->name('contracts.details');
            Route::post('RecruitmentContract/{contractId}/add-maid', [RecruitmentContractController::class, 'addMaidToContract'])->name('RecruitmentContract.addMaid');
            Route::get('RecruitmentContract/{contractId}/select-maid', [RecruitmentContractController::class, 'selectMaidForContract'])->name('RecruitmentContract.selectMaid');
            Route::get('RecruitmentContract/{contractId}/change-maid', [RecruitmentContractController::class, 'showChangeMaid'])->name('RecruitmentContract.showChangeMaid');
            Route::post('RecruitmentContract/{contractId}/change-maid', [RecruitmentContractController::class, 'changeMaid'])->name('RecruitmentContract.changeMaid');
            Route::post('RecruitmentContract/{contractId}/notes', [RecruitmentContractController::class, 'storeNote'])->name('RecruitmentContract.notes.store');
            Route::resource('RentContract', RentContractController::class);
            Route::get('rental-invoices/download/{id}', [RentalContractInvoiceController::class, 'download'])->name('rental.invoice.download');
            Route::post('RentContract/{id}/complete', [RentContractController::class, 'completeContract'])->name('RentContract.complete');
            Route::get('RentContract/statistics/data', [RentContractController::class, 'statistics'])->name('RentContract.statistics');
            Route::get('RentContract/export/excel', [RentContractController::class, 'exportExcel'])->name('RentContract.export.excel');
            Route::get('RentContract/existing-clients', [RentContractController::class, 'getExistingClients'])->name('RentContract.existing.clients');

            // Ø§Ù„ÙÙˆØ§ØªÙŠØ±
            Route::resource('invoices', InvoiceController::class);
            Route::get('invoices/download/{id}', [InvoiceController::class, 'downloadInvoice'])->name('invoice.download');
            Route::resource('musaned-transfers', MusanedTransferController::class)->only(['index', 'create', 'store', 'show']);

            // Ø¥ØºÙ„Ø§Ù‚ Ø¹Ù‚ÙˆØ¯ Ø§Ù„Ø¥ÙŠØ¬Ø§Ø±
            Route::get('RentContract/{id}/details', [RentContractController::class, 'getContractDetails'])->name('RentContract.details');
            Route::post('RentContract/{id}/close-and-make-available', [RentContractController::class, 'closeAndMakeAvailable'])->name('RentContract.closeAndMakeAvailable');
            Route::post('RentContract/{id}/transfer-sponsorship', [RentContractController::class, 'transferSponsorship'])->name('RentContract.transferSponsorship');

            // Routes Ø¬Ø¯ÙŠØ¯Ø© Ù„Ù€ workflow Ø¹Ù‚ÙˆØ¯ Ø§Ù„Ø¥ÙŠØ¬Ø§Ø±
            Route::post('RentContract/{id}/send-contract', [RentContractController::class, 'sendContract'])->name('RentContract.sendContract');
            Route::post('RentContract/{id}/sign-contract', [RentContractController::class, 'signContract'])->name('RentContract.signContract');
            Route::post('RentContract/{id}/deliver-maid', [RentContractController::class, 'deliverMaid'])->name('RentContract.deliverMaid');
            Route::post('RentContract/{id}/activate-contract', [RentContractController::class, 'activateContract'])->name('RentContract.activateContract');
            Route::post('RentContract/{id}/extend', [RentContractController::class, 'extend'])->name('RentContract.extend');
            Route::post('RentContract/{id}/save-signature', [RentContractController::class, 'saveSignature'])->name('RentContract.saveSignature');
            Route::post('RentContract/{id}/change-maid', [RentContractController::class, 'changeMaid'])->name('RentContract.changeMaid');

            // Ø¥Ø¯Ø§Ø±Ø© Ø¨Ù†ÙˆØ¯ Ø¹Ù‚Ø¯ Ø§Ù„ØªØ£Ø¬ÙŠØ±
            Route::get('rent-contract-terms/create', [RentalContractTermsController::class, 'create'])->name('rent-contract-terms.create');
            Route::post('rent-contract-terms/store', [RentalContractTermsController::class, 'store'])->name('rent-contract-terms.store');
            Route::get('rent-contract-terms', [RentalContractTermsController::class, 'index'])->name('rent-contract-terms.index');
            Route::get('rent-contract-terms/edit/{id}', [RentalContractTermsController::class, 'edit'])->name('rent-contract-terms.edit');
            Route::put('rent-contract-terms/update/{id}', [RentalContractTermsController::class, 'update'])->name('rent-contract-terms.update');
            Route::get('rent-contract-terms/import-default', [RentalContractTermsController::class, 'importDefault'])->name('rent-contract-terms.import-default');
            Route::delete('rent-contract-terms/{id}', [RentalContractTermsController::class, 'destroy'])->name('rent-contract-terms.destroy');

            // Ø¥Ø¹Ø¯Ø§Ø¯Ø§Øª Ø¹Ù‚Ø¯ Ø§Ù„ØªØ£Ø¬ÙŠØ±
            Route::get('rent-contract/settings', [RentalContractTermsController::class, 'showSettings'])->name('rent-contract.settings');
            Route::put('rent-contract/settings', [RentalContractTermsController::class, 'updateSettings'])->name('rent-contract.settings.update');
            Route::get('showDataOfOrder', [OrderController::class, 'showDataOfOrder'])->name('showDataOfOrder');
            Route::get('showDataOfMaid', [OrderController::class, 'showDataOfMaid'])->name('showDataOfMaid');
            Route::get('showUserFromContract', [OrderController::class, 'showUserFromContract'])->name('showUserFromContract');

            // Routes Ù„Ø·Ù„Ø¨Ø§Øª Ù†Ù‚Ù„ Ø§Ù„ÙƒÙØ§Ù„Ø© Ù…Ù† Ø§Ù„Ù…ÙˆÙ‚Ø¹ Ø§Ù„Ø®Ø§Ø±Ø¬ÙŠ - ÙŠØ¬Ø¨ Ø£Ù† ØªÙƒÙˆÙ† Ù‚Ø¨Ù„ resource route
            Route::get('SponsorshipTransfer/web-requests', [SponsorshipTransferController::class, 'webRequests'])->name('SponsorshipTransfer.webRequests');
            Route::get('SponsorshipTransfer/web-requests/{id}', [SponsorshipTransferController::class, 'showWebRequest'])->name('SponsorshipTransfer.webRequest.show');
            Route::post('SponsorshipTransfer/web-requests/{id}/cancel', [SponsorshipTransferController::class, 'cancelOrder'])->name('SponsorshipTransfer.webRequest.cancel');

            // Routes Ù„Ù„ØµÙØ­Ø§Øª Ø§Ù„Ù…Ø®ØªÙ„ÙØ© Ø­Ø³Ø¨ Ø§Ù„Ø­Ø§Ù„Ø© - ÙŠØ¬Ø¨ Ø£Ù† ØªÙƒÙˆÙ† Ù‚Ø¨Ù„ resource route
            Route::get('SponsorshipTransfer/status/trial', [SponsorshipTransferController::class, 'trial'])->name('SponsorshipTransfer.trial');
            Route::get('SponsorshipTransfer/status/transferred', [SponsorshipTransferController::class, 'transferred'])->name('SponsorshipTransfer.transferred');
            Route::get('SponsorshipTransfer/status/failed', [SponsorshipTransferController::class, 'failed'])->name('SponsorshipTransfer.failed');
            Route::get('SponsorshipTransfer/{id}/trial-details', [SponsorshipTransferController::class, 'trialDetails'])->name('SponsorshipTransfer.trial-details');

            Route::resource('SponsorshipTransfer', SponsorshipTransferController::class);
            Route::get('Contract/searchClients', [SponsorshipTransferController::class, 'searchClients'])->name('Contract.searchClients');
            Route::post('SponsorshipTransfer/{id}/approve', [SponsorshipTransferController::class, 'approve'])->name('SponsorshipTransfer.approve');
            Route::post('SponsorshipTransfer/{id}/reject', [SponsorshipTransferController::class, 'reject'])->name('SponsorshipTransfer.reject');
            Route::post('SponsorshipTransfer/{id}/complete', [SponsorshipTransferController::class, 'complete'])->name('SponsorshipTransfer.complete');
            Route::post('SponsorshipTransfer/{id}/confirm-transfer', [SponsorshipTransferController::class, 'confirmTransfer'])->name('SponsorshipTransfer.confirmTransfer');
            Route::post('SponsorshipTransfer/{id}/cancel-transfer', [SponsorshipTransferController::class, 'cancelTransfer'])->name('SponsorshipTransfer.cancelTransfer');
            Route::get('sms-dispatch', [NotificationController::class, 'index'])->name('notifications.index');
            Route::post('sms-dispatch', [NotificationController::class, 'store'])->name('notifications.store');
            Route::get('whatsapp-dispatch', [NotificationController::class, 'indexWhatsApp'])->name('whatsapp.notifications.index');
            Route::post('whatsapp-dispatch', [NotificationController::class, 'storeWhatsApp'])->name('whatsapp.notifications.store');
            Route::get('notifications-settings', [NotificationSettingsController::class, 'edit'])->name('notifications.settings');
            Route::post('notifications-settings', [NotificationSettingsController::class, 'update'])->name('notifications.settings.update');
            Route::resource('statistics', StatisticController::class);

            Route::resource('steps', StepController::class);
            Route::resource('return_requests', ReturnRequestController::class);
            Route::get('return_requests/export/pdf', [ReturnRequestController::class, 'exportPdf'])->name('return_requests.export.pdf');
            Route::get('return_requests/export/excel', [ReturnRequestController::class, 'exportExcel'])->name('return_requests.export.excel');
            Route::resource('recruitment-offices', OfficeController::class)
                ->parameters(['recruitment-offices' => 'office']);
            Route::resource('shelter-places', ShelterPlaces::class);
            // Route::resource('roles', RoleController::class);

            // Custom routes for different shelter contract stages - MUST come before resource route
            Route::get('ShelterContract/data', [ShelterContractController::class, 'getData'])->name('ShelterContract.data');
            Route::get('ShelterContract/available-for-transfer', [ShelterContractController::class, 'availableForTransfer'])->name('ShelterContract.availableForTransfer');
            Route::get('ShelterContract/deportation-stage', [ShelterContractController::class, 'deportationStage'])->name('ShelterContract.deportationStage');
            Route::get('ShelterContract/general-shelter', [ShelterContractController::class, 'generalShelter'])->name('ShelterContract.generalShelter');
            Route::get('ShelterContract/outside-shelter', [ShelterContractController::class, 'outsideShelter'])->name('ShelterContract.outside');
            Route::get('ShelterContract/search-by-passport', [ShelterContractController::class, 'searchByPassport'])->name('ShelterContract.searchByPassport');
            Route::get('ShelterContract/get-contract-details', [ShelterContractController::class, 'getContractDetails'])->name('get.contract.details');
            Route::get('ShelterContract/get-nationalities', [ShelterContractController::class, 'getNationalities'])->name('ShelterContract.getNationalities');
            Route::get('ShelterContract/get-shelter-places', [ShelterContractController::class, 'getShelterPlaces'])->name('ShelterContract.getShelterPlaces');
            Route::get('ShelterContract/export/{format}', [ShelterContractController::class, 'export'])->name('ShelterContract.export');
            Route::post('ShelterContract/{id}/back', [ShelterContractController::class, 'back'])->name('ShelterContract.back');
            Route::post('ShelterContract/{id}/quick-tags', [ShelterContractController::class, 'updateQuickTags'])->name('ShelterContract.quickTags');
            Route::get('shelter_contract_form/download/{id}', [ShelterContractController::class, 'deliveryShelterContractDownload'])->name('shelter_contract_form.download');

            // Deportation routes
            Route::post('ShelterContract/{id}/deportation/start', [ShelterContractController::class, 'startDeportation'])->name('ShelterContract.deportation.start');
            Route::get('ShelterContract/{id}/deportation/procedures', [ShelterContractController::class, 'showDeportationProcedures'])->name('ShelterContract.deportation.procedures');
            Route::post('ShelterContract/deportation/{id}/update', [ShelterContractController::class, 'updateDeportationProcedures'])->name('ShelterContract.deportation.update');
            Route::get('ShelterContract/deportation/{id}/activity-log', [ShelterContractController::class, 'getActivityLog'])->name('ShelterContract.deportation.activity-log');
            Route::get('ShelterContract/deported', [ShelterContractController::class, 'deported'])->name('ShelterContract.deported');

            // Work desire route
            Route::post('ShelterContract/{id}/change-work-desire', [ShelterContractController::class, 'changeWorkDesire'])->name('ShelterContract.changeWorkDesire');

            // Instant receipt toggle route
            Route::post('ShelterContract/{id}/toggle-instant-receipt', [ShelterContractController::class, 'toggleInstantReceipt'])->name('ShelterContract.toggleInstantReceipt');

            Route::resource('ShelterContract', ShelterContractController::class);

            // Istiqdam Integration Routes
            Route::prefix('istiqdam')->name('istiqdam.')->group(function () {
                Route::get('/', [\App\Http\Controllers\admin\IstiqdasmController::class, 'index'])->name('index');
                Route::get('/test-connection', [\App\Http\Controllers\admin\IstiqdasmController::class, 'testConnection'])->name('test.connection');

                // Real API Routes (for actual integration)
                Route::prefix('real')->name('real.')->group(function () {
                    Route::get('/', [\App\Http\Controllers\admin\IstiqdasmRealController::class, 'index'])->name('index');
                    Route::get('/test-connection', [\App\Http\Controllers\admin\IstiqdasmRealController::class, 'testConnection'])->name('test.connection');
                    Route::get('/diagnose', [\App\Http\Controllers\admin\IstiqdasmRealController::class, 'diagnoseConnection'])->name('diagnose');
                    Route::get('/packages/data', [\App\Http\Controllers\admin\IstiqdasmRealController::class, 'getPackages'])->name('packages.data');
                    Route::get('/packages/{packageId}', [\App\Http\Controllers\admin\IstiqdasmRealController::class, 'getPackage'])->name('packages.show');
                    Route::post('/contracts', [\App\Http\Controllers\admin\IstiqdasmRealController::class, 'storeContract'])->name('contracts.store');
                    Route::get('/contracts/{contractId}', [\App\Http\Controllers\admin\IstiqdasmRealController::class, 'getContractDetails'])->name('contracts.show');
                    Route::delete('/contracts/{contractId}', [\App\Http\Controllers\admin\IstiqdasmRealController::class, 'cancelContract'])->name('contracts.cancel');
                    Route::get('/contracts/{contractId}/download/{type?}', [\App\Http\Controllers\admin\IstiqdasmRealController::class, 'downloadContract'])->name('contracts.download');
                });

                // Packages (Mock/Test)
                Route::get('/packages', [\App\Http\Controllers\admin\IstiqdasmController::class, 'packages'])->name('packages');
                Route::get('/packages/data', [\App\Http\Controllers\admin\IstiqdasmController::class, 'getPackages'])->name('packages.data');
                Route::get('/packages/{packageId}', [\App\Http\Controllers\admin\IstiqdasmController::class, 'getPackage'])->name('packages.show');

                // Contracts (Mock/Test)
                Route::get('/contracts', [\App\Http\Controllers\admin\IstiqdasmController::class, 'contracts'])->name('contracts');
                Route::get('/contracts/create', [\App\Http\Controllers\admin\IstiqdasmController::class, 'createContract'])->name('contracts.create');
                Route::post('/contracts', [\App\Http\Controllers\admin\IstiqdasmController::class, 'storeContract'])->name('contracts.store');
                Route::get('/contracts/{contractId}', [\App\Http\Controllers\admin\IstiqdasmController::class, 'getContractDetails'])->name('contracts.show');
                Route::delete('/contracts/{contractId}', [\App\Http\Controllers\admin\IstiqdasmController::class, 'cancelContract'])->name('contracts.cancel');
                Route::get('/contracts/{contractId}/download/{type?}', [\App\Http\Controllers\admin\IstiqdasmController::class, 'downloadContract'])->name('contracts.download');

                // Work Permits (Mock/Test)
                Route::get('/contracts/{contractId}/work-permit', [\App\Http\Controllers\admin\IstiqdasmController::class, 'showWorkPermitForm'])->name('work-permit.form');
                Route::post('/contracts/{contractId}/work-permit', [\App\Http\Controllers\admin\IstiqdasmController::class, 'assignWorkPermit'])->name('work-permit.assign');
            });

            //** New */
            Route::get('show_single_order/{orderId}', [OrderController::class, 'showSingleOrder'])->name('showSingleOrder');
            Route::get('/order_file/{id}/download', [OrderController::class, 'download'])->name('order_files.download');
            Route::post('orders/changeMaid', [OrderController::class, 'changeMaid'])->name('orders.changeMaid');
            Route::post('orders/changeResponsible', [OrderController::class, 'changeResponsible'])->name('orders.changeResponsible');
            Route::post('orders/{id}/change-responsible', [OrderController::class, 'changeResponsible'])->name('orders.changeResponsibleById');
            Route::post('orders/{id}/refuse-return', [OrderController::class, 'refuseReturn'])->name('orders.refuseReturn');
            Route::post('orders/{id}/change-maid', [OrderController::class, 'changeMaid'])->name('orders.changeMaidById');
            Route::post('orders/{id}/change-status', [OrderController::class, 'changeStatus'])->name('orders.changeStatus');
            Route::get('/cancel_contracts', [App\Http\Controllers\ingaz\ContractsController::class, 'cancel_contracts'])->name('cancel_contracts');

            Route::resource('complaint-types', ComplaintTypeController::class);
            Route::post('complaints/response', [\App\Http\Controllers\ingaz\ComplaintController::class, 'response'])->name('complaints.response');

            // Contract routes
            Route::prefix('contracts')->group(function () {
                Route::get('search-clients', [App\Http\Controllers\ingaz\ContractController::class, 'searchClients'])->name('Contract.searchClients');
                Route::get('search-maids', [App\Http\Controllers\ingaz\ContractController::class, 'searchMaids'])->name('Contract.searchMaids');
                Route::get('get-all-maids', [App\Http\Controllers\ingaz\ContractController::class, 'getAllMaids'])->name('Contract.getAllMaids');
                Route::post('create', [App\Http\Controllers\ingaz\ContractController::class, 'createContract'])->name('Contract.create');
                Route::post('create-completed', [App\Http\Controllers\ingaz\ContractController::class, 'createCompletedContract'])->name('Contract.createCompleted');
                Route::get('create-completed-page', [App\Http\Controllers\ingaz\ContractController::class, 'createCompletedPage'])->name('Contract.createCompletedPage');
                Route::get('contract_delivery_form/download/{contract_id}', [App\Http\Controllers\ingaz\ContractController::class, 'deliveryFormDownload'])->name('contract_delivery_form.download');
                Route::get('contract_download_form/download/{contract_id}', [App\Http\Controllers\ingaz\ContractController::class, 'deliveryFormDownload'])->name('contract_download_form.download');
            });

            // Ø·Ù„Ø¨Ø§Øª Ø§Ø³ØªØ±Ø¬Ø§Ø¹ Ø§Ù„Ø¹Ù‚ÙˆØ¯ - Ø§Ù„Ø¥Ø¯Ø§Ø±Ø©
            Route::prefix('contract-return-requests')->name('contract-return-requests.')->group(function () {
                Route::get('/', [ContractReturnRequestController::class, 'index'])->name('index');
                Route::get('/statistics', [ContractReturnRequestController::class, 'statistics'])->name('statistics');
                Route::get('/{returnRequest}', [ContractReturnRequestController::class, 'show'])->name('show');
                Route::post('/{returnRequest}/process', [ContractReturnRequestController::class, 'process'])->name('process');
            });
        });
    }
);

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
    ],
    function () {

        Route::middleware(['admin', 'admin.route.permission'])->name('dash.')->prefix('dash')->group(function () {

            //** SETTINGS **//
            Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings');
            Route::get('/settings/announcements', [AdminSettingsController::class, 'announcements'])->name('settings.announcements');
            Route::post('/settings/store', [AdminSettingsController::class, 'store'])->name('settings.store');

            //** ROLES **//
            Route::name('roles.')->prefix('roles')->group(function () {
                Route::get('/{id?}', [RolesController::class, 'index'])->name('index');
                Route::post('store', [RolesController::class, 'store'])->name('store');
                Route::delete('delete/{id}', [RolesController::class, 'delete'])->name('delete');
            });

            //** PERMISSIONS **//
            Route::name('perms.')->prefix('perms')->group(function () {
                Route::get('/{id}', [PermissionsController::class, 'setPerms'])->name('setPerms');
                Route::post('storePerms', [PermissionsController::class, 'storePerms'])->name('storePerms');
            });

            //** DEVELOPERS ONLY PERMISSIONS **//
            Route::name('developers.perms.')->prefix('developers')->group(function () {
                Route::get('perms/{id?}', [PermissionsController::class, 'index'])->name('index');
                Route::post('store', [PermissionsController::class, 'store'])->name('store');
                Route::patch('toggle-status/{id}', [PermissionsController::class, 'toggleStatus'])->name('toggleStatus');
                Route::delete('delete/{id}', [PermissionsController::class, 'delete'])->name('delete');
            });

            //** ACTIVITY LOG **//
            Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity.log');
        });
    }
);

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
    ],
    function () {
        Route::group(['prefix' => 'admin', 'middleware' => ['auth:admin', 'admin.route.permission']], function () {
            Route::prefix('DeportationTravel')->name('DeportationTravel.')->group(function () {
                Route::get('/', [DeportationTravelController::class, 'index'])->name('index');
                Route::get('/create', [DeportationTravelController::class, 'create'])->name('create');
                Route::get('/statistics', [DeportationTravelController::class, 'statistics'])->name('statistics');
                Route::post('/store', [DeportationTravelController::class, 'store'])->name('store');
                Route::get('/{id}/edit', [DeportationTravelController::class, 'edit'])->name('edit');
                Route::post('/{id}', [DeportationTravelController::class, 'update'])->name('update');
                Route::get('/{id}', [DeportationTravelController::class, 'show'])->name('show');
                Route::post('/{id}/complete', [DeportationTravelController::class, 'complete'])->name('complete');
                Route::delete('/{id}', [DeportationTravelController::class, 'destroy'])->name('destroy');
            });
        });
    }
);

Route::get("ShelterContract/deportation/{id}/activity-log", [ShelterContractController::class, "getActivityLog"])->name("ShelterContract.deportation.activity-log");
