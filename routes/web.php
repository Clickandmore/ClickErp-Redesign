<?php

use App\Http\Controllers\admin\AdminContractController;
use App\Http\Controllers\admin\BranchController;
use App\Http\Controllers\admin\CountryController;
use App\Http\Controllers\admin\IngazDelegationController;
use App\Http\Controllers\ingaz\MaidController as IngazMaidController;
use App\Http\Controllers\ingaz\RecruitmentContractController;
use App\Http\Controllers\ingaz\RecruitmentController;
use App\Http\Controllers\ingaz\UserController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\ComplaintController;
use App\Http\Controllers\Web\ContactUsController as ContactUs;
use App\Http\Controllers\Web\ContractsController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\MaidController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\OrderController;
use App\Http\Controllers\Web\RentalContractExpiryReminderController;
use App\Http\Controllers\Web\ReturnRequestController;
use App\Http\Controllers\Web\UserAddressController;
use App\Http\Controllers\Web\VisitorChatController;
use App\Services\Zoho\SalesIqClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
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
Route::get('/rental-contract-reminders/{token}', [RentalContractExpiryReminderController::class, 'show'])->name('rental-contract-reminders.show');
Route::post('/rental-contract-reminders/{token}', [RentalContractExpiryReminderController::class, 'respond'])->name('rental-contract-reminders.respond');

Route::prefix('visitor-chat')->middleware('throttle:120,1')->name('visitor-chat.')->group(function () {
    Route::post('/heartbeat', [VisitorChatController::class, 'heartbeat'])->name('heartbeat');
    Route::get('/messages', [VisitorChatController::class, 'messages'])->name('messages');
    Route::post('/messages', [VisitorChatController::class, 'send'])->name('send');
});

