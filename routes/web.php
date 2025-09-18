<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Admin\AdminContractController;
use App\Http\Controllers\ingaz\MaidController as IngazMaidController;
use App\Http\Controllers\ingaz\UserController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\ingaz\RecruitmentContractController;
use App\Http\Controllers\ingaz\RecruitmentController;

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

//Route::get('controllers-generate',function (){
//    $projectName = 'ingaz';
//    $migrations = getMigrations($projectName);
//
//    foreach ($migrations as $index => $migration) {
//        $modelName = extractModelName($migration);
//
//        if ($modelName === null) {
//            continue;
//        }
//        Artisan::call("make:controller ".$projectName."/".$modelName."Controller --model=".$modelName);
//        echo $modelName."\n";
//    }
//});
//
//Route::get('requests-generate',function (){
//    $projectName = 'ingaz';
//    $migrations = getMigrations($projectName);
//    foreach ($migrations as $index => $migration) {
//        $modelName = extractModelName($migration);
//
//        if ($modelName === null) {
//            continue;
//        }
//        Artisan::call("make:request ".$projectName."/".$modelName."Request");
//        echo $modelName."\n";
//    }
//});

/** NEW ROUTES */
Route::get('/getMaidDetails/{maidId}', [IngazMaidController::class, 'getMaidDetails'])->name('is.getMaidDetails');
Route::get('/maidsLoopContractsAndSetAvailableWhenDoneCronJob', [AdminContractController::class, 'maidsLoopContractsAndSetAvailableWhenDoneCronJob'])->name('maidsLoopContractsAndSetAvailableWhenDoneCronJob');
Route::get('/check-tazker-contracts', [AdminContractController::class, 'checkTazkerContracts'])->name('check.tazker.contracts');
Route::get('/check-warranty-contracts', [AdminContractController::class, 'checkWarrantyContracts'])->name('check.warranty.contracts');

/** END NEW ROUTES */
Route::get('apis-generate', function () {


    $projectName = 'ingaz';
    $migrations = getMigrations($projectName);
    foreach ($migrations as $index => $migration) {
        $modelName = extractModelName($migration);

        if ($modelName === null) {
            continue;
        }
        // Artisan::call("make:controller Api/Api{$modelName}Controller --model={$projectName}/{$modelName}  --api");
        Artisan::call("make:resource Api/{$modelName}Resource");
        echo $modelName . "\n";
    }
});

// update contracts when status is tazker and travel_histories

// توجيه المسار الأساسي إلى صفحة تسجيل دخول لوحة التحكم
Route::get('/', function () {
    return redirect()->route('admin.form.login');
})->name('home');

// Laravel Migration System Routes
Route::get('/migration', [App\Http\Controllers\MigrationController::class, 'index'])->name('migration.index');
Route::post('/migration/status', [App\Http\Controllers\MigrationController::class, 'checkStatus'])->name('migration.status');
Route::post('/migration/scan-columns', [App\Http\Controllers\MigrationController::class, 'scanColumns'])->name('migration.scan');
Route::post('/migration/add-columns', [App\Http\Controllers\MigrationController::class, 'addMissingColumns'])->name('migration.add_columns');
Route::post('/migration/apply-order', [App\Http\Controllers\MigrationController::class, 'applyCustomOrder'])->name('migration.order');
Route::post('/migration/migrate-all', [App\Http\Controllers\MigrationController::class, 'migrateAllUsers'])->name('migration.migrate_all');
Route::post('/migration/upload-customers', [App\Http\Controllers\MigrationController::class, 'uploadCustomers'])->name('migration.customers');

// Legacy redirect for old public script
Route::get('/migration/legacy', function () {
    return redirect('/migration/file_only_migration.php');
})->name('migration.legacy');

