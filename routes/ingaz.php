<?php

use App\Http\Controllers\admin\ActivityLogController;
use App\Http\Controllers\admin\AdminContractController;
use App\Http\Controllers\admin\AdminFullOrderContractController;
use App\Http\Controllers\admin\AdminSettingsController;
use App\Http\Controllers\admin\FirebaseSettingsController;
use App\Http\Controllers\admin\AdminShelterWorkersContractCTLR;
use App\Http\Controllers\admin\AdminTravelHistoryController;
use App\Http\Controllers\admin\ContractReturnRequestController;
use App\Http\Controllers\admin\FormSettingsController;
use App\Http\Controllers\admin\IngazDelegationStageController;
use App\Http\Controllers\admin\DevSuperAdminController;
use App\Http\Controllers\admin\DevDatabaseBackupController;
use App\Http\Controllers\admin\office\OfficeController;
use App\Http\Controllers\admin\PrintContractController;
use App\Http\Controllers\admin\ShelterWorkersController;
use App\Http\Controllers\ingaz\AboutUsController;
use App\Http\Controllers\ingaz\AirportController;
use App\Http\Controllers\ingaz\ArrivalWorkerController;
use App\Http\Controllers\ingaz\ClientController;
use App\Http\Controllers\ingaz\ComplaintController;
use App\Http\Controllers\ingaz\ComplaintTypeController;
use App\Http\Controllers\ingaz\ContactController;
use App\Http\Controllers\ingaz\CountryPoliceController;
use App\Http\Controllers\ingaz\CvConvertController;
use App\Http\Controllers\ingaz\CvOptionsSettingsController;
use App\Http\Controllers\ingaz\CvTemplateSettingsController;
use App\Http\Controllers\ingaz\DataImportController;
use App\Http\Controllers\ingaz\DeportationTravelController;
use App\Http\Controllers\ingaz\DevelopSettingController;
use App\Http\Controllers\ingaz\FastNewController;
use App\Http\Controllers\ingaz\FeatureController;
use App\Http\Controllers\ingaz\FindUsLocationController;
use App\Http\Controllers\ingaz\HomeHeroSectionController;
use App\Http\Controllers\ingaz\GuidelineSectionController;
use App\Http\Controllers\ingaz\FinancialRequestController;
use App\Http\Controllers\ingaz\InvoiceController;
use App\Http\Controllers\ingaz\JobTitleController;
use App\Http\Controllers\ingaz\AgentMaidImportAdminController;
use App\Http\Controllers\ingaz\MaidBulkImportController;
use App\Http\Controllers\ingaz\MaidController;
use App\Http\Controllers\ingaz\MaidLanguageController;
use App\Http\Controllers\ingaz\MaidTestController;
use App\Http\Controllers\ingaz\MakeAccountsForClientsController;
use App\Http\Controllers\ingaz\MusanedTransferController;
use App\Http\Controllers\ingaz\MusanedSectionController;
use App\Http\Controllers\ingaz\NationalityController;
use App\Http\Controllers\ingaz\NotificationController;
use App\Http\Controllers\ingaz\NotificationSettingsController;
use App\Http\Controllers\ingaz\StorageSettingsController;
use App\Http\Controllers\ingaz\WhatsAppInboxController;
use App\Http\Controllers\ingaz\BlogController;
use App\Http\Controllers\ingaz\OfferController;
use App\Http\Controllers\ingaz\OrderController;
use App\Http\Controllers\ingaz\PackageController;
use App\Http\Controllers\ingaz\PoliceSettingController;
use App\Http\Controllers\ingaz\ProfessionalApplicationController;
use App\Http\Controllers\ingaz\QuestionController;
use App\Http\Controllers\ingaz\RecruitmentContractController;
use App\Http\Controllers\ingaz\RecruitmentContractExternalOfficeDispatchController;
use App\Http\Controllers\ingaz\RecruitmentContractStageLabelController;
use App\Http\Controllers\ingaz\RecruitmentRequestController;
use App\Http\Controllers\ingaz\ReligionController;
use App\Http\Controllers\ingaz\RenewContractController;
use App\Http\Controllers\ingaz\RentalContractInvoiceController;
use App\Http\Controllers\ingaz\RentalContractTermsController;
use App\Http\Controllers\ingaz\RentalOrderController;
use App\Http\Controllers\ingaz\RentContractController;
use App\Http\Controllers\ingaz\MoyasarWebhookController;
use App\Http\Controllers\ingaz\MoyasarSettingsController;
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
use App\Http\Controllers\ingaz\TranslationTermController;
use App\Http\Controllers\ingaz\UserController;
use App\Http\Controllers\ingaz\WhatDoController;
use App\Http\Controllers\ingaz\WhatDoSettingController;
use App\Http\Controllers\ingaz\WhyUsController;
use App\Http\Controllers\ingaz\WebsiteVisitorChatController;
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
        // ميسر: callback يستقبل العميل بعد الدفع (بدون auth)
        Route::get('moyasar/callback', [MoyasarWebhookController::class, 'callback'])->name('moyasar.callback');
        // ميسر: webhook يستقبل إشعارات ميسر (بدون CSRF — يُستثنى في VerifyCsrfToken أيضاً)
        Route::post('moyasar/webhook', [MoyasarWebhookController::class, 'webhook'])->name('moyasar.webhook')->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

        Route::group(["prefix" => "admin"], function () {
            Route::get('print/rental-contract/{id}', [AdminContractController::class, 'printRentalContract'])->name('print.rental.contract');
            Route::post('RentContract/{id}/save-signature', [AdminContractController::class, 'saveSignature'])->name('RentContract.saveSignature');
            Route::get('form-settings/preview/rental-contract', [FormSettingsController::class, 'publicRentalContractPreview'])
                ->middleware('throttle:60,1')
                ->name('public.form-settings.preview.rental-contract');
        });

        Route::group(["prefix" => "admin", "middleware" => ["auth:admin", "admin", "admin.route.permission"]], function () {

            Route::resource('partenrs', ClientController::class);
            Route::resource('why-us', WhyUsController::class);
            Route::get('why-choose-us', [\App\Http\Controllers\ingaz\WhyChooseUsController::class, 'edit'])
                ->name('why-choose-us.edit');
            Route::put('why-choose-us', [\App\Http\Controllers\ingaz\WhyChooseUsController::class, 'update'])
                ->name('why-choose-us.update');
            Route::get('musaned-section', [MusanedSectionController::class, 'edit'])
                ->name('musaned-section.edit');
            Route::put('musaned-section', [MusanedSectionController::class, 'update'])
                ->name('musaned-section.update');
            Route::get('home-hero', [HomeHeroSectionController::class, 'edit'])
                ->name('home-hero.edit');
            Route::put('home-hero', [HomeHeroSectionController::class, 'update'])
                ->name('home-hero.update');
            Route::get('guidelines', [GuidelineSectionController::class, 'edit'])
                ->name('guidelines.edit');
            Route::put('guidelines', [GuidelineSectionController::class, 'update'])
                ->name('guidelines.update');
            // يجب أن يسبق تعريف الـ resource حتى لا يبتلعه المسار find-us/{location}
            Route::put('find-us/mode', [FindUsLocationController::class, 'updateMode'])
                ->name('find-us.mode');
            Route::resource('find-us', FindUsLocationController::class)
                ->except('show')
                ->parameters(['find-us' => 'location']);
            Route::resource('arrival-workers', ArrivalWorkerController::class);
            Route::resource('testimonials', TestimonialController::class);
            Route::resource('select-workers', SelectWorkerController::class);
            Route::resource('select-workers-settings', SelectWorkerSettingsController::class);
            Route::resource('requiremens', RequirementsController::class);
            Route::resource('cv-convert', CvConvertController::class);

            Route::resource('partenrs', ClientController::class);
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
            Route::get('get-contract-details', [\App\Http\Controllers\ingaz\ShelterContractController::class, 'getContractDetails'])->name('get.contract.details.legacy');
            Route::get('search-contracts-by-passport', [\App\Http\Controllers\ingaz\ShelterContractController::class, 'searchByPassport'])->name('search.contracts.by.passport');
            //** */
            Route::get('viewFullOrderContract', [AdminFullOrderContractController::class, 'viewFullOrderContract'])->name('viewFullOrderContract');
            Route::post('createFullOrderContract', [AdminFullOrderContractController::class, 'createFullOrderContract'])->name('createFullOrderContract');
            /** */
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
            Route::get('/travel/export', [AdminTravelHistoryController::class, 'export'])->name('travel.export');
            Route::get('/travel/print', [AdminTravelHistoryController::class, 'print'])->name('travel.print');
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

            Route::get('settings/cv-template', [CvTemplateSettingsController::class, 'edit'])
                ->name('settings.cv-template.edit');
            Route::put('settings/cv-template', [CvTemplateSettingsController::class, 'update'])
                ->name('settings.cv-template.update');
            Route::get('settings/cv-options', [CvOptionsSettingsController::class, 'edit'])
                ->name('settings.cv-options.edit');
            Route::put('settings/cv-options', [CvOptionsSettingsController::class, 'update'])
                ->name('settings.cv-options.update');
            Route::resource('settings', SettingController::class);
            Route::get('settings/section/{section}', [SettingController::class, 'section'])->name('settings.section');
            Route::get('moyasar-settings', [MoyasarSettingsController::class, 'edit'])->name('moyasar-settings.edit');
            Route::put('moyasar-settings', [MoyasarSettingsController::class, 'update'])->name('moyasar-settings.update');
            Route::get('recruitment-contract-stages', [RecruitmentContractStageLabelController::class, 'edit'])->name('recruitment-contract-stages.edit');
            Route::put('recruitment-contract-stages', [RecruitmentContractStageLabelController::class, 'update'])->name('recruitment-contract-stages.update');
            Route::get('recruitment-contract-stage-labels', fn() => redirect()->route('recruitment-contract-stages.edit'));
            Route::put('recruitment-contract-stage-labels', [RecruitmentContractStageLabelController::class, 'update']);
            Route::get('ingaz-delegation-stages', [IngazDelegationStageController::class, 'edit'])->name('ingaz-delegation-stages.edit');
            Route::put('ingaz-delegation-stages', [IngazDelegationStageController::class, 'update'])->name('ingaz-delegation-stages.update');
            Route::get('translation-terms', [TranslationTermController::class, 'index'])->name('translation-terms.index');
            Route::post('translation-terms', [TranslationTermController::class, 'store'])->name('translation-terms.store');
            Route::post('translation-terms/sync', [TranslationTermController::class, 'sync'])->name('translation-terms.sync');
            Route::delete('translation-terms', [TranslationTermController::class, 'destroy'])->name('translation-terms.destroy');
            Route::resource('develop-settings', DevelopSettingController::class);
            Route::resource('job-titles', JobTitleController::class);
            Route::post('nationalities/{id}/generate-financial-accounts', [NationalityController::class, 'generateFinancialAccounts'])->name('nationalities.generate-financial-accounts');
            Route::resource('nationalities', NationalityController::class);
            Route::post('offers/{id}/toggle-status', [OfferController::class, 'toggleStatus'])->name('offers.toggle-status');
            Route::resource('offers', OfferController::class);
            Route::post('blogs/{id}/toggle-status', [BlogController::class, 'toggleStatus'])->name('blogs.toggle-status');
            Route::resource('blogs', BlogController::class);
            Route::resource('sliders', SliderController::class);
            Route::resource('religions', ReligionController::class);
            Route::patch('languages/{language}/toggle-visibility', [MaidLanguageController::class, 'toggleVisibility'])
                ->name('languages.toggle-visibility');
            Route::resource('languages', MaidLanguageController::class)->except('show');
            Route::resource('education-qualifications', \App\Http\Controllers\ingaz\EducationQualificationController::class)
                ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
            Route::patch('education-qualifications/{educationQualification}/toggle-visibility', [\App\Http\Controllers\ingaz\EducationQualificationController::class, 'toggleVisibility'])
                ->name('education-qualifications.toggle-visibility');
            Route::resource('social-statuses', SocialStatusController::class);
            Route::resource('questions', QuestionController::class);
            Route::resource('contact', ContactController::class);
            Route::resource('services', ServiceController::class);
            Route::resource('country-polices', CountryPoliceController::class);
            Route::resource('police-settings', PoliceSettingController::class);
            Route::resource('about-us', AboutUsController::class);
            Route::get('website-visitors', [WebsiteVisitorChatController::class, 'index'])->name('website-visitors.index');
            Route::get('website-visitors/data', [WebsiteVisitorChatController::class, 'data'])->name('website-visitors.data');
            Route::get('website-visitors/history', [WebsiteVisitorChatController::class, 'history'])->name('website-visitors.history');
            Route::get('website-visitors/conversations', [WebsiteVisitorChatController::class, 'conversations'])->name('website-visitors.conversations');
            Route::post('website-visitors/sites', [WebsiteVisitorChatController::class, 'storeSite'])->name('website-visitors.sites.store');
            Route::put('website-visitors/sites/{site}', [WebsiteVisitorChatController::class, 'updateSite'])->name('website-visitors.sites.update');
            Route::delete('website-visitors/sites/{site}', [WebsiteVisitorChatController::class, 'destroySite'])->name('website-visitors.sites.destroy');
            Route::get('website-visitors/{visitor}', [WebsiteVisitorChatController::class, 'show'])->name('website-visitors.show');
            Route::get('website-visitors/{visitor}/messages', [WebsiteVisitorChatController::class, 'messages'])->name('website-visitors.messages');
            Route::post('website-visitors/{visitor}/messages', [WebsiteVisitorChatController::class, 'send'])->name('website-visitors.send');
            Route::post('clients/bulk-update-status', [UserController::class, 'bulkUpdateStatus'])->name('clients.bulk-update-status');
            Route::get('clients/reports/duplicate-phones', [UserController::class, 'exportDuplicatePhones'])
                ->name('clients.reports.duplicate-phones');
            Route::resource('clients', UserController::class);
            Route::post('clients/{id}/attachments', [UserController::class, 'storeAttachments'])->name('clients.attachments.store');
            Route::get('clients/{id}/attachments/download', [UserController::class, 'downloadAttachments'])->name('clients.attachments.download');
            Route::get('clients/{id}/attachments/{attachment}/view', [UserController::class, 'viewAttachment'])->name('clients.attachments.view');
            Route::get('clients/{id}/attachments/{attachment}/download', [UserController::class, 'downloadAttachment'])->name('clients.attachments.download.single');
            Route::delete('clients/{id}/attachments/{attachment}', [UserController::class, 'deleteAttachment'])->name('clients.attachments.delete');
            Route::resource('make-accounts-for-clients', MakeAccountsForClientsController::class)->only(['store']);
            Route::resource('skills', SkillController::class);
            Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
            Route::get('reports/import', [DataImportController::class, 'index'])->name('reports.import.index');
            Route::get('reports/import/template/{type}', [DataImportController::class, 'template'])->name('reports.import.template');
            Route::post('reports/import', [DataImportController::class, 'store'])->name('reports.import.store');
            Route::post('reports/import/generate-bonds', [DataImportController::class, 'generateBonds'])->name('reports.import.generate-bonds');
            Route::get('reports/export/{report}', [ReportController::class, 'export'])->name('reports.export');
            Route::get('show-maid-details/{id}', [\App\Http\Controllers\ingaz\MaidController::class, 'showMaidDetails'])->name('showMaidDetails');
            Route::get('maids/{id}/cv-preview', [\App\Http\Controllers\ingaz\MaidController::class, 'previewCv'])->name('maids.cv.preview');
            Route::get('maids/{id}/cv-preview-data', [\App\Http\Controllers\ingaz\MaidController::class, 'previewCvData'])->name('maids.cv.preview.data');
            Route::get('maids/{id}/cv-download', [\App\Http\Controllers\ingaz\MaidController::class, 'downloadCv'])->name('maids.cv.download');
            Route::get('maids-not-available', [\App\Http\Controllers\ingaz\MaidController::class, 'noneAvailableMaids'])->name('noneAvailableMaids');
            Route::get('maids/pending-submissions', [\App\Http\Controllers\ingaz\MaidController::class, 'pendingSubmissions'])->name('maids.pending-submissions');
            Route::post('maids/{id}/approve-submission', [\App\Http\Controllers\ingaz\MaidController::class, 'approveSubmission'])->name('maids.approve-submission');
            Route::post('maids/{id}/reject-submission', [\App\Http\Controllers\ingaz\MaidController::class, 'rejectSubmission'])->name('maids.reject-submission');
            Route::post('maids/submissions/bulk-approve', [\App\Http\Controllers\ingaz\MaidController::class, 'bulkApproveSubmissions'])
                ->name('maids.submissions.bulk-approve');
            Route::post('maids/status-change-requests/bulk-approve', [\App\Http\Controllers\ingaz\MaidController::class, 'bulkApproveStatusChangeRequests'])
                ->name('maids.status-change-requests.bulk-approve');
            Route::post('maids/status-change-requests/{statusChangeRequest}/approve', [\App\Http\Controllers\ingaz\MaidController::class, 'approveStatusChangeRequest'])
                ->name('maids.status-change-requests.approve');
            Route::post('maids/status-change-requests/{statusChangeRequest}/reject', [\App\Http\Controllers\ingaz\MaidController::class, 'rejectStatusChangeRequest'])
                ->name('maids.status-change-requests.reject');
            Route::get('maids/cvs-deleted', [\App\Http\Controllers\ingaz\MaidController::class, 'deletedCvs'])->name('maids.deleted_cvs');
            Route::get('maids/{id}/details', [\App\Http\Controllers\ingaz\MaidController::class, 'getMaidDetails'])->name('maids.details');
            Route::get('maids/rental', [\App\Http\Controllers\ingaz\MaidController::class, 'rentalIndex'])->name('maids.rental.index');
            Route::get('maids/rental/create', [\App\Http\Controllers\ingaz\MaidController::class, 'createRental'])->name('maids.rental.create');
            Route::post('maids/{maid}/branch', [\App\Http\Controllers\ingaz\MaidController::class, 'changeBranch'])->name('maids.branch.update');
            Route::post('maids/{id}/attachments', [\App\Http\Controllers\ingaz\MaidController::class, 'storeAttachments'])->name('maids.attachments.store');
            Route::get('maids/{id}/attachments/download', [\App\Http\Controllers\ingaz\MaidController::class, 'downloadAttachments'])->name('maids.attachments.download');
            Route::get('maids/{id}/attachments/{attachment}/view', [\App\Http\Controllers\ingaz\MaidController::class, 'viewAttachment'])->name('maids.attachments.view');
            Route::get('maids/{id}/attachments/{attachment}/download', [\App\Http\Controllers\ingaz\MaidController::class, 'downloadAttachment'])->name('maids.attachments.download.single');
            Route::delete('maids/{id}/attachments/{attachment}', [\App\Http\Controllers\ingaz\MaidController::class, 'deleteAttachment'])->name('maids.attachments.delete');
            Route::get('maids/bulk-import', [MaidBulkImportController::class, 'create'])->name('maids.bulk-import.create');
            Route::get('maids/bulk-import/template', [MaidBulkImportController::class, 'template'])->name('maids.bulk-import.template');
            Route::post('maids/bulk-import', [MaidBulkImportController::class, 'store'])->name('maids.bulk-import.store');
            Route::get('maids/bulk-import/{maidBulkImport}', [MaidBulkImportController::class, 'show'])->name('maids.bulk-import.show');
            Route::post('maids/bulk-import/{maidBulkImport}/approve', [MaidBulkImportController::class, 'approve'])->name('maids.bulk-import.approve');

            // Agent imports management
            Route::prefix('agent-imports')->name('admin.agent-imports.')->group(function () {
                Route::get('/', [AgentMaidImportAdminController::class, 'index'])->name('index');
                Route::get('/{agentMaidBulkImport}', [AgentMaidImportAdminController::class, 'show'])->name('show');
                Route::post('/{agentMaidBulkImport}/approve', [AgentMaidImportAdminController::class, 'approve'])->name('approve');
                Route::post('/{agentMaidBulkImport}/reject', [AgentMaidImportAdminController::class, 'reject'])->name('reject');
                Route::get('/{agentMaidBulkImport}/download', [AgentMaidImportAdminController::class, 'download'])->name('download');
            });
            // Limit maid parameter to numeric IDs to avoid catching custom pages like create_rapid_cv
            Route::post('maids/extract-cv-data', [MaidController::class, 'extractCvData'])->name('maids.extract-cv-data');
            Route::post('maids/bulk-visibility', [MaidController::class, 'bulkVisibility'])->name('maids.bulk-visibility');
            Route::post('maids/cv/generate-bulk', [MaidController::class, 'generateCvBulk'])->name('maids.generate-cv.bulk');
            Route::post('maids/cv/export', [MaidController::class, 'exportGeneratedCvs'])->name('maids.generated-cvs.export');
            Route::get('maids/{maid}/cv/download', [MaidController::class, 'downloadGeneratedCv'])
                ->whereNumber('maid')
                ->name('maids.generated-cv.download');
            Route::post('maids/{maid}/generate-cv', [MaidController::class, 'generateCv'])
                ->whereNumber('maid')
                ->name('maids.generate-cv');
            Route::post('maids/{maid}/media', [MaidController::class, 'updateMedia'])->whereNumber('maid')->name('maids.media.update');
            Route::get('maids/{maid}/video', [MaidController::class, 'openVideo'])
                ->whereNumber('maid')
                ->name('maids.video.open');
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
            Route::get('packages/{id}/toggle-visibility', [PackageController::class, 'toggleVisibility'])->name('packages.toggle-visibility');
            Route::get('packages/{id}/offer', [PackageController::class, 'offer'])->name('packages.offer');
            Route::put('packages/{id}/offer', [PackageController::class, 'updateOffer'])->name('packages.offer.update');
            Route::resource('packages', PackageController::class);
            Route::resource('complaints', ComplaintController::class);
            Route::get('complaints/cards/view', [ComplaintController::class, 'cards'])->name('complaints.cards');
            Route::post('complaints/quick-response', [ComplaintController::class, 'quickResponse'])->name('complaints.quick-response');
            Route::post('complaints/add-comment', [ComplaintController::class, 'addComment'])->name('complaints.add-comment');
            Route::post('complaints/update-status', [ComplaintController::class, 'updateStatus'])->name('complaints.update-status');
            Route::post('complaints/assign', [ComplaintController::class, 'assign'])->name('complaints.assign');
            Route::post('complaints/change-priority', [ComplaintController::class, 'changePriority'])->name('complaints.change-priority');
            Route::post('complaints/{complaint}/attachments', [ComplaintController::class, 'addAttachments'])
                ->name('complaints.attachments.store');
            Route::delete('complaints/{complaint}/attachments/{attachment}', [ComplaintController::class, 'deleteAttachment'])
                ->whereNumber('attachment')
                ->name('complaints.attachments.destroy');
            Route::delete('complaints/complaint-comment/{id}', [ComplaintController::class, 'deleteComment'])
                ->name('complaints.comments.destroy');
            Route::post('completeOrder/{id}', [OrderController::class, 'completeOrder'])->name('orders.completeOrder');
            Route::post('getOrderById/{id}', [OrderController::class, 'getOrderById'])->name('orders.getOrderById');
            Route::post('orders/quick-recruitment', [OrderController::class, 'quickRecruitment'])->name('orders.quickRecruitment');
            Route::post('orders/{orderId}/notes', [OrderController::class, 'storeNote'])
                ->whereNumber('orderId')
                ->name('orders.notes.store');
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
            Route::get('financial-requests/search-clients', [FinancialRequestController::class, 'searchClients'])->name('financial-requests.search-clients');
            Route::get('financial-requests/search-maids', [FinancialRequestController::class, 'searchMaids'])->name('financial-requests.search-maids');
            Route::get('financial-requests/search-accounts', [FinancialRequestController::class, 'searchAccounts'])->name('financial-requests.search-accounts');
            Route::get('financial-requests/contract-details', [FinancialRequestController::class, 'contractDetails'])->name('financial-requests.contract-details');
            Route::get('financial-requests/export', [FinancialRequestController::class, 'export'])->name('financial-requests.export');
            Route::get('financial-requests/print', [FinancialRequestController::class, 'print'])->name('financial-requests.print');
            Route::post('financial-requests/{financialRequest}/restore', [FinancialRequestController::class, 'restore'])->name('financial-requests.restore');
            Route::post('financial-requests/{financialRequest}/status', [FinancialRequestController::class, 'updateStatus'])->name('financial-requests.status');
            Route::resource('financial-requests', FinancialRequestController::class)
                ->parameters(['financial-requests' => 'financialRequest']);

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
            Route::post('RecruitmentContract/external-office-dispatches/bulk', [RecruitmentContractExternalOfficeDispatchController::class, 'bulkStore'])->name('RecruitmentContract.externalOfficeDispatch.bulkStore');
            Route::get('RecruitmentContract/{contract}/external-office-dispatch', [RecruitmentContractExternalOfficeDispatchController::class, 'edit'])->name('RecruitmentContract.externalOfficeDispatch.edit');
            Route::post('RecruitmentContract/{contract}/external-office-dispatch', [RecruitmentContractExternalOfficeDispatchController::class, 'store'])->name('RecruitmentContract.externalOfficeDispatch.store');
            Route::get('RecruitmentContract/{contract}/external-office-dispatch/print', [RecruitmentContractExternalOfficeDispatchController::class, 'print'])->name('RecruitmentContract.externalOfficeDispatch.print');
            Route::post('RecruitmentContract/{contractId}/attachments', [RecruitmentContractController::class, 'storeAttachments'])->name('RecruitmentContract.attachments.store');
            Route::get('RecruitmentContract/{contractId}/attachments/download', [RecruitmentContractController::class, 'downloadAttachments'])->name('RecruitmentContract.attachments.download');
            Route::get('RecruitmentContract/{contractId}/attachments/{attachment}/download', [RecruitmentContractController::class, 'downloadAttachment'])->name('RecruitmentContract.attachments.download.single');
            Route::delete('RecruitmentContract/{contractId}/attachments/{attachment}', [RecruitmentContractController::class, 'deleteAttachment'])->name('RecruitmentContract.attachments.delete');
            Route::post('RecruitmentContract/refund/preview', [RecruitmentContractController::class, 'refundPreview'])->name('RecruitmentContract.refundPreview');
            Route::post('RecruitmentContract/refund', [RecruitmentContractController::class, 'refund'])->name('RecruitmentContract.refund');
            Route::post('RecruitmentContract/{contract}/restore-returned', [RecruitmentContractController::class, 'restoreReturned'])->name('RecruitmentContract.restoreReturned');

            Route::resource('RecruitmentContract', RecruitmentContractController::class);
            Route::get('RecruitmentContractBack/{id}', [RecruitmentContractController::class, 'back'])->name('RecruitmentContract.back');
            Route::get('contractsDetails/{id}', [RecruitmentContractController::class, 'details'])->name('contracts.details');
            Route::post('RecruitmentContract/{contract}/stage-steps/{step}', [RecruitmentContractController::class, 'updateStageStep'])->name('RecruitmentContract.stageSteps.update');
            Route::post('RecruitmentContract/{contractId}/add-maid', [RecruitmentContractController::class, 'addMaidToContract'])->name('RecruitmentContract.addMaid');
            Route::get('RecruitmentContract/{contractId}/select-maid', [RecruitmentContractController::class, 'selectMaidForContract'])->name('RecruitmentContract.selectMaid');
            Route::get('RecruitmentContract/{contractId}/change-maid', [RecruitmentContractController::class, 'showChangeMaid'])->name('RecruitmentContract.showChangeMaid');
            Route::post('RecruitmentContract/{contractId}/change-maid', [RecruitmentContractController::class, 'changeMaid'])->name('RecruitmentContract.changeMaid');
            Route::post('RecruitmentContract/{contractId}/notes', [RecruitmentContractController::class, 'storeNote'])->name('RecruitmentContract.notes.store');
            Route::get('RentContract/existing-clients', [RentContractController::class, 'getExistingClients'])->name('RentContract.existing.clients');
            Route::resource('RentContract', RentContractController::class);
            Route::get('rental-invoices/download/{id}', [RentalContractInvoiceController::class, 'download'])->name('rental.invoice.download');
            Route::get('rental-extension-invoices/download/{id}', [RentalContractInvoiceController::class, 'extension'])->name('rental.extension-invoice.download');
            Route::get('rental-credit-notes/download/{id}', [RentalContractInvoiceController::class, 'creditNote'])->name('rental.credit-note.download');
            Route::get('rental-invoices/{id}/credit-notes/create', [RentalContractInvoiceController::class, 'createCreditNote'])->name('rental-invoices.credit-notes.create');
            Route::post('rental-invoices/{id}/credit-notes', [RentalContractInvoiceController::class, 'storeCreditNote'])->name('rental-invoices.credit-notes.store');
            Route::post('RentContract/{id}/complete', [RentContractController::class, 'completeContract'])->name('RentContract.complete');
            Route::get('RentContract/statistics/data', [RentContractController::class, 'statistics'])->name('RentContract.statistics');
            Route::get('RentContract/export/excel', [RentContractController::class, 'exportExcel'])->name('RentContract.export.excel');

            // Ø§Ù„ÙÙˆØ§ØªÙŠØ±
            Route::post('invoices/{id}/queue-zatca-sale', [InvoiceController::class, 'queueZatcaSale'])->name('invoices.queue-zatca-sale');
            Route::post('rental-invoices/{id}/queue-zatca-sale', [InvoiceController::class, 'queueRentalZatcaSale'])->name('rental-invoices.queue-zatca-sale');
            Route::post('rental-adjustments/{id}/queue-zatca-sale', [InvoiceController::class, 'queueRentalAdjustmentZatcaSale'])->name('rental-adjustments.queue-zatca-sale');
            Route::get('SponsorshipTransfer/{id}/invoice/create', [InvoiceController::class, 'createSponsorshipTransfer'])->name('SponsorshipTransfer.invoice.create');
            Route::get('invoices/search-clients', [InvoiceController::class, 'searchClients'])->name('invoices.searchClients');
            Route::get('invoices/export/excel', [InvoiceController::class, 'exportExcel'])->name('invoices.export.excel');
            Route::get('invoices/export/pdf', [InvoiceController::class, 'exportPdf'])->name('invoices.export.pdf');
            Route::get('invoices/{id}/legacy-source-file', [InvoiceController::class, 'legacySourceFile'])->name('invoices.legacy-source-file');
            Route::get('invoices/{id}/credit-notes/create', [InvoiceController::class, 'createRecruitmentCreditNote'])->name('invoices.credit-notes.create');
            Route::post('invoices/{id}/credit-notes', [InvoiceController::class, 'storeRecruitmentCreditNote'])->name('invoices.credit-notes.store');
            Route::get('recruitment-credit-notes/{id}/download', [InvoiceController::class, 'downloadRecruitmentCreditNote'])->name('recruitment-credit-notes.download');
            Route::resource('invoices', InvoiceController::class);
            Route::get('invoices/download/{id}', [InvoiceController::class, 'downloadInvoice'])->name('invoice.download');
            Route::get('musaned-transfers/search', [MusanedTransferController::class, 'search'])->name('musaned-transfers.search');
            Route::resource('musaned-transfers', MusanedTransferController::class)->only(['index', 'create', 'store', 'show', 'destroy']);

            // Ø¥ØºÙ„Ø§Ù‚ Ø¹Ù‚ÙˆØ¯ Ø§Ù„Ø¥ÙŠØ¬Ø§Ø±
            Route::get('RentContract/{id}/details', [RentContractController::class, 'getContractDetails'])->name('RentContract.details');
            Route::get('RentContract/{id}/attachments/download', [RentContractController::class, 'downloadAttachments'])->name('RentContract.attachments.download');
            Route::get('RentContract/{id}/attachments/{attachment}/view', [RentContractController::class, 'viewAttachment'])->name('RentContract.attachments.view');
            Route::get('RentContract/{id}/attachments/{attachment}/download', [RentContractController::class, 'downloadAttachment'])->name('RentContract.attachments.download.single');
            Route::delete('RentContract/{id}/payment-receipts/{receipt}', [RentContractController::class, 'deletePaymentReceipt'])->name('RentContract.paymentReceipts.delete');
            Route::post('RentContract/{id}/payment', [RentContractController::class, 'updatePayment'])->name('RentContract.payment.update');
            Route::post('RentContract/{id}/close-and-make-available', [RentContractController::class, 'closeAndMakeAvailable'])->name('RentContract.closeAndMakeAvailable');
            Route::post('RentContract/{id}/transfer-sponsorship', [RentContractController::class, 'transferSponsorship'])->name('RentContract.transferSponsorship');

            // Routes Ø¬Ø¯ÙŠØ¯Ø© Ù„Ù€ workflow Ø¹Ù‚ÙˆØ¯ Ø§Ù„Ø¥ÙŠØ¬Ø§Ø±
            Route::post('RentContract/{id}/send-contract', [RentContractController::class, 'sendContract'])->name('RentContract.sendContract');
            Route::post('RentContract/{id}/send-payment-link', [RentContractController::class, 'sendPaymentLink'])->name('RentContract.sendPaymentLink');
            Route::post('RentContract/{id}/send-expiry-reminder', [RentContractController::class, 'sendExpiryReminder'])->name('RentContract.sendExpiryReminder');
            Route::post('RentContract/{id}/sign-contract', [RentContractController::class, 'signContract'])->name('RentContract.signContract');
            Route::post('RentContract/{id}/deliver-maid', [RentContractController::class, 'deliverMaid'])->name('RentContract.deliverMaid');
            Route::post('RentContract/{id}/activate-contract', [RentContractController::class, 'activateContract'])->name('RentContract.activateContract');
            Route::post('RentContract/{id}/extend', [RentContractController::class, 'extend'])->name('RentContract.extend');
            Route::post('RentContract/{id}/freeze', [RentContractController::class, 'freeze'])->name('RentContract.freeze');
            Route::post('RentContract/{id}/update-freeze-date', [RentContractController::class, 'updateFreezeDate'])->name('RentContract.updateFreezeDate');
            Route::post('RentContract/{id}/resume', [RentContractController::class, 'resume'])->name('RentContract.resume');
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
            Route::get('SponsorshipTransfer/{id}/download/{type}/{index?}', [SponsorshipTransferController::class, 'downloadAttachment'])
                ->where([
                    'type' => 'payment-receipt|attachment|iban',
                    'index' => '[0-9]+',
                ])
                ->name('SponsorshipTransfer.attachment.download');

            Route::resource('SponsorshipTransfer', SponsorshipTransferController::class);
            Route::get('Contract/searchClients', [SponsorshipTransferController::class, 'searchClients'])->name('SponsorshipTransfer.searchClients');
            Route::post('SponsorshipTransfer/{id}/approve', [SponsorshipTransferController::class, 'approve'])->name('SponsorshipTransfer.approve');
            Route::post('SponsorshipTransfer/{id}/reject', [SponsorshipTransferController::class, 'reject'])->name('SponsorshipTransfer.reject');
            Route::post('SponsorshipTransfer/{id}/complete', [SponsorshipTransferController::class, 'complete'])->name('SponsorshipTransfer.complete');
            Route::post('SponsorshipTransfer/{id}/confirm-transfer', [SponsorshipTransferController::class, 'confirmTransfer'])->name('SponsorshipTransfer.confirmTransfer');
            Route::post('SponsorshipTransfer/{id}/return-to-shelter', [SponsorshipTransferController::class, 'returnTransferredMaidToShelter'])->name('SponsorshipTransfer.returnToShelter');
            Route::post('SponsorshipTransfer/{id}/cancel-transfer', [SponsorshipTransferController::class, 'cancelTransfer'])->name('SponsorshipTransfer.cancelTransfer');
            Route::get('sms-dispatch', [NotificationController::class, 'index'])->name('notifications.index');
            Route::post('sms-dispatch', [NotificationController::class, 'store'])->name('notifications.store');
            Route::get('whatsapp-dispatch', [NotificationController::class, 'indexWhatsApp'])->name('whatsapp.notifications.index');
            Route::post('whatsapp-dispatch', [NotificationController::class, 'storeWhatsApp'])->name('whatsapp.notifications.store');
            Route::get('whatsapp-inbox', [WhatsAppInboxController::class, 'index'])->name('whatsapp.inbox.index');
            Route::post('whatsapp-inbox/templates', [WhatsAppInboxController::class, 'createTemplate'])->name('whatsapp.templates.store');
            Route::post('whatsapp-inbox/templates/send-to-client', [WhatsAppInboxController::class, 'sendTemplateToClient'])->name('whatsapp.templates.send-to-client');
            Route::get('whatsapp-inbox/{conversation}/messages', [WhatsAppInboxController::class, 'messages'])->name('whatsapp.inbox.messages');
            Route::post('whatsapp-inbox/{conversation}/messages', [WhatsAppInboxController::class, 'send'])->name('whatsapp.inbox.send');
            Route::post('whatsapp-inbox/{conversation}/template', [WhatsAppInboxController::class, 'sendTemplate'])->name('whatsapp.inbox.template.send');
            Route::get('notifications-settings', [NotificationSettingsController::class, 'edit'])->name('notifications.settings');
            Route::get('notifications-settings/alerts', [NotificationSettingsController::class, 'editAlerts'])->name('notifications.settings.alerts');
            Route::post('notifications-settings', [NotificationSettingsController::class, 'update'])->name('notifications.settings.update');
            Route::post('notifications-settings/whatsapp/test', [NotificationSettingsController::class, 'testWhatsApp'])->name('notifications.settings.whatsapp.test');
            Route::post('notifications-settings/whatsapp/embedded-signup', [NotificationSettingsController::class, 'completeWhatsAppEmbeddedSignup'])->name('notifications.settings.whatsapp.embedded-signup');
            Route::get('storage-settings', [StorageSettingsController::class, 'edit'])->name('storage.settings');
            Route::post('storage-settings', [StorageSettingsController::class, 'update'])->name('storage.settings.update');
            Route::post('storage-settings/r2/test', [StorageSettingsController::class, 'test'])->name('storage.settings.test');
            Route::resource('statistics', StatisticController::class);

            Route::resource('steps', StepController::class);
            Route::resource('return_requests', ReturnRequestController::class);
            Route::post('return_requests/{id}/restore', [ReturnRequestController::class, 'restore'])->name('return_requests.restore');
            Route::post('return_requests/{id}/restore-previous-status', [ReturnRequestController::class, 'restorePreviousStatus'])->name('return_requests.restore_previous_status');
            Route::get('return_requests/export/pdf', [ReturnRequestController::class, 'exportPdf'])->name('return_requests.export.pdf');
            Route::get('return_requests/export/excel', [ReturnRequestController::class, 'exportExcel'])->name('return_requests.export.excel');
            Route::resource('recruitment-offices', OfficeController::class)
                ->parameters(['recruitment-offices' => 'office']);
            Route::resource('airports', AirportController::class);
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
            Route::post('ShelterContract/{id}/vacation', [ShelterContractController::class, 'leaveForVacation'])->name('ShelterContract.vacation');
            Route::post('ShelterContract/{id}/return-from-vacation', [ShelterContractController::class, 'returnFromVacation'])->name('ShelterContract.returnFromVacation');
            Route::post('ShelterContract/{id}/escape-report', [ShelterContractController::class, 'reportEscape'])->name('ShelterContract.escapeReport');
            Route::get('ShelterContract/{id}/escape-report/attachment', [ShelterContractController::class, 'downloadEscapeReportAttachment'])->name('ShelterContract.escapeReport.attachment');
            Route::post('ShelterContract/{id}/quick-tags', [ShelterContractController::class, 'updateQuickTags'])->name('ShelterContract.quickTags');
            Route::get('ShelterContract/living-expenses/list', [ShelterContractController::class, 'livingExpenses'])->name('ShelterContract.livingExpenses');
            Route::post('ShelterContract/living-expenses', [ShelterContractController::class, 'storeLivingExpense'])->name('ShelterContract.livingExpenses.store');
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

            // Istiqdam Integration Routes (wired to real API controller)
            Route::prefix('istiqdam')->name('istiqdam.')->group(function () {
                Route::get('/', [\App\Http\Controllers\admin\IstiqdasmRealController::class, 'index'])->name('index');
                Route::get('/settings', [\App\Http\Controllers\admin\IstiqdasmRealController::class, 'settings'])->name('settings');
                Route::put('/settings', [\App\Http\Controllers\admin\IstiqdasmRealController::class, 'updateSettings'])->name('settings.update');
                Route::get('/test-connection', [\App\Http\Controllers\admin\IstiqdasmRealController::class, 'testConnection'])->name('test.connection');
                Route::get('/diagnose', [\App\Http\Controllers\admin\IstiqdasmRealController::class, 'diagnoseConnection'])->name('diagnose');

                // Packages
                Route::get('/packages/data', [\App\Http\Controllers\admin\IstiqdasmRealController::class, 'getPackages'])->name('packages.data');
                Route::get('/packages/{packageId}', [\App\Http\Controllers\admin\IstiqdasmRealController::class, 'getPackage'])->name('packages.show');

                // Contracts
                Route::get('/contracts/create', [\App\Http\Controllers\admin\IstiqdasmRealController::class, 'createContract'])->name('contracts.create');
                Route::post('/contracts', [\App\Http\Controllers\admin\IstiqdasmRealController::class, 'storeContract'])->name('contracts.store');
                Route::get('/contracts/{contractId}', [\App\Http\Controllers\admin\IstiqdasmRealController::class, 'getContractDetails'])->name('contracts.show');
                Route::delete('/contracts/{contractId}', [\App\Http\Controllers\admin\IstiqdasmRealController::class, 'cancelContract'])->name('contracts.cancel');
                Route::get('/contracts/{contractId}/download/{type?}', [\App\Http\Controllers\admin\IstiqdasmRealController::class, 'downloadContract'])->name('contracts.download');

                // Work Permit Assignment
                Route::post('/contracts/{contractId}/work-permit', [\App\Http\Controllers\admin\IstiqdasmRealController::class, 'assignWorkPermit'])->name('work-permit.assign');
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

            // طلبات استرجاع العقود - الإدارة
            Route::prefix('contract-return-requests')->name('contract-return-requests.')->group(function () {
                Route::get('/', [ContractReturnRequestController::class, 'index'])->name('index');
                Route::get('/statistics', [ContractReturnRequestController::class, 'statistics'])->name('statistics');
                Route::get('/{returnRequest}', [ContractReturnRequestController::class, 'show'])->name('show');
                Route::post('/{returnRequest}/process', [ContractReturnRequestController::class, 'process'])->name('process');
            });

            // طلبات تمديد العقود - الإدارة
            Route::prefix('contract-extension-requests')->name('contract-extension-requests.')->group(function () {
                Route::get('/', [\App\Http\Controllers\ingaz\ContractExtensionRequestController::class, 'index'])->name('index');
                Route::get('/{extensionRequest}', [\App\Http\Controllers\ingaz\ContractExtensionRequestController::class, 'show'])->name('show');
                Route::post('/{extensionRequest}/process', [\App\Http\Controllers\ingaz\ContractExtensionRequestController::class, 'process'])->name('process');
                Route::post('/{extensionRequest}/notes', [\App\Http\Controllers\ingaz\ContractExtensionRequestController::class, 'storeNote'])->name('notes.store');
                Route::delete('/{extensionRequest}/notes/{note}', [\App\Http\Controllers\ingaz\ContractExtensionRequestController::class, 'deleteNote'])->name('notes.delete');
            });
            Route::post('RecruitmentContract/{contractId}/request-extension', [\App\Http\Controllers\ingaz\ContractExtensionRequestController::class, 'store'])
                ->name('RecruitmentContract.requestExtension');
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
            Route::get('/settings/zoho', [AdminSettingsController::class, 'zoho'])->name('settings.zoho');
            Route::post('/settings/zoho', [AdminSettingsController::class, 'updateZoho'])->name('settings.zoho.update');
            Route::post('/settings/store', [AdminSettingsController::class, 'store'])->name('settings.store');

            //** FIREBASE PUSH NOTIFICATIONS **//
            Route::get('/firebase-settings', [FirebaseSettingsController::class, 'index'])->name('firebase-settings.index');
            Route::put('/firebase-settings', [FirebaseSettingsController::class, 'update'])->name('firebase-settings.update');
            Route::post('/firebase-settings/credentials', [FirebaseSettingsController::class, 'uploadCredentials'])->name('firebase-settings.credentials.upload');
            Route::delete('/firebase-settings/credentials', [FirebaseSettingsController::class, 'deleteCredentials'])->name('firebase-settings.credentials.delete');
            Route::post('/firebase-settings/test', [FirebaseSettingsController::class, 'test'])->name('firebase-settings.test');

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
        Route::get('superadmin', [DevSuperAdminController::class, 'loginForm'])->name('superadmin.login');
        Route::post('superadmin', [DevSuperAdminController::class, 'login'])->name('superadmin.login.post');

        Route::prefix('dev-super-admin')->name('dev-superadmin.')->group(function () {
            Route::get('login', function () {
                return redirect()->route('superadmin.login');
            })->name('login');
            Route::post('login', [DevSuperAdminController::class, 'login'])->name('login.post');

            Route::middleware('dev.superadmin')->group(function () {
                Route::get('recovery', [DevSuperAdminController::class, 'recovery'])->name('recovery');
                Route::post('recovery/migrate', [DevSuperAdminController::class, 'runRecoveryMigrations'])
                    ->middleware('throttle:3,1')
                    ->name('recovery.migrate');
                Route::get('modules', [DevSuperAdminController::class, 'modules'])->name('modules');
                Route::get('subscription', [DevSuperAdminController::class, 'modules'])->name('subscription');
                Route::get('product-updates', [DevSuperAdminController::class, 'modules'])->name('product-updates');
                Route::get('employees', [DevSuperAdminController::class, 'modules'])->name('employees');
                Route::get('database-backups', [DevSuperAdminController::class, 'modules'])->name('database-backups');
                Route::get('scheduler', [DevSuperAdminController::class, 'modules'])->name('scheduler');
                Route::put('modules', [DevSuperAdminController::class, 'updateModules'])->name('modules.update');
                Route::put('subscription', [DevSuperAdminController::class, 'updateSubscription'])->name('subscription.update');
                Route::put('product-updates', [DevSuperAdminController::class, 'updateProductUpdates'])->name('product-updates.update');
                Route::post('scheduler/enable', [DevSuperAdminController::class, 'enableScheduler'])->name('scheduler.enable');
                Route::put('database-backups/settings', [DevDatabaseBackupController::class, 'updateSettings'])->name('database-backups.settings');
                Route::put('database-backups/security', [DevSuperAdminController::class, 'updateSecurity'])->name('database-backups.security');
                Route::post('database-backups', [DevDatabaseBackupController::class, 'create'])->name('database-backups.create');
                Route::post('database-backups/download-current', [DevDatabaseBackupController::class, 'downloadCurrent'])->name('database-backups.download-current');
                Route::get('database-backups/download', [DevDatabaseBackupController::class, 'download'])->name('database-backups.download');
                Route::post('database-backups/verification-code', [DevDatabaseBackupController::class, 'requestVerificationCode'])->name('database-backups.verification-code');
                Route::post('database-backups/restore', [DevDatabaseBackupController::class, 'restore'])->name('database-backups.restore');
                Route::post('database-backups/import', [DevDatabaseBackupController::class, 'import'])->name('database-backups.import');
                Route::delete('database-backups/local', [DevDatabaseBackupController::class, 'destroyLocalUploaded'])->name('database-backups.local.destroy');
                Route::delete('database-backups', [DevDatabaseBackupController::class, 'destroy'])->name('database-backups.destroy');
                Route::post('impersonate/{admin}', [DevSuperAdminController::class, 'impersonate'])->name('impersonate');
                Route::post('logout', [DevSuperAdminController::class, 'logout'])->name('logout');
            });
        });

        Route::get('dash/super-admin/modules', function () {
            return redirect()->route('dev-superadmin.modules');
        });
    }
);

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
    ],
    function () {
        Route::group(['prefix' => 'admin', 'middleware' => ['auth:admin', 'admin', 'admin.route.permission']], function () {
            Route::prefix('DeportationTravel')->name('DeportationTravel.')->group(function () {
                Route::get('/', [DeportationTravelController::class, 'index'])->name('index');
                Route::get('/create', [DeportationTravelController::class, 'create'])->name('create');
                Route::get('/statistics', [DeportationTravelController::class, 'statistics'])->name('statistics');
                Route::get('/export', [DeportationTravelController::class, 'export'])->name('export');
                Route::get('/print', [DeportationTravelController::class, 'print'])->name('print');
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

// ===================== نظام العمولات =====================
Route::middleware(['admin', 'admin.route.permission'])
    ->prefix('commissions')
    ->name('commissions.')
    ->group(function () {
        Route::get('finance', [\App\Http\Controllers\commissions\CommissionPaymentController::class, 'financialIndex'])->name('finance.index');
        Route::get('payment-options', [\App\Http\Controllers\commissions\CommissionPaymentController::class, 'financialOptions'])->name('payment-options');

        Route::get('marketers', [\App\Http\Controllers\commissions\CommissionMarketerController::class, 'index'])->name('marketers.index');
        Route::get('marketers/{admin}', [\App\Http\Controllers\commissions\CommissionMarketerController::class, 'show'])->name('marketers.show');
        Route::match(['get', 'post'], 'marketers/{admin}/calculate-preview', [\App\Http\Controllers\commissions\CommissionMarketerController::class, 'preview'])->name('marketers.preview');
        Route::post('marketers/{admin}/calculate-approve', [\App\Http\Controllers\commissions\CommissionMarketerController::class, 'approve'])->name('marketers.approve');
        Route::post('marketers/{admin}/returned-commissions/{item}/resolve', [\App\Http\Controllers\commissions\CommissionMarketerController::class, 'resolveReturnedCommission'])->name('marketers.returned-commissions.resolve');

        Route::get('marketers/{admin}/payments', [\App\Http\Controllers\commissions\CommissionPaymentController::class, 'index'])->name('marketers.payments.index');
        Route::post('marketers/{admin}/payments', [\App\Http\Controllers\commissions\CommissionPaymentController::class, 'store'])->name('marketers.payments.store');
        Route::post('marketers/{admin}/payments/adjustments', [\App\Http\Controllers\commissions\CommissionPaymentController::class, 'adjustBalance'])->name('marketers.payments.adjustments.store');
        Route::post('marketers/{admin}/payments/batches/{batch}/rollback', [\App\Http\Controllers\commissions\CommissionPaymentController::class, 'rollbackBatch'])->name('marketers.payments.batches.rollback');
        Route::post('marketers/{admin}/payments/{payment}/finance-bond', [\App\Http\Controllers\commissions\CommissionPaymentController::class, 'linkFinanceBond'])->name('marketers.payments.finance-bond.store');
        Route::get('marketers/{admin}/payments/{payment}/print', [\App\Http\Controllers\commissions\CommissionPaymentController::class, 'print'])->name('marketers.payments.print');

        Route::get('settings', [\App\Http\Controllers\commissions\CommissionSettingsController::class, 'index'])->name('settings.index');
        Route::post('settings', [\App\Http\Controllers\commissions\CommissionSettingsController::class, 'store'])->name('settings.store');
    });