/** END NEW ROUTES */
Route::get('/zoho/salesiq/oauth/callback', function (Request $request, SalesIqClient $salesIq) {
    if ($request->filled('error')) {
        return response('Zoho OAuth error: ' . $request->query('error'), 400)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    if (!$request->filled('code')) {
        return response('Missing Zoho authorization code.', 400)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    $tokens = $salesIq->exchangeCodeForTokens((string) $request->query('code'));
    $refreshToken = $tokens['refresh_token'] ?? null;

    if (!$refreshToken) {
        return response("Zoho did not return a refresh_token.\n\nResponse:\n" . json_encode($tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 400)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    return response("Add this value to .env:\n\nZOHO_SALESIQ_REFRESH_TOKEN={$refreshToken}\n\nThen run:\nphp artisan config:clear\nphp artisan salesiq:visitors --limit=10", 200)
        ->header('Content-Type', 'text/plain; charset=UTF-8');
})->name('zoho.salesiq.oauth.callback');

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


// Laravel Migration System Routes
Route::get('/migration', [\App\Http\Controllers\admin\MigrationController::class, 'index'])->name('migration.index');
Route::post('/migration/status', [\App\Http\Controllers\admin\MigrationController::class, 'checkStatus'])->name('migration.status');
Route::post('/migration/scan-columns', [\App\Http\Controllers\admin\MigrationController::class, 'scanColumns'])->name('migration.scan');
Route::post('/migration/add-columns', [\App\Http\Controllers\admin\MigrationController::class, 'addMissingColumns'])->name('migration.add_columns');
Route::post('/migration/apply-order', [\App\Http\Controllers\admin\MigrationController::class, 'applyCustomOrder'])->name('migration.order');
Route::post('/migration/migrate-all', [\App\Http\Controllers\admin\MigrationController::class, 'migrateAllUsers'])->name('migration.migrate_all');
Route::post('/migration/upload-customers', [\App\Http\Controllers\admin\MigrationController::class, 'uploadCustomers'])->name('migration.customers');

// Simple Migration System (Stable Link)
Route::get('/migration/simple', function () {
    return view('migration.simple');
})->name('migration.simple');

// Route لمعالج الملفات (من Laravel)
Route::any('/migration/file_processor.php', function (Illuminate\Http\Request $request) {
    // تمرير الطلب إلى المعالج الأصلي
    $publicPath = public_path('migration/file_processor.php');
    if (file_exists($publicPath)) {
        // تعيين متغيرات الطلب
        $_POST = $request->all();
        $_FILES = $request->files->all();
        $_GET = $request->query();

        // تشغيل المعالج
        ob_start();
        include $publicPath;
        $output = ob_get_clean();

        return response($output)
            ->header('Content-Type', 'application/json; charset=utf-8');
    }

    return response()->json(['success' => false, 'message' => 'المعالج غير موجود'], 404);
})->name('migration.processor');

// Route لمعالج العاملات (من Laravel)
Route::any('/migration/migrate_cvs_now.php', function (Illuminate\Http\Request $request) {
    $publicPath = public_path('migration/migrate_cvs_now.php');
    if (file_exists($publicPath)) {
        $_POST = $request->all();
        $_FILES = $request->files->all();
        $_GET = $request->query();

        ob_start();
        include $publicPath;
        $output = ob_get_clean();

        return response($output)
            ->header('Content-Type', 'application/json; charset=utf-8');
    }

    return response()->json(['success' => false, 'message' => 'معالج العاملات غير موجود'], 404);
})->name('migration.maids_processor');

// Legacy redirect for old public script
Route::get('/migration/legacy', function () {
    return redirect('/migration/file_only_migration.php');
})->name('migration.legacy');

Route::group(['prefix' => 'admin', 'middleware' => ['auth:admin', 'admin.route.permission']], function () {
    Route::group(['prefix' => 'ingaz'], function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');
    });
});

Route::group(
    [
        'as' => 'web.',
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ["webLocale", 'public_site_enabled', 'track.website.visitor', 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath']
    ],
    function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::get('/rights-maids', [HomeController::class, 'rightsMaids'])->name('rights-maids');
        Route::get('/rights-users', [HomeController::class, 'rightsUsers'])->name('rights-users');
        Route::get('/privacy-policy', [HomeController::class, 'privacyPolicy'])->name('privacyPolicy');
        Route::get('/terms-conditions', [HomeController::class, 'termsConditions'])->name('termsConditions');
        Route::get('/faqs', [\App\Http\Controllers\Web\FAQsController::class, 'index'])->name('faqs');
        Route::resource('return_requests', ReturnRequestController::class);
        Route::resource('maids', MaidController::class);

        //================ authanticaion  =============
        Route::get('auth', [AuthController::class, 'index'])->name('auth');
        Route::get('reset-password', [AuthController::class, 'resetPassword'])->name('reset_password');
        Route::get('sendOtp', [AuthController::class, 'sendOtp'])->name('sendOtp');
        Route::get('otp', [AuthController::class, 'otp'])->name('otp');
        Route::post('confirm-code', [AuthController::class, 'confirm_code'])->name('confirm-code');
        Route::post('sendLogin', [AuthController::class, 'sendLogin'])->name('sendLogin');
        Route::group(
            [
                'middleware' => ["auth_web"]
            ],
            function () {
                Route::get('profile', [AuthController::class, 'profile'])->name('profile');
                Route::post('update-profile', [AuthController::class, 'updateProfile'])->name('update-profile');
                Route::get('edit-profile', [AuthController::class, 'editProfile'])->name('edit-profile');
                Route::get('deleteAccount', [AuthController::class, 'deleteAccount'])->name('deleteAccount');
                Route::get("logout", [AuthController::class, "logout"])->name("logout");
                Route::resource('orders', \App\Http\Controllers\Web\OrderController::class);
                Route::post('save-pending-order', [\App\Http\Controllers\Web\OrderController::class, 'savePendingOrder'])->name('save-pending-order');
                Route::resource('contracts', ContractsController::class);
                Route::post('/contracts/cancel', [\App\Http\Controllers\Web\ContractsController::class, 'cancel'])->name('contracts.cancel');



                Route::post('bank_transfer_submit', [\App\Http\Controllers\Web\OrderController::class, 'submitBankTransfer'])->name('bank_transfer.submit');
                Route::resource('addresses', UserAddressController::class);
                Route::resource('complaints', ComplaintController::class);
                Route::resource('notifications', NotificationController::class);

                // طلبات استرجاع العقود
                Route::get('contracts/{contract}/return-request', [\App\Http\Controllers\Web\ContractReturnRequestController::class, 'create'])->name('contract-return-requests.create');
                Route::post('contracts/{contract}/return-request', [\App\Http\Controllers\Web\ContractReturnRequestController::class, 'store'])->name('contract-return-requests.store');
                Route::get('return-requests', [\App\Http\Controllers\Web\ContractReturnRequestController::class, 'index'])->name('contract-return-requests.index');
                Route::get('return-requests/{returnRequest}', [\App\Http\Controllers\Web\ContractReturnRequestController::class, 'show'])->name('contract-return-requests.show');
                Route::delete('return-requests/{returnRequest}', [\App\Http\Controllers\Web\ContractReturnRequestController::class, 'cancel'])->name('contract-return-requests.cancel');

                // طلبات نقل الكفالة
                Route::get('sponsorship-transfers', [\App\Http\Controllers\Web\SponsorshipTransferController::class, 'index'])->name('sponsorship-transfers.index');
                Route::get('sponsorship-transfers/{transfer}', [\App\Http\Controllers\Web\SponsorshipTransferController::class, 'show'])->name('sponsorship-transfers.show');
                Route::post('sponsorship-transfers', [\App\Http\Controllers\Web\SponsorshipTransferController::class, 'store'])->name('sponsorship-transfers.store');
            }
        );
        Route::resource('contact-us', ContactUs::class);
        Route::get('/about-us', [\App\Http\Controllers\Web\AboutUsController::class, 'index'])->name('about-us');

        Route::get('/clients/export/pdf', [UserController::class, 'exportPdf'])->name('clients.export.pdf');
        Route::get('/clients/export/excel', [UserController::class, 'exportExcel'])->name('clients.export.excel');
        Route::resource('skills', \App\Http\Controllers\ingaz\SkillController::class);
        Route::get('/get-offices-by-nationality', [App\Http\Controllers\ingaz\MaidController::class, 'getOfficesByNationality'])->name('getOfficesByNationality');

        // Dashboard route for clients
        Route::get('dashboard', [\App\Http\Controllers\Web\AuthController::class, 'dashboard'])->name('dashboard')->middleware('auth_web');

        // Test route for orders
        Route::get('test-orders', function () {
            return response()->json([
                'orders_count' => \App\Models\ingaz\Order::count(),
                'latest_orders' => \App\Models\ingaz\Order::latest()->take(3)->get(['id', 'user_id', 'maid_id', 'status', 'created_at'])
            ]);
        });

        // Test route for admin orders
        Route::get('test-admin-orders', function () {
            return response()->json([
                'orders_count' => \App\Models\ingaz\Order::count(),
                'orders' => \App\Models\ingaz\Order::with(['user', 'maid', 'jobType'])->latest()->take(5)->get()
            ]);
        });

        // Test route for orders display (no auth required)
        Route::get('orders-display', function () {
            $orders = \App\Models\ingaz\Order::with(['user', 'maid', 'jobType', 'details.admin'])->latest()->get();
            return view('admin.ingaz.orders.index', [
                'totalOrders' => $orders->count(),
                'newOrders' => $orders->where('status', 'new')->count(),
                'underProcessOrders' => $orders->where('status', 'under_process')->count(),
                'completedOrders' => $orders->whereIn('status', ['completed', 'done'])->count(),
                'jobTypes' => \App\Models\ingaz\JobType::with('translations')->get(),
                'admins' => \App\Models\auth\Admin::select('id', 'name')->whereNull('deleted_at')->get(),
                'orders' => $orders
            ]);
        });

        // Test route for client orders display (no auth required)
        Route::get('client-orders-display', function () {
            // Get a test user ID (first user with orders)
            $testUserId = \App\Models\ingaz\Order::first()?->user_id ?? 1;

            // Get orders directly for testing
            $currentOrders = \App\Models\ingaz\Order::where('user_id', $testUserId)
                ->whereIn('status', ['new', 'completed', 'under_process', 'contracted', 'contracted_without_contract'])
                ->with(['user', 'maid', 'jobType', 'details.admin'])
                ->orderBy('created_at', 'desc')
                ->get();

            $previousOrders = \App\Models\ingaz\Order::where('user_id', $testUserId)
                ->whereIn('status', ['refused', 'done'])
                ->with(['user', 'maid', 'jobType', 'details.admin'])
                ->orderBy('created_at', 'desc')
                ->get();

            $data['current'] = $currentOrders;
            $data['previous'] = $previousOrders;
            $data['order_progressbar'] = ['refused' => 0, 'new' => 20, 'paid' => 40, 'completed' => 60, 'started' => 80, 'done' => 100];

            return view('web.ingaz.orders.index', $data);
        });

        // AJAX route for DataTable (no auth required)
        Route::get('orders-ajax', function (\Illuminate\Http\Request $request) {
            $query = \App\Models\ingaz\Order::query();

            // Eager load relationships
            $query->with([
                'user',
                'maid.nationality',
                'jobType',
                'details.admin',
                'contracts'
            ]);

            // Order by created_at desc
            $query->orderBy('created_at', 'desc');

            $orders = $query->get();

            return response()->json([
                'draw' => intval($request->get('draw')),
                'recordsTotal' => $orders->count(),
                'recordsFiltered' => $orders->count(),
                'data' => $orders->map(function ($order) {
                    return [
                        'DT_RowId' => 'row_' . $order->id,
                        'id' => $order->id,
                        'order_code' => $order->order_code,
                        'user_id' => $order->user ? $order->user->name : '-',
                        'phone' => $order->user ? $order->user->phone_code . $order->user->phone : '-',
                        'maid_id' => $order->maid ? $order->maid->name : '-',
                        'nationality_name' => $order->maid && $order->maid->nationality ? $order->maid->nationality->sub_title : '',
                        'maid_passport_num' => $order->maid ? $order->maid->passport_num : '',
                        'job_type_id' => $order->jobType ? $order->jobType->title : '',
                        'status' => $order->status,
                        'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                        'admin_name' => $order->details && $order->details->admin ? $order->details->admin->name : '-',
                        'deliver_status' => $order->details && $order->details->deliver_maid == '1' ? 'نعم' : 'لا',
                        'contract_status' => $order->contracts()->exists() ? 'يوجد عقد' : 'لا يوجد عقد',
                        'actions' => '<div class="btn-group">
                            <a href="#" class="btn btn-sm btn-primary">عرض</a>
                            <a href="#" class="btn btn-sm btn-warning">تعديل</a>
                            <a href="#" class="btn btn-sm btn-danger">حذف</a>
                        </div>'
                    ];
                })
            ]);
        });
    }
);

Route::group(['prefix' => 'admin', 'middleware' => ['auth:admin', 'admin.route.permission']], function () {
    // Orders routes will be handled by routes/ingaz.php

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
        Route::get('countries/{country}', [CountryController::class, 'show'])->name('countries.show');



        Route::post('countries', [CountryController::class, 'store'])->name('countries.store');
        Route::get('countries/{country}/edit', [CountryController::class, 'edit'])->name('countries.edit');
        Route::put('countries/{country}', [CountryController::class, 'update'])->name('countries.update');
        Route::delete('countries/{country}', [CountryController::class, 'destroy'])->name('countries.destroy');
        Route::match(['post', 'patch'], 'countries/{country}/toggle-status', [CountryController::class, 'toggleStatus'])->name('countries.toggle-status');
        Route::post('countries/update-order', [CountryController::class, 'updateOrder'])->name('countries.update-order');

        // مسارات المدن
        Route::get('ingaz-delegations/find-maid-by-passport', [IngazDelegationController::class, 'findMaidByPassport'])
            ->name('ingaz-delegations.find-maid-by-passport');
        Route::get('ingaz-delegations/{ingazDelegation}/print', [IngazDelegationController::class, 'print'])
            ->name('ingaz-delegations.print');
        Route::patch('ingaz-delegations/{ingazDelegation}/status', [IngazDelegationController::class, 'updateStatus'])
            ->name('ingaz-delegations.update-status');
        Route::resource('ingaz-delegations', IngazDelegationController::class);
        Route::get('cities', [CountryController::class, 'citiesIndex'])->name('cities.index');
        Route::get('cities/create', [CountryController::class, 'createCity'])->name('cities.create');
        Route::post('cities', [CountryController::class, 'storeCity'])->name('cities.store');
        Route::get('cities/{city}/edit', [CountryController::class, 'editCity'])->name('cities.edit');
        Route::put('cities/{city}', [CountryController::class, 'updateCity'])->name('cities.update');
        Route::delete('cities/{city}', [CountryController::class, 'destroyCity'])->name('cities.destroy');

        Route::get('branches/report', [BranchController::class, 'report'])->name('branches.report');
        Route::get('branches/settings', [BranchController::class, 'settings'])->name('branches.settings');
        Route::post('branches/{branch}/assign-manager', [BranchController::class, 'assignManager'])->name('branches.assign-manager');
        Route::get('branches/{branch}/admins', [BranchController::class, 'branchAdmins'])->name('branches.admins');
        Route::get('branches/{branch}/available-admins', [BranchController::class, 'availableAdmins'])->name('branches.available-admins');
        Route::post('branches/{branch}/assign-admin', [BranchController::class, 'assignAdmin'])->name('branches.assign-admin');
        Route::post('branches/{branch}/remove-admin', [BranchController::class, 'removeAdmin'])->name('branches.remove-admin');
        Route::resource('branches', BranchController::class)->except(['show']);

        // API routes للدول والمدن
        Route::get('api/countries', [CountryController::class, 'getCountries'])->name('api.countries');
        Route::get('api/countries/{countryId}/cities', [CountryController::class, 'getCities'])->name('api.countries.cities');
        Route::get('api/countries/search', [CountryController::class, 'search'])->name('api.countries.search');
        Route::get('api/cities', [CountryController::class, 'getAllCities'])->name('api.cities');
        Route::get('api/cities/search', [CountryController::class, 'searchCities'])->name('api.cities.search');
        Route::get('api/cities/{city}', [CountryController::class, 'getCityDetails'])->name('api.cities.show');
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
    Route::post('/RecruitmentContract/bulk-update-status', [RecruitmentContractController::class, 'bulkUpdateStatus'])->name('RecruitmentContract.bulkUpdateStatus');
    Route::post('/RecruitmentContract/cancel', [RecruitmentContractController::class, 'cancel'])->name('RecruitmentContract.cancel');
});

// Notification routes
Route::prefix('admin')->middleware(['auth:admin', 'admin.route.permission'])->group(function () {
    Route::get('/notifications', [App\Http\Controllers\admin\NotificationController::class, 'index'])->name('admin.notifications.dropdown');
    Route::post('/notifications/{id}/mark-as-read', [App\Http\Controllers\admin\NotificationController::class, 'markAsRead'])->name('admin.notifications.mark-as-read');
    Route::post('/notifications/mark-all-read', [App\Http\Controllers\admin\NotificationController::class, 'markAllAsRead'])->name('admin.notifications.mark-all-read');

    // SMS Templates Management
    Route::get('sms-templates', [\App\Http\Controllers\admin\SmsTemplateController::class, 'index'])->name('admin.sms-templates.index');
    Route::get('sms-templates/create', [\App\Http\Controllers\admin\SmsTemplateController::class, 'create'])->name('admin.sms-templates.create');
    Route::post('sms-templates', [\App\Http\Controllers\admin\SmsTemplateController::class, 'store'])->name('admin.sms-templates.store');
    Route::post('sms-templates/bulk-update', [\App\Http\Controllers\admin\SmsTemplateController::class, 'bulkUpdate'])->name('admin.sms-templates.bulk-update');
    Route::get('sms-templates/{smsTemplate}', [\App\Http\Controllers\admin\SmsTemplateController::class, 'show'])->name('admin.sms-templates.show');
    Route::get('sms-templates/{smsTemplate}/edit', [\App\Http\Controllers\admin\SmsTemplateController::class, 'edit'])->name('admin.sms-templates.edit');
    Route::put('sms-templates/{smsTemplate}', [\App\Http\Controllers\admin\SmsTemplateController::class, 'update'])->name('admin.sms-templates.update');
    Route::delete('sms-templates/{smsTemplate}', [\App\Http\Controllers\admin\SmsTemplateController::class, 'destroy'])->name('admin.sms-templates.destroy');
    Route::post('sms-templates/{smsTemplate}/test', [\App\Http\Controllers\admin\SmsTemplateController::class, 'test'])->name('admin.sms-templates.test');
    Route::post('sms-templates/{smsTemplate}/send', [\App\Http\Controllers\admin\SmsTemplateController::class, 'send'])->name('admin.sms-templates.send');

    Route::prefix('zoho-salesiq')->name('admin.zoho-salesiq.')->group(function () {
        Route::get('visitors', [\App\Http\Controllers\admin\ZohoSalesIqController::class, 'visitors'])->name('visitors');
        Route::get('conversations', [\App\Http\Controllers\admin\ZohoSalesIqController::class, 'conversations'])->name('conversations.index');
        Route::get('conversations/{conversationId}', [\App\Http\Controllers\admin\ZohoSalesIqController::class, 'conversation'])
            ->where('conversationId', '[^/]+')
            ->name('conversations.show');
    });
});

// روابط التقييم العامة (بدون تسجيل دخول)
Route::get('/feedback/{token}', [\App\Http\Controllers\admin\ComplaintFeedbackController::class, 'show'])->name('complaint.feedback');
Route::post('/feedback/submit', [\App\Http\Controllers\admin\ComplaintFeedbackController::class, 'submit'])->name('complaint.feedback.submit');

// Stable Simple Migration Routes
Route::get('/migration-system', function () {
    return redirect('/migration.php');
})->name('migration.redirect');

Route::get('/simple-migration', function () {
    return redirect('/migration.php');
})->name('simple.migration.redirect');