Route::group(['prefix' => 'admin', 'middleware' => ['auth:admin']], function () {
    Route::group(['prefix' => 'ingaz'], function () {
        Route::get('/clients/export/pdf', [UserController::class, 'exportPdf'])->name('clients.export.pdf');
        Route::get('/clients/export/excel', [UserController::class, 'exportExcel'])->name('clients.export.excel');
        Route::get('/get-offices-by-nationality', [App\Http\Controllers\ingaz\MaidController::class, 'getOfficesByNationality'])->name('getOfficesByNationality');

        // تحديث مسارات الإعدادات
        Route::get('settings', [App\Http\Controllers\ingaz\SettingController::class, 'index'])->name('admin.ingaz.settings.index');
        Route::put('settings', [App\Http\Controllers\ingaz\SettingController::class, 'update'])->name('admin.ingaz.settings.update');

        // مسارات الدول
        Route::get('countries', [CountryController::class, 'index'])->name('countries.index');
        Route::get('countries/create', [CountryController::class, 'create'])->name('countries.create');
        Route::post('countries', [CountryController::class, 'store'])->name('countries.store');
        Route::get('countries/{country}/edit', [CountryController::class, 'edit'])->name('countries.edit');
        Route::put('countries/{country}', [CountryController::class, 'update'])->name('countries.update');
        Route::delete('countries/{country}', [CountryController::class, 'destroy'])->name('countries.destroy');
        Route::patch('countries/{country}/toggle-status', [CountryController::class, 'toggleStatus'])->name('countries.toggle-status');
        Route::post('countries/update-order', [CountryController::class, 'updateOrder'])->name('countries.update-order');

        // Recruitment routes
        Route::get('/recruitment/select-maid/{client_id}', [RecruitmentController::class, 'create'])->name('admin.recruitment.select.maid');
        Route::get('/recruitment/show-maid-form/{client_id}/{maid_id}', [RecruitmentController::class, 'showMaidForm'])->name('admin.recruitment.show.maid.form');
        Route::post('/recruitment/store', [RecruitmentController::class, 'store'])->name('admin.recruitment.store');
        Route::get('/recruitment/show-employee-modal/{maid_id}/{client_id}', [RecruitmentController::class, 'showEmployeeModal'])->name('admin.recruitment.show.employee.modal');
    });

    // Add the travel complete route inside the admin group
    Route::post('/travel/complete/{tripId}', [RecruitmentContractController::class, 'completeTrip'])->name('travel.complete');
    Route::post('/RecruitmentContract/completeTrip', [RecruitmentContractController::class, 'completeTrip'])->name('RecruitmentContract.completeTrip');
    Route::post('/travel/add', [RecruitmentContractController::class, 'addTravel'])->name('travel.add');
    Route::post('/RecruitmentContract/updateStatus', [RecruitmentContractController::class, 'updateStatus'])->name('RecruitmentContract.updateStatus');
    Route::post('/RecruitmentContract/cancel', [RecruitmentContractController::class, 'cancel'])->name('RecruitmentContract.cancel');
});

// Notification routes
Route::prefix('admin')->middleware(['auth:admin'])->group(function () {
    Route::get('/notifications', [App\Http\Controllers\Admin\NotificationController::class, 'index']);
    Route::post('/notifications/{id}/mark-as-read', [App\Http\Controllers\Admin\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [App\Http\Controllers\Admin\NotificationController::class, 'markAllAsRead']);

    // SMS Templates Management
    Route::get('sms-templates', [\App\Http\Controllers\admin\SmsTemplateController::class, 'index'])->name('admin.sms-templates.index');
    Route::get('sms-templates/create', [\App\Http\Controllers\admin\SmsTemplateController::class, 'create'])->name('admin.sms-templates.create');
    Route::post('sms-templates', [\App\Http\Controllers\admin\SmsTemplateController::class, 'store'])->name('admin.sms-templates.store');
    Route::get('sms-templates/{smsTemplate}', [\App\Http\Controllers\admin\SmsTemplateController::class, 'show'])->name('admin.sms-templates.show');
    Route::get('sms-templates/{smsTemplate}/edit', [\App\Http\Controllers\admin\SmsTemplateController::class, 'edit'])->name('admin.sms-templates.edit');
    Route::put('sms-templates/{smsTemplate}', [\App\Http\Controllers\admin\SmsTemplateController::class, 'update'])->name('admin.sms-templates.update');
    Route::delete('sms-templates/{smsTemplate}', [\App\Http\Controllers\admin\SmsTemplateController::class, 'destroy'])->name('admin.sms-templates.destroy');
    Route::post('sms-templates/{smsTemplate}/test', [\App\Http\Controllers\admin\SmsTemplateController::class, 'test'])->name('admin.sms-templates.test');
});

// روابط التقييم العامة (بدون تسجيل دخول)
Route::get('/feedback/{token}', [\App\Http\Controllers\ComplaintFeedbackController::class, 'show'])->name('complaint.feedback');
Route::post('/feedback/submit', [\App\Http\Controllers\ComplaintFeedbackController::class, 'submit'])->name('complaint.feedback.submit');

