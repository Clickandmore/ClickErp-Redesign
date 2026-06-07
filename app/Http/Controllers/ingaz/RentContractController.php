<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Enums\ingaz\OrderEnum;
use App\Http\Actions\ingaz\RecruitmentOfficeAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\StoreRentalContractRequest;
use App\Models\Branch;
use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\ContractPayment;
use App\Models\Country;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Order;
use App\Models\ingaz\Package;
use App\Models\ingaz\User;
use App\Models\RentalContract;
use App\Models\RentalContractExtension;
use App\Models\RentalContractInfo;
use App\Models\RentalContractTerm;
use App\Services\ingaz\RentalContractFinanceService;
use App\Support\SystemModuleManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class RentContractController extends Controller
{
    public $folderPath = "admin.ingaz.RentContract";
    public $mainRoute = "RentContract";

    public function __construct()
    {
        $this->middleware('permission:' . \App\PermKeys::RENT_CONTRACT_VIEW . ',admin', [
            'only' => ['index', 'show', 'statistics', 'exportExcel', 'getContractDetails', 'downloadAttachments', 'downloadAttachment', 'viewAttachment'],
        ]);
        $this->middleware('permission:' . \App\PermKeys::RENT_CONTRACT_CREATE . ',admin', [
            'only' => ['create', 'store', 'getExistingClients'],
        ]);
        $this->middleware('permission:' . \App\PermKeys::RENT_CONTRACT_EDIT . ',admin', [
            'only' => ['edit', 'update', 'changeMaid'],
        ]);
        $this->middleware('permission:' . \App\PermKeys::RENT_CONTRACT_DELETE . ',admin', [
            'only' => ['destroy'],
        ]);
        $this->middleware('permission:' . \App\PermKeys::RENT_CONTRACT_CONTROL . ',admin', [
            'only' => [
                'completeContract',
                'closeAndMakeAvailable',
                'transferSponsorship',
                'sendContract',
                'signContract',
                'deliverMaid',
                'activateContract',
                'extend',
                'freeze',
                'resume',
                'saveSignature',
            ],
        ]);
    }

    public function index(Request $request, $status = null)
    {
        if ($request->ajax()) {
            $status = $request->get('status', $status);
            $branchesEnabled = $this->branchesEnabledFor('rental_contracts');

            $relations = ['user', 'maid', 'info', 'marketer', 'financeInvoice'];
            if ($branchesEnabled) {
                $relations[] = 'branch';
            }

            $query = RentalContract::with($relations)
                ->withCount('extensions');

            // Apply status filter with closure type consideration
            if ($status) {
                $this->applyRentalContractStatusFilter($query, $status);
            }

            // Apply other filters
            if ($request->filled('status_filter')) {
                $this->applyRentalContractStatusFilter($query, $request->status_filter);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('contract_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('contract_date', '<=', $request->date_to);
            }

            if ($request->filled('marketer_id')) {
                $query->where('marketer_id', $request->marketer_id);
            }

            if ($branchesEnabled && $request->filled('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            if ($request->filled('client_phone')) {
                $phone = $request->client_phone;
                $query->whereHas('user', function ($q) use ($phone) {
                    $q->where('phone', 'like', "%{$phone}%")
                        ->orWhereRaw("CONCAT(phone_code, phone) LIKE ?", ["%{$phone}%"]);
                });
            }

            if ($request->filled('client_name')) {
                $name = $request->client_name;
                $query->whereHas('user', function ($q) use ($name) {
                    $q->where('name', 'like', "%{$name}%");
                });
            }

            if ($request->filled('maid_passport')) {
                $passport = $request->maid_passport;
                $query->whereHas('maid', function ($q) use ($passport) {
                    $q->where('passport_num', 'like', "%{$passport}%");
                });
            }

            if ($request->filled('maid_name')) {
                $maidName = $request->maid_name;
                $query->whereHas('maid', function ($q) use ($maidName) {
                    $q->where('name', 'like', "%{$maidName}%");
                });
            }

            if ($request->filled('contract_number')) {
                $query->where('contract_number', 'like', '%' . $request->contract_number . '%');
            }

            if ($request->filled('request_type')) {
                $query->whereHas('maid', function ($q) use ($request) {
                    $q->ofRequestType($request->request_type);
                });
            }

            if ($request->filled('warranty_status')) {
                $query->where('warranty_status', $request->warranty_status);
            }

            if ($request->filled('extension_filter')) {
                if ($request->extension_filter === 'extended') {
                    $query->whereHas('extensions');
                } elseif ($request->extension_filter === 'not_extended') {
                    $query->whereDoesntHave('extensions');
                }
            }

            if ($request->filled('expiry_filter')) {
                $this->applyRentalContractExpiryFilter($query, $request->expiry_filter);
            }

            $contracts = $query->latest();

            return DataTables::eloquent($contracts)
                ->addIndexColumn()
                ->editColumn('status', function ($row) {
                    return $this->getStatusBadge($row->status, $row->closure_type);
                })
                ->editColumn('user_id', function ($row) {
                    return $row->user ? $row->user->name : 'غير محدد';
                })
                ->addColumn('phone', function ($row) {
                    if ($row->user && $row->user->phone) {
                        $fullPhone = $row->user->phone_code . $row->user->phone;
                        return '<a href="tel:' . $fullPhone . '" class="text-decoration-none">
                                    <i class="fas fa-phone-alt me-1"></i>' . $fullPhone . '
                                </a>';
                    }
                    return '<span class="text-muted">غير محدد</span>';
                })
                ->editColumn('maid_id', function ($row) {
                    if ($row->maid) {
                        return '<div class="d-flex align-items-center">
                                    <img src="' . $row->maid->photo_url . '" class="rounded-circle me-2" width="40" height="40">
                                    <div>
                                        <div class="fw-bold">' . $row->maid->name . '</div>
                                        <small class="text-muted d-block">' . $row->maid->passport_num . '</small>
                                        <small class="text-muted d-block">' . helperTrans('ingaz.request_type') . ': ' . e($row->maid->request_type_label) . '</small>
                                    </div>
                                </div>';
                    }
                    return '<span class="text-muted">لم يتم اختيار عاملة</span>';
                })
                ->addColumn('contract_info', function ($row) {
                    if ($row->info) {
                        $extensionBadge = ($row->extensions_count ?? 0) > 0
                            ? '<span class="badge bg-info">ممدد</span>'
                            : '<span class="badge bg-secondary">غير ممدد</span>';
                        $durationMonths = $row->info->contract_duration_months ?? null;
                        $durationDays = $row->info->contract_duration_days ?? null;
                        $durationText = $durationMonths ? $durationMonths . ' شهر' : 'غير محدد';
                        if ($durationDays) {
                            $durationText .= ' / ' . $durationDays . ' يوم';
                        }
                        return '<div class="small">
                                    <div><strong>المدة:</strong> ' . $durationText . '</div>
                                    <div><strong>التكلفة الشهرية:</strong> ' . number_format($row->info->monthly_cost ?? 0, 2) . ' ريال</div>
                                    <div><strong>قيمة العقد:</strong> ' . number_format($row->info->contract_value ?? 0, 2) . ' ريال</div>
                                </div>';
                    }
                    return 'غير محدد';
                })
                ->addColumn('marketer', function ($row) {
                    return $row->marketer ? $row->marketer->name : 'غير محدد';
                })
                ->addColumn('branch', function ($row) use ($branchesEnabled) {
                    if (!$branchesEnabled) {
                        return '-';
                    }

                    return $row->branch ? e($row->branch->name) : '-';
                })
                ->addColumn('remaining_days', function ($row) {
                    // Only show remaining days for active contracts
                    if ($row->status !== 'completed') {
                        $remainingDays = $row->calculateRemainingDays();
                        if ($remainingDays !== null) {
                            if ($remainingDays > 0) {
                                return '<span class="badge bg-success">' . $remainingDays . ' يوم متبقي</span>';
                            } else {
                                return '<span class="badge bg-danger">منتهي منذ ' . abs((int) $remainingDays) . ' يوم</span>';
                            }
                        }
                        return '<span class="badge bg-secondary">غير محدد</span>';
                    } else {
                        // For completed contracts, show closure info
                        if ($row->closure_type === 'transferred') {
                            return '<span class="badge bg-warning">تم النقل</span>';
                        } elseif ($row->closure_type === 'available') {
                            return '<span class="badge bg-info">أصبحت متاحة</span>';
                        } else {
                            return '<span class="badge bg-secondary">مكتمل</span>';
                        }
                    }
                })
                ->addColumn('actions', function ($row) {
                    $actions = '<div class="dropdown actions-dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button"
                                            aria-expanded="false">
                                        <i class="fas fa-cogs"></i> الإجراءات
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-lg">';

                    // عرض التفاصيل
                    $actions .= '<li><a class="dropdown-item" href="' . route($this->mainRoute . '.show', $row->id) . '">
                                    <i class="fas fa-eye"></i> عرض التفاصيل
                                </a></li>';

                    // طباعة العقد المحسّن
                    $actions .= '<li><a class="dropdown-item" href="' . route('print.rental.contract', $row->id) . '" target="_blank">
                                    <i class="fas fa-print"></i> طباعة العقد
                                </a></li>';

                    $actions .= '<li><a class="dropdown-item" href="' . route('RentContract.attachments.download', $row->id) . '">
                                    <i class="fas fa-paperclip"></i> تحميل مرفقات العقد
                                </a></li>';

                    if ($row->financeInvoice) {
                        $actions .= '<li><a class="dropdown-item" href="' . route('rental.invoice.download', ['id' => $row->financeInvoice->id, 'autoprint' => 1]) . '" target="_blank">
                                        <i class="fas fa-file-invoice"></i> طباعة الفاتورة
                                    </a></li>';

                        if ($row->financeInvoice->receipt_bond_id) {
                            $actions .= '<li><a class="dropdown-item" href="' . route('finance.bonds-print.show', [
                                            'bonds_print' => $row->financeInvoice->receipt_bond_id,
                                            'type' => 'qabd',
                                        ]) . '" target="_blank">
                                            <i class="fas fa-receipt"></i> طباعة سند القبض
                                        </a></li>';
                        }

                        if ($row->financeInvoice->invoice_constraint_id) {
                            $actions .= '<li><a class="dropdown-item" href="' . route('finance.add-constraint.show', [
                                            'add_constraint' => $row->financeInvoice->invoice_constraint_id,
                                            'autoprint' => 1,
                                        ]) . '" target="_blank">
                                            <i class="fas fa-book"></i> قيد الفاتورة
                                        </a></li>';
                        }

                        if ($row->financeInvoice->receipt_constraint_id) {
                            $actions .= '<li><a class="dropdown-item" href="' . route('finance.add-constraint.show', [
                                            'add_constraint' => $row->financeInvoice->receipt_constraint_id,
                                            'autoprint' => 1,
                                        ]) . '" target="_blank">
                                            <i class="fas fa-book-open"></i> قيد سند القبض
                                        </a></li>';
                        }
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CONTROL) && $row->canSignContract()) {
                        $actions .= '<li><button class="dropdown-item text-success sign-contract" data-id="' . $row->id . '">
                                        <i class="fas fa-signature"></i> تأكيد توقيع العقد
                                    </button></li>';
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CONTROL) && $row->canDeliverMaid()) {
                        $actions .= '<li><button class="dropdown-item text-primary deliver-maid" data-id="' . $row->id . '">
                                        <i class="fas fa-handshake"></i> تأكيد تسليم العاملة
                                    </button></li>';
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CONTROL) && $row->canActivateContract()) {
                        $actions .= '<li><button class="dropdown-item text-success activate-contract" data-id="' . $row->id . '">
                                        <i class="fas fa-power-off"></i> تفعيل العقد
                                    </button></li>';
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CONTROL) && $row->canFreeze()) {
                        $actions .= '<li><button class="dropdown-item text-warning freeze-contract" data-id="' . $row->id . '">
                                        <i class="fas fa-pause-circle"></i> تجميد العقد
                                    </button></li>';
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CONTROL) && $row->canResume()) {
                        $actions .= '<li><button class="dropdown-item text-success resume-contract" data-id="' . $row->id . '">
                                        <i class="fas fa-play-circle"></i> استئناف العقد
                                    </button></li>';
                    }

                    // تعديل العقد
                    if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_EDIT)) {
                        $actions .= '<li><hr class="dropdown-divider"></li>';
                        $actions .= '<li><a class="dropdown-item" href="' . route($this->mainRoute . '.edit', $row->id) . '">
                                        <i class="fas fa-edit"></i> تعديل العقد
                                    </a></li>';
                    }

                    // إنهاء العقد
                    if (
                        @AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CONTROL)
                        && $row->canCloseOrExtend()
                    ) {
                        $contractLabel = $row->contract_number ?: $row->id;
                        $currentStartDate = optional($row->info?->ingaz_from)->format('Y-m-d') ?: '';
                        $currentEndDate = optional($row->info?->ingaz_to)->format('Y-m-d') ?: '';
                        $monthlyCost = (float) ($row->info->monthly_cost ?? 0);
                        $contractValue = (float) ($row->info->contract_value ?? 0);
                        $durationMonths = (int) ($row->info->contract_duration_months ?? 0);
                        $durationDays = (int) ($row->info->contract_duration_days ?? 0);
                        if ($durationDays <= 0 && $durationMonths > 0) {
                            $durationDays = $durationMonths * 30;
                        }
                        $remainingDays = $row->info?->ingaz_to
                            ? now()->startOfDay()->diffInDays(Carbon::parse($row->info->ingaz_to)->startOfDay(), false)
                            : '';
                        $usedDays = '';
                        if ($row->info?->ingaz_from && $durationDays > 0) {
                            $usedDays = min($durationDays, max(0, Carbon::parse($row->info->ingaz_from)->startOfDay()->diffInDays(now()->startOfDay(), false) + 1));
                        }
                        $extensionsCount = (int) ($row->extensions_count ?? 0);

                        $actions .= '<li><button class="dropdown-item text-info open-extend-modal"
                                        data-extend-url="' . route('RentContract.extend', $row->id) . '"
                                        data-contract-number="' . e($contractLabel) . '"
                                        data-ingaz-from="' . e($currentStartDate) . '"
                                        data-ingaz-to="' . e($currentEndDate) . '"
                                        data-monthly-cost="' . $monthlyCost . '"
                                        data-contract-value="' . $contractValue . '"
                                        data-duration-months="' . $durationMonths . '"
                                        data-duration-days="' . $durationDays . '"
                                        data-remaining-days="' . e((string) $remainingDays) . '"
                                        data-used-days="' . e((string) $usedDays) . '"
                                        data-extensions-count="' . $extensionsCount . '">
                                        <i class="fas fa-calendar-plus"></i> تمديد العقد
                                    </button></li>';
                    }

                    if (
                        @AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CONTROL)
                        && $row->canCloseOrExtend()
                    ) {
                        $actions .= '<li><button class="dropdown-item complete-contract" data-id="' . $row->id . '">
                                        <i class="fas fa-check"></i> إنهاء العقد
                                    </button></li>';
                    }

                    // حذف العقد
                    if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_DELETE)) {
                        $actions .= '<li><hr class="dropdown-divider"></li>';
                        $actions .= '<li><button class="dropdown-item text-danger delete-contract" data-id="' . $row->id . '">
                                        <i class="fas fa-trash"></i> حذف العقد
                                    </button></li>';
                    }

                    $actions .= '</ul></div>';

                    return $actions;
                })
                ->rawColumns(['phone', 'maid_id', 'contract_info', 'remaining_days', 'actions', 'status'])
                ->make(true);
        }

        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["modalType"] = "modal-lg";
        $data['branchesEnabled'] = $this->branchesEnabledFor('rental_contracts');
        $data['branches'] = $data['branchesEnabled']
            ? Branch::query()->active()->ordered()->get(['id', 'name_ar', 'name_en'])
            : collect();
        $data['marketers'] = Admin::query()->whereNull('deleted_at')->orderBy('name')->get(['id', 'name']);
        return view($this->folderPath . '.index', $data);
    }

    public function create(RecruitmentOfficeAction $officeAction, Request $request)
    {
        $rentalJobTypeId = $this->getRentalJobTypeId();

        \Log::info('RentContract create method called', [
            'request_params' => $request->all(),
            'has_order_id' => $request->has('order_id'),
            'order_id' => $request->get('order_id')
        ]);

        // الطلبات الجاهزة لإنشاء عقد تأجير، سواء كانت مقبولة أو تم إنهاؤها من شاشة الطلبات.
        $endedOrders = Order::where('job_type_id', $rentalJobTypeId)
                           ->whereIn('status', [
                               OrderEnum::UnderProcess->value,
                               OrderEnum::Done->value,
                           ])
                           ->whereDoesntHave('rentalContracts')
                           ->with(['user', 'maid'])
                           ->latest()
                           ->get();

        // إذا تم تمرير order_id، احصل على بيانات الطلب
        $selectedOrder = null;
        if ($request->has('order_id')) {
            $selectedOrder = Order::with(['user', 'maid.nationality.translations', 'maid.religion.translations', 'details.package'])
                                ->where('job_type_id', $rentalJobTypeId)
                                ->whereIn('status', [
                                    OrderEnum::UnderProcess->value,
                                    OrderEnum::Done->value,
                                ])
                                ->whereDoesntHave('rentalContracts')
                                ->find($request->order_id);

            \Log::info('Selected order for rent contract', [
                'order_id' => $request->order_id,
                'selected_order' => $selectedOrder ? [
                    'id' => $selectedOrder->id,
                    'user_id' => $selectedOrder->user_id,
                    'maid_id' => $selectedOrder->maid_id,
                    'user_name' => $selectedOrder->user ? $selectedOrder->user->name : null,
                    'maid_name' => $selectedOrder->maid ? $selectedOrder->maid->name : null,
                ] : null
            ]);
        }

        // الحصول على العاملات المتاحة من نوع تأجير
        $requestedMaidId = $request->filled('maid_id')
            ? (int) $request->input('maid_id')
            : null;

        $availableRentalMaids = $this->getAvailableRentalMaidsQuery(
            $selectedOrder?->maid_id ?: $requestedMaidId
        )->get();

        $packages = Package::with(['translations', 'nationality.translations'])
            ->visible()
            ->ordered()
            ->get();

        // الحصول على المسوقين النشطين
        $marketers = Admin::whereNull('deleted_at')
                         ->orderBy('name')
                         ->get();

        // الحصول على الجنسيات مع الترجمات
        $nationalities = Nationality::with('translations')->get();

        // الحصول على العملاء الموجودين - نفس الطريقة المستخدمة في عقود الاستقدام
        $existing_clients = User::with('nationality')->get();

        // تسجيل عدد العملاء للتشخيص
        \Log::info('Existing clients loaded for rent contract create', [
            'count' => $existing_clients->count(),
            'first_3' => $existing_clients->take(3)->pluck('name', 'id')->toArray()
        ]);

        $offices = $officeAction->getAllOffices();

        // إضافة متغير لتحديد ما إذا كانت هناك طلبات متاحة
        $hasEndedOrders = $endedOrders->count() > 0;
        $countries = Country::active()->ordered()->get();

        \Log::info('RentContract create view data', [
            'selectedOrder' => $selectedOrder ? [
                'id' => $selectedOrder->id,
                'user_id' => $selectedOrder->user_id,
                'maid_id' => $selectedOrder->maid_id,
                'user_name' => $selectedOrder->user ? $selectedOrder->user->name : null,
                'maid_name' => $selectedOrder->maid ? $selectedOrder->maid->name : null,
            ] : null,
            'availableRentalMaids_count' => $availableRentalMaids->count(),
            'existing_clients_count' => $existing_clients->count()
        ]);

        $selectedClientId = $request->get('client_id');
        return view($this->folderPath . '.create', compact(
            'endedOrders',
            'availableRentalMaids',
            'packages',
            'marketers',
            'nationalities',
            'existing_clients',
            'offices',
            'hasEndedOrders',
            'countries',
            'selectedOrder',
            'selectedClientId'
        ));
    }

    public function store(StoreRentalContractRequest $request, RentalContractFinanceService $rentalContractFinanceService)
    {
        try {
            DB::beginTransaction();

            $profession = $request->filled('profession')
                ? $request->profession
                : $request->new_client_job;
            $accountClientType = $request->input('account_client_type') === 'company' ? 'company' : 'person';

            // إنشاء أو تحديث بيانات العميل
            if ($request->new_existing_client_id) {
                // استخدام عميل موجود وتحديث بياناته
                $user = User::find($request->new_existing_client_id);
                $user->client_type = $accountClientType;
                $user->update($user->clientTypeAwarePayload([
                    'client_type' => $accountClientType,
                    'name' => $request->new_client_name,
                    'phone' => $request->new_client_phone,
                    'nationality_id' => $request->client_nationality_id,
                    'email' => $request->new_client_email,
                    'license_number' => $accountClientType === 'company' ? $request->new_client_license_number : null,
                    'tax_number' => $accountClientType === 'person' ? null : $user->tax_number,
                    'birthdate' => $request->new_client_birthdate,
                    'phone2' => $request->new_client_phone2,
                    'address' => $request->new_client_address,
                    'city' => $request->new_client_city,
                    'rooms_num' => $request->new_client_beds_num,
                    'family_num' => $request->new_client_family_num,
                    'children' => $request->new_client_children,
                    'job' => $request->new_client_job,
                    'profession' => $profession,
                    'monthly_income' => $request->monthly_income,
                ], $request->new_client_national_num));
            } else {
                // إنشاء عميل جديد
                $userPayload = [
                    'name' => $request->new_client_name,
                    'phone' => $request->new_client_phone,
                    'phone_code' => '+966',
                    'nationality_id' => $request->client_nationality_id,
                    'email' => $request->new_client_email,
                    'client_type' => $accountClientType,
                    'birthdate' => $request->new_client_birthdate,
                    'phone2' => $request->new_client_phone2,
                    'address' => $request->new_client_address,
                    'city' => $request->new_client_city,
                    'rooms_num' => $request->new_client_beds_num,
                    'family_num' => $request->new_client_family_num,
                    'children' => $request->new_client_children,
                    'job' => $request->new_client_job,
                    'profession' => $profession,
                    'monthly_income' => $request->monthly_income,
                    'register_from' => 'admin',
                    'status' => 1,
                ];

                if ($accountClientType === 'company') {
                    foreach (['national_num', 'birthdate', 'rooms_num', 'family_num', 'children', 'job', 'profession', 'monthly_income'] as $field) {
                        unset($userPayload[$field]);
                    }

                    $userPayload['tax_number'] = $request->new_client_national_num;
                    $userPayload['license_number'] = $request->new_client_license_number;
                } else {
                    $userPayload['national_num'] = $request->new_client_national_num;
                    $userPayload['tax_number'] = null;
                    $userPayload['license_number'] = null;
                }

                $user = User::create($userPayload);
            }

            $selectedMaid = Maid::with('nationality')->find($request->maid_id);
            $monthlySalary = (float) ($request->monthly_salary ?: 0);
            if ($monthlySalary <= 0 && $selectedMaid) {
                $monthlySalary = (float) ($selectedMaid->resolved_salary ?: 0);
            }

            // إنشاء عقد التأجير
            $rentalContract = RentalContract::create([
                'user_id' => $user->id,
                'maid_id' => $request->maid_id,
                'marketer_id' => $request->marketer_id,
                'order_id' => $request->order_id,
                'status' => 'contract_sent',
                'contract_date' => $request->contract_date,
                'contract_number' => $this->generateContractNumber(),
                'contract_sent_at' => now(),
                'contract_sent_by' => auth('admin')->id(),
            ]);

            $packageId = (int) ($request->input('package_id') ?: $request->input('selected_package_id'));
            $selectedPackage = Package::find($packageId);
            $contractDurationDays = (int) ($request->contract_duration_days ?: ($selectedPackage?->days_count ?: ($request->contract_duration_months * 30)));
            $contractDurationMonths = (int) ($request->contract_duration_months ?: max(1, (int) ceil($contractDurationDays / 30)));
            $monthlyCost = (float) ($request->monthly_cost ?: $this->resolvePackagePeriodCost($selectedPackage, $contractDurationMonths));
            $ingazTo = $request->ingaz_to ?: \Carbon\Carbon::parse($request->ingaz_from)->addDays($contractDurationDays);

            $contractValue = (float) ($request->total_contract_value ?: ($selectedPackage?->price_with_tax ?: ($monthlyCost * $contractDurationMonths)));
            $packagePriceSnapshot = $this->buildPackagePriceSnapshot($selectedPackage, $contractValue);
            $paidAmount = 0.0;

            if ($request->payment_status === 'paid') {
                $paidAmount = $contractValue;
            } elseif ($request->payment_status === 'partial') {
                $paidAmount = min((float) $request->paid_amount, $contractValue);
            }

            $remainingAmount = max(0, $contractValue - $paidAmount);
            $paymentReceiptPath = $request->hasFile('payment_receipt')
                ? $request->file('payment_receipt')->store('rental-contracts/create/receipts', 'public')
                : null;

            // إنشاء معلومات العقد
            RentalContractInfo::create([
                'rental_contract_id' => $rentalContract->id,
                'package_id' => $packageId,
                'package_original_price' => $packagePriceSnapshot['package_original_price'],
                'package_discount_amount' => $packagePriceSnapshot['package_discount_amount'],
                'package_price_after_discount' => $packagePriceSnapshot['package_price_after_discount'],
                'package_vat_amount' => $packagePriceSnapshot['package_vat_amount'],
                'package_total_with_tax' => $packagePriceSnapshot['package_total_with_tax'],
                'payment_type' => $request->payment_type,
                'payment_status' => $request->payment_status,
                'payment_receipt_path' => $paymentReceiptPath,
                'payment_confirmed_at' => $paidAmount > 0 ? now() : null,
                'payment_confirmed_by' => $paidAmount > 0 ? auth('admin')->id() : null,
                'working_hours_per_day' => $request->working_hours ?: 8,
                'hourly_rate' => $request->hourly_rate,
                'monthly_salary' => $monthlySalary,
                'sponsorship_transfer' => $request->sponsorship_transfer,
                'rental_type' => $request->rental_type,
                'monthly_cost' => $monthlyCost,
                'contract_duration_months' => $contractDurationMonths,
                'contract_duration_days' => $contractDurationDays,
                'contract_value' => $contractValue,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'ingaz_from' => $request->ingaz_from,
                'ingaz_to' => $ingazTo,
                'notes' => $request->notes,
            ]);

            $rentalContract->loadMissing(['user', 'maid.nationality', 'info']);
            $financeResult = $rentalContractFinanceService->createForContract($rentalContract, $selectedPackage);
            $financeInvoice = $financeResult['invoice'] ?? null;
            $receiptBond = $financeResult['receipt_bond'] ?? null;
            $invoiceConstraint = $financeResult['invoice_constraint'] ?? null;
            $receiptConstraint = $financeResult['receipt_constraint'] ?? null;

            // تحديث حالة العاملة
            if ($selectedMaid) {
                $selectedMaid->update([
                    'status' => 'contracted',
                    'available' => false,
                ]);
            }

            // تحديث حالة الطلب إذا كان موجود
        if ($request->order_id) {
                Order::where('id', $request->order_id)->update([
                    'status' => 'contracted'
                ]);
            }

            DB::commit();

            // إذا كان الطلب AJAX، أرجع استجابة JSON
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إنشاء عقد التأجير بنجاح',
                    'contract_id' => $rentalContract->id,
                    'contract_number' => $rentalContract->contract_number,
                    'print_url' => route('print.rental.contract', $rentalContract->id),
                    'invoice_print_url' => $financeInvoice
                        ? route('rental.invoice.download', ['id' => $financeInvoice->id, 'autoprint' => 1])
                        : null,
                    'receipt_bond_print_url' => $receiptBond
                        ? route('finance.bonds-print.show', ['bonds_print' => $receiptBond->id, 'type' => 'qabd'])
                        : null,
                    'invoice_constraint_print_url' => $invoiceConstraint
                        ? route('finance.add-constraint.show', ['add_constraint' => $invoiceConstraint->id, 'autoprint' => 1])
                        : null,
                    'receipt_constraint_print_url' => $receiptConstraint
                        ? route('finance.add-constraint.show', ['add_constraint' => $receiptConstraint->id, 'autoprint' => 1])
                        : null,
                    'redirect_url' => route($this->mainRoute . '.index')
                ]);
            }

            return redirect()->route($this->mainRoute . '.index')
                           ->with('success', 'تم إنشاء عقد التأجير بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating rental contract: ' . $e->getMessage());

            // إذا كان الطلب AJAX، أرجع استجابة JSON للخطأ
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء إنشاء عقد التأجير: ' . $e->getMessage(),
                    'errors' => []
                ], 422);
            }

            return redirect()->back()
                           ->withInput()
                           ->with('error', 'حدث خطأ أثناء إنشاء عقد التأجير: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $contract = RentalContract::with([
            'user.nationality',
            'maid.nationality.translations',
            'maid.religion.translations',
            'info.package',
            'marketer',
            'frozenBy',
            'resumedBy',
            'order',
            'financeInvoice.receiptAccount',
            'extensions',
            'financialAdjustments',
        ])->findOrFail($id);

        $availableReplacementMaids = $this->getAvailableRentalMaidsQuery()
            ->when($contract->maid_id, function ($query) use ($contract) {
                $query->where('id', '!=', $contract->maid_id);
            })
            ->get();

        $activities = $this->buildActivityTimeline(
            $contract->activities()->with('causer')->latest()->limit(20)->get(),
            [
                'maid_id' => 'العاملة',
                'reason' => 'سبب التغيير',
                'status' => 'حالة العقد',
                'marketer_id' => 'المسوق',
                'contract_date' => 'تاريخ العقد',
                'contract_sent_at' => 'بداية مرحلة التوقيع',
                'contract_signed_at' => 'تاريخ توقيع العقد',
                'maid_delivered_at' => 'تاريخ تسليم العاملة',
                'warranty_status' => 'حالة الضمان',
                'warranty_start_date' => 'بداية الضمان',
                'warranty_end_date' => 'نهاية الضمان',
                'freeze_reason' => 'سبب التجميد',
                'frozen_at' => 'تاريخ التجميد',
                'resumed_at' => 'تاريخ الاستئناف',
            ],
            'عقد التأجير'
        );

        $maidChangeHistory = $contract->activities()
            ->with('causer')
            ->where('event', 'maid_changed')
            ->latest()
            ->get()
            ->map(function ($activity) {
                return [
                    'old_maid_name' => data_get($activity, 'properties.old_maid_name')
                        ?? data_get($activity, 'properties.old.maid_name')
                        ?? 'غير محدد',
                    'new_maid_name' => data_get($activity, 'properties.new_maid_name')
                        ?? data_get($activity, 'properties.attributes.maid_name')
                        ?? 'غير محدد',
                    'reason' => data_get($activity, 'properties.attributes.reason') ?: 'لم يكتب سبب',
                    'changed_by' => optional($activity->causer)->name ?? 'النظام',
                    'changed_at' => $activity->created_at,
                ];
            });

        $contractAttachments = $this->collectRentalContractAttachments($contract);

        return view($this->folderPath . '.show', compact(
            'contract',
            'availableReplacementMaids',
            'activities',
            'maidChangeHistory',
            'contractAttachments'
        ));
    }

    public function changeMaid(Request $request, $id)
    {
        $isAjaxRequest = $request->expectsJson() || $request->ajax();

        if (!@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_EDIT)) {
            $message = 'ليس لديك صلاحية لتغيير العاملة في عقد التأجير';

            return $isAjaxRequest
                ? response()->json(['success' => false, 'message' => $message], 403)
                : redirect()->back()->with('error', $message);
        }

        $validated = $request->validate([
            'new_maid_id' => 'required|exists:maids,id',
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $result = DB::transaction(function () use ($id, $validated) {
                $contract = RentalContract::with(['maid', 'order'])->lockForUpdate()->findOrFail($id);

                if (in_array($contract->status, ['completed', 'cancelled'], true)) {
                    throw new \RuntimeException('لا يمكن تغيير العاملة في عقد مكتمل أو ملغي');
                }

                $oldMaid = $contract->maid_id
                    ? Maid::query()->lockForUpdate()->find($contract->maid_id)
                    : null;

                if ($oldMaid && (int) $validated['new_maid_id'] === (int) $oldMaid->id) {
                    throw new \RuntimeException('العاملة المختارة هي العاملة الحالية للعقد');
                }

                $newMaid = $this->getAvailableRentalMaidsQuery()
                    ->whereKey((int) $validated['new_maid_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$newMaid) {
                    throw new \RuntimeException('العاملة المختارة غير متاحة للتأجير أو مرتبطة بحجز حالي');
                }

                activity()->withoutLogs(function () use ($contract, $newMaid) {
                    $contract->update([
                        'maid_id' => $newMaid->id,
                    ]);
                });

                if ($contract->order) {
                    $contract->order->update([
                        'maid_id' => $newMaid->id,
                    ]);
                }

                $newMaid->update([
                    'status' => 'contracted',
                    'available' => false,
                ]);

                if ($oldMaid) {
                    $this->syncMaidBookingState($oldMaid);
                }

                $description = $oldMaid
                    ? 'تم تغيير العاملة من ' . $oldMaid->name . ' إلى ' . $newMaid->name . ' بسبب: ' . $validated['reason']
                    : 'تم تعيين العاملة ' . $newMaid->name . ' على العقد بسبب: ' . $validated['reason'];

                activity()
                    ->causedBy(Auth::guard('admin')->user())
                    ->performedOn($contract)
                    ->event('maid_changed')
                    ->withProperties([
                        'attributes' => [
                            'maid_id' => $newMaid->id,
                            'reason' => $validated['reason'],
                        ],
                        'old' => [
                            'maid_id' => $oldMaid?->id,
                        ],
                        'old_maid_name' => $oldMaid?->name,
                        'new_maid_name' => $newMaid->name,
                    ])
                    ->log($description);

                return [
                    'old_maid_name' => $oldMaid?->name,
                    'new_maid_name' => $newMaid->name,
                ];
            });

            $message = 'تم تغيير العاملة في عقد التأجير بنجاح';

            return $isAjaxRequest
                ? response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => $result,
                ])
                : redirect()->route($this->mainRoute . '.show', $id)->with('success', $message);
        } catch (\RuntimeException $e) {
            return $isAjaxRequest
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 422)
                : redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Error changing maid in rental contract', [
                'contract_id' => $id,
                'new_maid_id' => $validated['new_maid_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            $message = 'حدث خطأ أثناء تغيير العاملة في عقد التأجير';

            return $isAjaxRequest
                ? response()->json(['success' => false, 'message' => $message], 500)
                : redirect()->back()->with('error', $message);
        }
    }

    public function edit($id)
    {
        $contract = RentalContract::with(['user', 'maid', 'info', 'marketer'])->findOrFail($id);

        // الحصول على العاملات المتاحة من نوع تأجير
        $availableRentalMaids = $this->getAvailableRentalMaidsQuery($contract->maid_id)->get();

        $packages = Package::with(['translations', 'nationality.translations'])
            ->visible()
            ->ordered()
            ->get();

        $marketers = Admin::whereNull('deleted_at')->orderBy('name')->get();
        $nationalities = Nationality::with('translations')->get();
        $countries = Country::active()->ordered()->get();

        return view($this->folderPath . '.edit', compact(
            'contract',
            'availableRentalMaids',
            'packages',
            'marketers',
            'nationalities',
            'countries'
        ));
    }

    public function update(StoreRentalContractRequest $request, RentalContractFinanceService $rentalContractFinanceService, $id)
    {
        try {
            DB::beginTransaction();

            $contract = RentalContract::with(['user', 'info'])->findOrFail($id);

            // تحديث بيانات العميل
            $contract->user->update($contract->user->clientTypeAwarePayload([
                'name' => $request->client_name,
                'phone' => $request->client_phone,
                'nationality_id' => $request->client_nationality_id,
                'email' => $request->client_email,
                'birthdate' => $request->client_birthdate,
                'address' => $request->client_address,
                'city' => $request->client_city,
                'family_num' => $request->family_num,
                'children' => $request->children,
                'profession' => $request->profession,
                'monthly_income' => $request->monthly_income,
            ], $request->client_national_num));

            // تحديث بيانات العقد
            $oldMaidId = $contract->maid_id;
            $contract->update([
                'maid_id' => $request->maid_id,
                'marketer_id' => $request->marketer_id,
                'contract_date' => $request->contract_date,
            ]);

            $packageId = (int) ($request->input('package_id') ?: $request->input('selected_package_id'));
            $selectedPackage = Package::find($packageId);
            $contractDurationDays = (int) ($request->contract_duration_days ?: ($selectedPackage?->days_count ?: ($request->contract_duration_months * 30)));
            $contractDurationMonths = (int) ($request->contract_duration_months ?: max(1, (int) ceil($contractDurationDays / 30)));
            $monthlyCost = (float) ($request->monthly_cost ?: $this->resolvePackagePeriodCost($selectedPackage, $contractDurationMonths));

            // حساب قيمة العقد
            $contractValue = (float) ($request->total_contract_value ?: ($selectedPackage?->price_with_tax ?: ($monthlyCost * $contractDurationMonths)));
            $packagePriceSnapshot = $this->buildPackagePriceSnapshot($selectedPackage, $contractValue);
            $paidAmount = 0.0;

            if ($request->payment_status === 'paid') {
                $paidAmount = $contractValue;
            } elseif ($request->payment_status === 'partial') {
                $paidAmount = min((float) $request->paid_amount, $contractValue);
            }

            if ($selectedPackage && (int) ($contract->info?->package_id ?? 0) !== (int) $selectedPackage->id) {
                $packageDurationDays = (int) ($selectedPackage->days_count ?: 0);
                $packageDurationMonths = max(1, (int) ceil(($packageDurationDays ?: 30) / 30));

                $contractDurationDays = $packageDurationDays ?: (int) ($request->contract_duration_days ?: ($packageDurationMonths * 30));
                $contractDurationMonths = $packageDurationMonths;
                $monthlyCost = $this->resolvePackagePeriodCost($selectedPackage, $contractDurationMonths);
                $contractValue = (float) ($selectedPackage->price_with_tax ?: ($monthlyCost * $contractDurationMonths));
                $packagePriceSnapshot = $this->buildPackagePriceSnapshot($selectedPackage, $contractValue);

                if ($request->payment_status === 'paid') {
                    $paidAmount = $contractValue;
                } elseif ($request->payment_status === 'partial') {
                    $paidAmount = min((float) $request->paid_amount, $contractValue);
                } else {
                    $paidAmount = 0.0;
                }
            }

            $remainingAmount = max(0, $contractValue - $paidAmount);
            $paymentReceiptPath = $contract->info?->payment_receipt_path;

            if ($request->hasFile('payment_receipt')) {
                $paymentReceiptPath = $request->file('payment_receipt')
                    ->store('rental-contracts/edit/receipts', 'public');
            }

            // تحديث معلومات العقد
            $contract->info->update([
                'package_id' => $packageId,
                'package_original_price' => $packagePriceSnapshot['package_original_price'],
                'package_discount_amount' => $packagePriceSnapshot['package_discount_amount'],
                'package_price_after_discount' => $packagePriceSnapshot['package_price_after_discount'],
                'package_vat_amount' => $packagePriceSnapshot['package_vat_amount'],
                'package_total_with_tax' => $packagePriceSnapshot['package_total_with_tax'],
                'payment_type' => $request->payment_type,
                'payment_status' => $request->payment_status,
                'payment_receipt_path' => $paymentReceiptPath,
                'payment_confirmed_at' => $paidAmount > 0
                    ? ($contract->info->payment_confirmed_at ?: now())
                    : null,
                'payment_confirmed_by' => $paidAmount > 0
                    ? ($contract->info->payment_confirmed_by ?: auth('admin')->id())
                    : null,
                'working_hours_per_day' => $request->working_hours_per_day ?: 8,
                'hourly_rate' => $request->hourly_rate,
                'monthly_salary' => $request->monthly_salary,
                'sponsorship_transfer_amount' => $request->sponsorship_transfer_amount,
                'salary_after_sponsorship' => $request->salary_after_sponsorship,
                'rental_type' => $selectedPackage?->rental_type ?: $request->rental_type,
                'monthly_cost' => $monthlyCost,
                'contract_duration_months' => $contractDurationMonths,
                'contract_duration_days' => $contractDurationDays,
                'contract_value' => $contractValue,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'ingaz_from' => $request->ingaz_from,
                'ingaz_to' => $request->ingaz_to ?: \Carbon\Carbon::parse($request->ingaz_from)->addDays($contractDurationDays),
                'notes' => $request->notes,
                'additional_terms' => $request->additional_terms,
            ]);

            $contract->loadMissing(['user', 'maid.nationality', 'info', 'financeInvoice']);
            $financeResult = $rentalContractFinanceService->syncPaymentForContract($contract, $selectedPackage);

            // تحديث حالة العاملات
            if ($oldMaidId && $oldMaidId != $request->maid_id) {
                $oldMaid = Maid::find($oldMaidId);

                if ($oldMaid) {
                    $this->syncMaidBookingState($oldMaid);
                }
            }

            if ($request->maid_id) {
                // تحديث العاملة الجديدة
                Maid::where('id', $request->maid_id)->update([
                    'status' => 'contracted',
                    'available' => false,
                ]);
            }

            DB::commit();

            if ($request->ajax()) {
                $receiptBond = $financeResult['receipt_bond'] ?? null;

                return response()->json([
                    'success' => true,
                    'message' => 'تم تحديث عقد التأجير بنجاح',
                    'redirect_url' => route($this->mainRoute . '.show', $contract->id),
                    'receipt_bond_print_url' => $receiptBond
                        ? route('finance.bonds-print.show', ['bonds_print' => $receiptBond->id, 'type' => 'qabd'])
                        : null,
                    'receipt_created' => (bool) ($financeResult['receipt_created'] ?? false),
                ]);
            }

            return redirect()->route($this->mainRoute . '.index')->with('success', 'تم تحديث عقد التأجير بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating rental contract: ' . $e->getMessage());

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء تحديث عقد التأجير: ' . $e->getMessage(),
                ], 422);
            }

            return redirect()->back()
                           ->withInput()
                           ->with('error', 'حدث خطأ أثناء تحديث عقد التأجير: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $contract = RentalContract::with(['maid'])->findOrFail($id);
            $releasedMaidId = $contract->maid_id;

            // حذف العقد (سيحذف معلومات العقد تلقائياً بسبب cascade)
            $contract->delete();

            if ($releasedMaidId) {
                $releasedMaid = Maid::find($releasedMaidId);

                if ($releasedMaid) {
                    $this->syncMaidBookingState($releasedMaid);
                }
            }

            DB::commit();

            return redirect()->route($this->mainRoute . '.index')
                           ->with('success', 'تم حذف عقد التأجير بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting rental contract: ' . $e->getMessage());

            return redirect()->back()
                           ->with('error', 'حدث خطأ أثناء حذف عقد التأجير: ' . $e->getMessage());
        }
    }

    // Helper Methods
    private function generateContractNumber()
    {
        $year = date('Y');
        $lastContract = RentalContract::whereYear('created_at', $year)
                                    ->orderBy('id', 'desc')
                                    ->first();

        $number = $lastContract ? (intval(substr($lastContract->contract_number, -4)) + 1) : 1;

        return 'RC-' . $year . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function completeContract(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $contract = RentalContract::lockForUpdate()->findOrFail($id);

            if (!$contract->canCloseOrExtend()) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إنهاء العقد قبل تفعيله'
                ], 400);
            }

            $contract->update([
                'status' => 'completed',
                'warranty_status' => 'active',
                'warranty_start_date' => now(),
                'warranty_end_date' => now()->addMonths(3), // 3 أشهر ضمان افتراضي
            ]);

            $this->returnShelterContractInsideForRental($contract);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إنهاء العقد بنجاح'
            ]);

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنهاء العقد: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getStatusBadge($status, $closureType = null)
    {
        if ($status === 'completed') {
            if ($closureType === 'transferred') {
                return '<span class="badge bg-warning">تم النقل</span>';
            } elseif ($closureType === 'available') {
                return '<span class="badge bg-success">مكتمل - متاحة</span>';
            } else {
                return '<span class="badge bg-info">مكتمل</span>';
            }
        }

        $badges = [
            'new' => '<span class="badge bg-primary">جديد</span>',
            'waiting_contract_send' => '<span class="badge bg-warning">بانتظار التوقيع</span>',
            'contract_sent' => '<span class="badge bg-warning">بانتظار التوقيع</span>',
            'contract_signed' => '<span class="badge bg-success">تم توقيع العقد</span>',
            'maid_delivered' => '<span class="badge bg-primary">تم تسليم العاملة</span>',
            'active' => '<span class="badge bg-success">نشط</span>',
            'cancelled' => '<span class="badge bg-danger">ملغي</span>',
            'suspended' => '<span class="badge bg-warning">معلق</span>',
            'frozen' => '<span class="badge bg-dark">مجمد</span>',
        ];

        return $badges[$status] ?? '<span class="badge bg-secondary">' . $status . '</span>';
    }

    public function statistics()
    {
        try {
            $total = RentalContract::count();
            $active = RentalContract::whereNotIn('status', ['completed', 'cancelled', 'frozen'])->count();
            $completed = RentalContract::where('status', 'completed')->count();
            $available = RentalContract::where('status', 'completed')->where('closure_type', 'available')->count();
            $transferred = RentalContract::where('status', 'completed')->where('closure_type', 'transferred')->count();

            $totalValue = RentalContract::with('info')
                                      ->get()
                                      ->sum(function($contract) {
                                          return $contract->info ? $contract->info->contract_value : 0;
                                      });

            return response()->json([
                'total' => $total,
                'active' => $active,
                'completed' => $completed,
                'available' => $available,
                'transferred' => $transferred,
                'totalValue' => $totalValue
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'total' => 0,
                'active' => 0,
                'completed' => 0,
                'available' => 0,
                'transferred' => 0,
                'totalValue' => 0
            ]);
        }
    }

    public function exportExcel(Request $request)
    {
        try {
            $query = RentalContract::with(['user', 'maid', 'info', 'marketer']);

            // Apply filters
            if ($request->status) {
                $query->where('status', $request->status);
            }
            if ($request->date_from) {
                $query->whereDate('contract_date', '>=', $request->date_from);
            }
            if ($request->date_to) {
                $query->whereDate('contract_date', '<=', $request->date_to);
            }
            if ($request->marketer_id) {
                $query->where('marketer_id', $request->marketer_id);
            }
            if ($this->branchesEnabledFor('rental_contracts') && $request->filled('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }
            if ($request->warranty_status) {
                $query->where('warranty_status', $request->warranty_status);
            }
            if ($request->expiry_filter) {
                $this->applyRentalContractExpiryFilter($query, $request->expiry_filter);
            }

            $contracts = $query->get();

            $filename = 'rental_contracts_' . date('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($contracts) {
                $file = fopen('php://output', 'w');

                // Add BOM for UTF-8
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                // Headers
                fputcsv($file, [
                    'رقم العقد',
                    'اسم العميل',
                    'رقم الجوال',
                    'اسم العاملة',
                    'رقم الجواز',
                    'المسوق',
                    'الحالة',
                    'تاريخ العقد',
                    'مدة العقد (شهور)',
                    'التكلفة الشهرية',
                    'قيمة العقد',
                    'تاريخ الإنجاز من',
                    'تاريخ الإنجاز إلى'
                ]);

                // Data
                foreach ($contracts as $contract) {
                    fputcsv($file, [
                        $contract->contract_number ?? '',
                        $contract->user ? $contract->user->name : '',
                        $contract->user ? $contract->user->phone_code . $contract->user->phone : '',
                        $contract->maid ? $contract->maid->name : 'لم يتم اختيار عاملة',
                        $contract->maid ? $contract->maid->passport_num : '',
                        $contract->marketer ? $contract->marketer->name : '',
                        $this->getStatusText($contract->status),
                        $contract->contract_date ? $contract->contract_date->format('Y-m-d') : '',
                        $contract->info ? $contract->info->contract_duration_months : '',
                        $contract->info ? $contract->info->monthly_cost : '',
                        $contract->info ? $contract->info->contract_value : '',
                        $contract->info && $contract->info->ingaz_from ? $contract->info->ingaz_from->format('Y-m-d') : '',
                        $contract->info && $contract->info->ingaz_to ? $contract->info->ingaz_to->format('Y-m-d') : ''
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Error exporting rental contracts: ' . $e->getMessage());
            return redirect()->back()->with('error', 'حدث خطأ أثناء تصدير البيانات');
        }
    }

    private function branchesEnabledFor(string $table): bool
    {
        return SystemModuleManager::isEnabled('branches')
            && Schema::hasTable($table)
            && Schema::hasColumn($table, 'branch_id');
    }

    private function getStatusText($status)
    {
        $statuses = [
            'new' => 'جديد',
            'waiting_contract_send' => 'بانتظار التوقيع',
            'contract_sent' => 'بانتظار التوقيع',
            'contract_signed' => 'تم توقيع العقد',
            'maid_delivered' => 'تم تسليم العاملة',
            'active' => 'نشط',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            'suspended' => 'معلق',
            'frozen' => 'مجمد',
        ];

        return $statuses[$status] ?? $status;
    }

    private function getWarrantyStatusText($status)
    {
        $statuses = [
            'not_applicable' => 'غير قابل للتطبيق',
            'active' => 'نشط',
            'expired' => 'منتهي',
        ];

        return $statuses[$status] ?? $status;
    }

    // Add method to pass status texts to view
    public function getStatusTexts()
    {
        return [
            'new' => 'جديد',
            'active' => 'نشط',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            'suspended' => 'معلق',
            'frozen' => 'مجمد',
        ];
    }

    public function getWarrantyStatusTexts()
    {
        return [
            'not_applicable' => 'غير قابل للتطبيق',
            'active' => 'نشط',
            'expired' => 'منتهي',
        ];
    }

    private function applyRentalContractStatusFilter($query, ?string $status): void
    {
        if (!$status) {
            return;
        }

        if ($status === 'transferred') {
            $query->where('status', 'completed')->where('closure_type', 'transferred');
            return;
        }

        if ($status === 'available') {
            $query->where('status', 'completed')->where('closure_type', 'available');
            return;
        }

        if (in_array($status, ['waiting_contract_send', 'contract_sent'], true)) {
            $query->whereIn('status', ['waiting_contract_send', 'contract_sent']);
            return;
        }

        $query->where('status', $status);
    }

    private function applyRentalContractExpiryFilter($query, ?string $expiryFilter): void
    {
        if (!$expiryFilter) {
            return;
        }

        $query->whereNotIn('status', ['completed', 'cancelled']);

        if ($expiryFilter === 'expired') {
            $query->whereHas('info', function ($infoQuery) {
                $infoQuery->whereNotNull('ingaz_to')
                    ->whereDate('ingaz_to', '<=', Carbon::today());
            });
            return;
        }

        if ($expiryFilter === 'not_expired') {
            $query->whereHas('info', function ($infoQuery) {
                $infoQuery->whereNotNull('ingaz_to')
                    ->whereDate('ingaz_to', '>', Carbon::today());
            });
        }
    }

    private function getAvailableRentalMaidsQuery(?int $currentMaidId = null)
    {
        $rentalJobTypeId = $this->getRentalJobTypeId();

        return Maid::query()
            ->whereHas('jobTypes', function ($query) use ($rentalJobTypeId) {
                $query->where('job_type_id', $rentalJobTypeId);
            })
            ->where('status', '!=', 'backout')
            ->where(function ($query) use ($currentMaidId) {
                $query->where(function ($availableQuery) {
                    $availableQuery->where('available', true)
                        ->whereIn('status', ['available', 'completed']);
                });

                if ($currentMaidId) {
                    $query->orWhere('id', $currentMaidId);
                }
            })
            ->where(function ($query) use ($currentMaidId) {
                $query->whereDoesntHave('rentalContracts', function ($contractQuery) use ($currentMaidId) {
                    $contractQuery->whereIn('status', $this->rentalActiveContractStatuses());

                    if ($currentMaidId) {
                        $contractQuery->where('maid_id', '!=', $currentMaidId);
                    }
                });

                if ($currentMaidId) {
                    $query->orWhere('id', $currentMaidId);
                }
            })
            ->where(function ($query) use ($currentMaidId) {
                $query->whereDoesntHave('orders', function ($orderQuery) {
                    $orderQuery->whereIn('status', $this->rentalBookedOrderStatuses());
                });

                if ($currentMaidId) {
                    $query->orWhere('id', $currentMaidId);
                }
            })
            ->where(function ($query) use ($currentMaidId) {
                $query->whereDoesntHave('contracts', function ($contractQuery) {
                    $contractQuery->whereIn('status', $this->recruitmentBusyContractStatuses());
                });

                if ($currentMaidId) {
                    $query->orWhere('id', $currentMaidId);
                }
            })
            ->with(['nationality.translations', 'religion.translations', 'jobTitles.translations'])
            ->latest();
    }

    private function rentalActiveContractStatuses(): array
    {
        return ['new', 'waiting_contract_send', 'contract_sent', 'contract_signed', 'maid_delivered', 'active', 'transferred', 'suspended', 'frozen'];
    }

    private function rentalBookedOrderStatuses(): array
    {
        return [
            OrderEnum::New->value,
            OrderEnum::UnderProcess->value,
            OrderEnum::Contracted->value,
            OrderEnum::ContractedWithoutContract->value,
        ];
    }

    private function recruitmentBusyContractStatuses(): array
    {
        // A shelter contract means the maid is in accommodation and can still be rented.
        return [
            ContractStatusEnum::New->value,
            ContractStatusEnum::AccreditationContracts->value,
            ContractStatusEnum::Msaned->value,
            ContractStatusEnum::Tafwed->value,
            ContractStatusEnum::Taffez->value,
            ContractStatusEnum::Tazker->value,
            ContractStatusEnum::Arrival->value,
            ContractStatusEnum::Sari->value,
            ContractStatusEnum::PendingMaidSelection->value,
            ContractStatusEnum::Trial->value,
            ContractStatusEnum::InProgress->value,
            ContractStatusEnum::ClientContracted->value,
            ContractStatusEnum::DelegateSelected->value,
            ContractStatusEnum::UnderWarranty->value,
        ];
    }

    private function syncMaidBookingState(Maid $maid): void
    {
        if ($maid->status === 'backout') {
            $maid->update([
                'available' => false,
            ]);

            return;
        }

        $hasActiveRentalContracts = RentalContract::query()
            ->where('maid_id', $maid->id)
            ->whereIn('status', $this->rentalActiveContractStatuses())
            ->exists();

        if ($hasActiveRentalContracts) {
            $maid->update([
                'status' => 'contracted',
                'available' => false,
            ]);

            return;
        }

        $hasBusyRecruitmentContracts = Contract::query()
            ->where('maid_id', $maid->id)
            ->whereIn('status', $this->recruitmentBusyContractStatuses())
            ->exists();

        $hasBusyOrders = Order::query()
            ->where('maid_id', $maid->id)
            ->whereIn('status', $this->rentalBookedOrderStatuses())
            ->exists();

        $maid->update([
            'status' => ($hasBusyRecruitmentContracts || $hasBusyOrders) ? 'reserved' : 'available',
            'available' => !($hasBusyRecruitmentContracts || $hasBusyOrders),
        ]);
    }

    private function getRentalJobTypeId(): int
    {
        $jobType = JobType::with('translations')->get()->first(function ($type) {
            $title = $this->normalizeArabicText($type->title ?? '');

            foreach (['ايجار', 'إيجار', 'تأجير', 'rent', 'rental'] as $keyword) {
                if (Str::contains($title, $this->normalizeArabicText($keyword))) {
                    return true;
                }
            }

            return false;
        });

        return (int) ($jobType?->id ?? 2);
    }

    private function normalizeArabicText(?string $value): string
    {
        $value = trim((string) $value);
        $value = Str::lower($value);

        return str_replace(['أ', 'إ', 'آ', 'ى', 'ة'], ['ا', 'ا', 'ا', 'ي', 'ه'], $value);
    }

    /**
     * Get existing clients for AJAX requests
     */
    public function getExistingClients(Request $request)
    {
        $search = $request->get('search', '');

        $clients = User::select('id', 'name', 'phone', 'email', 'nationality_id', 'national_num', 'client_type', 'tax_number', 'license_number', 'birthdate', 'address', 'city', 'family_num', 'children', 'profession', 'monthly_income')
                      ->whereNotNull('name')
                      ->where('name', '!=', '')
                      ->where('name', 'not like', '+966%')
                      ->where('name', 'not like', '10%')
                      ->where('name', 'not like', '05%')
                      ->where('name', 'not like', '2%')
                      ->whereNotNull('phone');

        if ($search) {
            $clients->where(function($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('national_num', 'like', "%{$search}%")
                      ->orWhere('tax_number', 'like', "%{$search}%")
                      ->orWhere('license_number', 'like', "%{$search}%");
            });
        }

        $clients = $clients->orderBy('name')
                          ->limit(50)
                          ->get();

        return response()->json([
            'success' => true,
            'clients' => $clients->map(function ($client) {
                $client->document_number = $client->searchableDocumentNumber();
                $client->document_label = $client->documentNumberLabel();
                $client->national_num = $client->document_number;

                return $client;
            })->values(),
            'count' => $clients->count()
        ]);
    }

    protected function buildRentalClosureSummary(RentalContract $contract, ?Carbon $closureDate = null): array
    {
        $contract->loadMissing(['info', 'financialAdjustments', 'financeInvoice']);

        $info = $contract->info;
        $monthlyCost = (float) ($info->monthly_cost ?? 0);
        $durationMonths = (int) ($info->contract_duration_months ?? 0);
        $durationDays = (int) ($info->contract_duration_days ?? ($durationMonths * 30));
        $contractValue = (float) ($info->contract_value ?? ($monthlyCost * $durationMonths));
        $paidAmount = is_numeric($info?->paid_amount) ? (float) $info->paid_amount : 0.0;
        $closureDate = ($closureDate ?: Carbon::now())->copy()->startOfDay();

        if ($paidAmount <= 0 && ($info?->payment_status ?? null) === 'paid') {
            $paidAmount = $contractValue;
        }

        if ($contract->financeInvoice && is_numeric($contract->financeInvoice->paid_amount)) {
            $paidAmount = max($paidAmount, (float) $contract->financeInvoice->paid_amount);
        }

        $remainingAmount = is_numeric($info?->remaining_amount)
            ? (float) $info->remaining_amount
            : max(0.0, $contractValue - $paidAmount);

        $startDate = $info?->ingaz_from
            ? Carbon::parse($info->ingaz_from)->startOfDay()
            : ($contract->contract_date ? Carbon::parse($contract->contract_date)->startOfDay() : null);
        $endDate = $info?->ingaz_to ? Carbon::parse($info->ingaz_to)->startOfDay() : null;

        $remainingDaysValue = null;
        if ($endDate) {
            $remainingDaysValue = max(0, $closureDate->diffInDays($endDate, false));
        }

        if ($durationDays <= 0 && $startDate && $endDate) {
            $durationDays = max(1, $startDate->diffInDays($endDate, false));
        }

        $usedDays = null;
        if ($durationDays > 0) {
            if (!$startDate || $closureDate->lessThan($startDate)) {
                $usedDays = 0;
            } elseif ($endDate && $closureDate->greaterThanOrEqualTo($endDate)) {
                $usedDays = $durationDays;
            } else {
                $usedDays = min($durationDays, max(0, $startDate->diffInDays($closureDate, false) + 1));
            }
        }

        $dailyRate = $durationDays > 0 ? round($contractValue / $durationDays, 2) : 0.0;
        $usedCost = $usedDays !== null ? round($dailyRate * $usedDays, 2) : 0.0;
        $calculatedRefundValue = max(0.0, round($paidAmount - $usedCost, 2));

        $storedRefundValue = (float) $contract->financialAdjustments()
            ->where('context', 'closure')
            ->where('type', 'expense')
            ->whereNull('settled_at')
            ->sum('amount');
        $refundValue = $storedRefundValue > 0 ? $storedRefundValue : $calculatedRefundValue;

        return [
            'monthly_cost' => $monthlyCost,
            'duration_months' => $durationMonths,
            'duration_days' => $durationDays,
            'duration_label' => $this->formatRentalDurationText($durationDays, $durationMonths),
            'closure_date' => $closureDate->format('Y-m-d'),
            'contract_value' => $contractValue,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'used_days' => $usedDays,
            'remaining_days_value' => $remainingDaysValue,
            'daily_rate' => $dailyRate,
            'used_cost' => $usedCost,
            'calculated_refund_value' => $calculatedRefundValue,
            'refund_value' => $refundValue,
            'payment_status' => $info?->payment_status,
            'closure_client_iban' => $info?->closure_client_iban,
            'closure_client_iban_attachment_url' => $info?->closure_client_iban_attachment_url,
            'closure_return_reason' => $info?->closure_return_reason,
        ];
    }

    private function formatRentalDurationText(int $durationDays, int $durationMonths = 0): string
    {
        if ($durationDays > 0 && (!$durationMonths || $durationDays !== ($durationMonths * 30))) {
            return $this->formatArabicDaysDuration($durationDays);
        }

        if ($durationMonths > 0) {
            return $durationMonths . ' شهر';
        }

        return $durationDays > 0 ? $this->formatArabicDaysDuration($durationDays) : '-';
    }

    private function formatArabicDaysDuration(int $days): string
    {
        if ($days === 7) {
            return 'أسبوع';
        }

        if ($days === 14) {
            return 'أسبوعين';
        }

        if ($days > 0 && $days % 7 === 0) {
            return ($days / 7) . ' أسابيع';
        }

        if ($days === 1) {
            return 'يوم واحد';
        }

        if ($days === 2) {
            return 'يومين';
        }

        return $days . ' يوم';
    }

    private function closureDateFromRequest(Request $request): Carbon
    {
        return $request->filled('closure_date')
            ? Carbon::parse($request->input('closure_date'))->startOfDay()
            : Carbon::now()->startOfDay();
    }

    private function assertClosureDateIsValidForContract(RentalContract $contract, Carbon $closureDate): void
    {
        $startDate = $contract->info?->ingaz_from
            ? Carbon::parse($contract->info->ingaz_from)->startOfDay()
            : ($contract->contract_date ? Carbon::parse($contract->contract_date)->startOfDay() : null);

        if ($startDate && $closureDate->lessThan($startDate)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'closure_date' => 'تاريخ الإنهاء لا يمكن أن يكون قبل تاريخ بداية العقد.',
            ]);
        }
    }

    /**
     * Get contract details for closing modal
     */
    public function getContractDetails(Request $request, $id)
    {
        try {
            $request->validate([
                'closure_date' => 'nullable|date',
            ]);

            $contract = RentalContract::with(['user', 'maid', 'info', 'financialAdjustments', 'financeInvoice'])->findOrFail($id);
            $closureDate = $this->closureDateFromRequest($request);
            $summary = $this->buildRentalClosureSummary($contract, $closureDate);
            $remainingDays = (int) ($summary['remaining_days_value'] ?? 0);
            $attachments = collect($this->collectRentalContractAttachments($contract))
                ->map(fn ($attachment) => [
                    'label' => $attachment['label'],
                    'url' => $attachment['url'],
                ])
                ->values()
                ->all();

            $contractData = [
                'id' => $contract->id,
                'client_name' => $contract->user->name ?? 'غير محدد',
                'maid_name' => $contract->maid->name ?? 'غير محدد',
                'status_text' => $this->getStatusText($contract->status),
                'start_date' => $contract->info?->ingaz_from ? Carbon::parse($contract->info->ingaz_from)->format('Y-m-d') : null,
                'end_date' => $contract->info?->ingaz_to ? Carbon::parse($contract->info->ingaz_to)->format('Y-m-d') : null,
                'remaining_days' => $remainingDays . ' يوم',
                'amount' => $summary['contract_value'],
                'monthly_cost' => $summary['monthly_cost'],
                'duration_months' => $summary['duration_months'],
                'duration_days' => $summary['duration_days'],
                'duration_label' => $summary['duration_label'],
                'closure_date' => $summary['closure_date'],
                'contract_value' => $summary['contract_value'],
                'paid_amount' => $summary['paid_amount'],
                'remaining_amount' => $summary['remaining_amount'],
                'used_days' => $summary['used_days'],
                'remaining_days_value' => $summary['remaining_days_value'],
                'daily_rate' => $summary['daily_rate'],
                'used_cost' => $summary['used_cost'],
                'calculated_refund_value' => $summary['calculated_refund_value'],
                'refund_value' => $summary['refund_value'],
                'payment_status' => $summary['payment_status'],
                'closure_client_iban' => $summary['closure_client_iban'],
                'closure_client_iban_attachment_url' => $summary['closure_client_iban_attachment_url'],
                'closure_return_reason' => $summary['closure_return_reason'],
                'attachments' => $attachments,
            ];

            return response()->json([
                'success' => true,
                'contract' => $contractData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات العقد: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Close contract and make maid available
     */
    public function closeAndMakeAvailable($id, Request $request)
    {
        $validated = $request->validate([
            'closure_date' => 'nullable|date',
            'adjustment_type' => 'nullable|in:refund',
            'adjustment_amount' => 'required|numeric|min:0',
            'clear_remaining_amount' => 'nullable|numeric|min:0',
            'closure_return_reason' => 'nullable|string|max:1000',
            'close_payment_status' => 'nullable|in:paid,partial,pending,later,bank_pending',
            'close_payment_receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'close_client_iban' => 'nullable|string|max:34',
            'close_client_iban_attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        try {
            DB::beginTransaction();

            $contract = RentalContract::with(['maid', 'info', 'financialAdjustments', 'financeInvoice'])->lockForUpdate()->findOrFail($id);

            if (!$contract->info) {
                throw new \RuntimeException('بيانات عقد الإيجار غير مكتملة');
            }

            if (!$contract->canCloseOrExtend()) {
                throw new \RuntimeException('لا يمكن إنهاء العقد قبل تفعيله');
            }

            $closureDate = $this->closureDateFromRequest($request);
            $this->assertClosureDateIsValidForContract($contract, $closureDate);

            $summary = $this->buildRentalClosureSummary($contract, $closureDate);
            $adjustmentAmount = (float) ($validated['adjustment_amount'] ?? 0);
            $clearRemainingAmount = (float) ($validated['clear_remaining_amount'] ?? 0);
            $currentRemainingAmount = (float) ($summary['remaining_amount'] ?? 0);
            $newRemainingAmount = max(0, $currentRemainingAmount - $clearRemainingAmount);
            $closureReturnReason = trim((string) ($validated['closure_return_reason'] ?? ''));

            $infoUpdates = [
                'remaining_amount' => $newRemainingAmount,
                'closure_return_reason' => $closureReturnReason !== '' ? $closureReturnReason : null,
            ];

            if (!empty($validated['close_payment_status'])) {
                $infoUpdates['payment_status'] = $validated['close_payment_status'];

                if ($validated['close_payment_status'] === 'paid') {
                    $infoUpdates['paid_amount'] = (float) ($summary['contract_value'] ?? 0);
                    $infoUpdates['remaining_amount'] = 0;
                }
            }

            if (!empty($validated['close_client_iban'])) {
                $infoUpdates['closure_client_iban'] = $validated['close_client_iban'];
            }

            if ($request->hasFile('close_payment_receipt')) {
                $infoUpdates['payment_receipt_path'] = $request->file('close_payment_receipt')
                    ->store('rental-contracts/closures/receipts', 'public');
            }

            if ($request->hasFile('close_client_iban_attachment')) {
                $infoUpdates['closure_client_iban_attachment_path'] = $request->file('close_client_iban_attachment')
                    ->store('rental-contracts/closures/iban', 'public');
            }

            if ($contract->info) {
                $contract->info->update($infoUpdates);
            }

            $contract->status = 'completed';
            $contract->closure_type = 'available';
            $contract->closed_at = $closureDate;
            $contract->save();

            if ($adjustmentAmount > 0) {
                $contract->financialAdjustments()->create([
                    'maid_id' => $contract->maid_id,
                    'type' => 'expense',
                    'label' => 'استرجاع عند إغلاق العقد',
                    'amount' => $adjustmentAmount,
                    'notes' => 'استرجاع مسجل أثناء إنهاء عقد الإيجار. القيمة المقترحة حسب الأيام: ' . number_format((float) ($summary['calculated_refund_value'] ?? 0), 2) . ' ريال'
                        . ($closureReturnReason !== '' ? "\nسبب الإرجاع: " . $closureReturnReason : ''),
                    'context' => 'closure',
                    'created_by' => auth('admin')->id(),
                ]);
            }

            // Make maid available
            if ($contract->maid) {
                $contract->maid->status = 'available';
                $contract->maid->available = 1; // Set available to true
                $contract->maid->available_at = $closureDate->toDateString(); // Set availability date
                $contract->maid->save();
            }

            $this->returnShelterContractInsideForRental($contract);

            // Log the action
            \Log::info('Rent contract closed and maid made available', [
                'contract_id' => $contract->id,
                'maid_id' => $contract->maid_id,
                'closed_by' => auth()->id(),
                'closure_date' => $closureDate->format('Y-m-d'),
                'adjustment_amount' => $adjustmentAmount
            ]);

            DB::commit();

            $newContract = (object) ['id' => null];
            $message = 'تم إغلاق عقد الإيجار وتوثيق نقل الكفالة بنجاح.';

            return response()->json([
                'success' => true,
                'message' => 'تم إغلاق العقد وإرجاع العاملة متاحة بنجاح'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();

            throw $e;
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error closing rent contract', [
                'contract_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إغلاق العقد: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadAttachments(Request $request, $id)
    {
        $contract = RentalContract::with(['info', 'maid', 'extensions', 'financialAdjustments'])->findOrFail($id);
        $attachments = $this->collectRentalContractAttachments($contract);
        $selectedKeys = array_filter((array) $request->input('attachments', []));

        if (!empty($selectedKeys)) {
            $attachments = array_values(array_filter($attachments, function ($attachment) use ($selectedKeys) {
                return in_array($attachment['key'], $selectedKeys, true);
            }));
        } elseif ($request->boolean('selected_only')) {
            $attachments = [];
        }

        if (empty($attachments)) {
            return redirect()->back()->with('error', 'لا توجد مرفقات صالحة للتحميل في هذا العقد.');
        }

        $zip = new \ZipArchive();
        $zipFile = tempnam(sys_get_temp_dir(), 'rent-contract-attachments-');

        if ($zip->open($zipFile, \ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', 'تعذر تجهيز ملف المرفقات.');
        }

        foreach ($attachments as $attachment) {
            $absolutePath = Storage::disk($attachment['disk'])->path($attachment['path']);

            if (!is_file($absolutePath)) {
                continue;
            }

            $zip->addFile($absolutePath, $attachment['filename']);
        }

        $zip->close();

        if (filesize($zipFile) <= 0) {
            @unlink($zipFile);
            return redirect()->back()->with('error', 'لا توجد مرفقات صالحة للتحميل في هذا العقد.');
        }

        $contractNumber = $contract->contract_number ?: $contract->id;

        return response()
            ->download($zipFile, 'rent-contract-' . $contractNumber . '-attachments.zip')
            ->deleteFileAfterSend(true);
    }

    public function downloadAttachment($id, string $attachment)
    {
        $contract = RentalContract::with(['info', 'maid', 'extensions', 'financialAdjustments'])->findOrFail($id);
        $attachmentData = $this->findRentalContractAttachment($contract, $attachment);

        if (!$attachmentData) {
            abort(404);
        }

        return Storage::disk($attachmentData['disk'])->download($attachmentData['path'], $attachmentData['filename']);
    }

    public function viewAttachment($id, string $attachment)
    {
        $contract = RentalContract::with(['info', 'maid', 'extensions', 'financialAdjustments'])->findOrFail($id);
        $attachmentData = $this->findRentalContractAttachment($contract, $attachment);

        if (!$attachmentData) {
            abort(404);
        }

        $filename = str_replace(['"', "\r", "\n"], '', $attachmentData['filename']);
        $mimeType = Storage::disk($attachmentData['disk'])->mimeType($attachmentData['path']) ?: 'application/octet-stream';

        return response()->file(Storage::disk($attachmentData['disk'])->path($attachmentData['path']), [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private function findRentalContractAttachment(RentalContract $contract, string $attachmentKey): ?array
    {
        foreach ($this->collectRentalContractAttachments($contract) as $attachment) {
            if ($attachment['key'] === $attachmentKey) {
                return $attachment;
            }
        }

        return null;
    }

    private function collectRentalContractAttachments(RentalContract $contract): array
    {
        $contract->loadMissing(['info', 'maid', 'extensions', 'financialAdjustments']);

        $attachments = [];
        $addAttachment = function (?string $path, string $label, string $keyPrefix) use (&$attachments, $contract) {
            if (!$path || !Storage::disk('public')->exists($path)) {
                return;
            }

            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $safeLabel = Str::slug($label, '-');
            if ($safeLabel === '') {
                $safeLabel = 'attachment';
            }

            $attachments[] = [
                'key' => $keyPrefix . '-' . count($attachments),
                'label' => $label,
                'path' => $path,
                'disk' => 'public',
                'url' => route('RentContract.attachments.view', [$contract->id, $keyPrefix . '-' . count($attachments)]),
                'download_url' => route('RentContract.attachments.download.single', [$contract->id, $keyPrefix . '-' . count($attachments)]),
                'filename' => (count($attachments) + 1) . '-' . $safeLabel . ($extension ? '.' . $extension : ''),
            ];
        };

        $addAttachment($contract->info?->payment_receipt_path, 'إيصال دفع العقد', 'payment-receipt');
        $addAttachment($contract->info?->sponsorship_transfer_payment_receipt_path, 'إيصال دفع نقل الكفالة', 'transfer-payment-receipt');
        $addAttachment($contract->info?->closure_client_iban_attachment_path, 'مرفق آيبان العميل', 'closure-iban');
        $addAttachment($contract->maid?->cv_file, 'سيرة العاملة', 'maid-cv');
        $addAttachment($contract->maid?->cv_preview, 'معاينة سيرة العاملة', 'maid-cv-preview');
        $addAttachment($contract->maid?->image, 'صورة العاملة', 'maid-image');

        foreach ((array) ($contract->info?->sponsorship_transfer_attachments ?? []) as $index => $transferAttachment) {
            $path = is_array($transferAttachment) ? ($transferAttachment['path'] ?? null) : null;
            $name = is_array($transferAttachment) ? ($transferAttachment['name'] ?? null) : null;
            $addAttachment($path, 'مرفق نقل الكفالة' . ($name ? ' - ' . $name : ' رقم ' . ($index + 1)), 'transfer-attachment-' . $index);
        }

        foreach ($contract->extensions as $extension) {
            $addAttachment($extension->payment_receipt_path, 'إيصال تمديد رقم ' . $extension->id, 'extension-' . $extension->id);
        }

        foreach ($contract->financialAdjustments as $adjustment) {
            $addAttachment($adjustment->receipt_path, 'مرفق إجراء مالي رقم ' . $adjustment->id, 'adjustment-' . $adjustment->id);
        }

        return $attachments;
    }

    /**
     * Close contract and transfer sponsorship
     */
    public function transferSponsorship($id, Request $request)
    {
        $request->validate([
            'closure_date' => 'nullable|date',
            'transfer_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0|lte:total_amount',
            'remaining_amount' => 'required|numeric|min:0',
            'payment_type' => 'required|in:cash,bank_transfer,mada,tabby,tamara,pay_later,other',
            'payment_status' => 'required|in:paid,partial,pending,later,bank_pending',
            'payment_receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:4096',
            'notes' => 'nullable|string',
            'transfer_finance_items' => 'nullable|array',
            'transfer_finance_items.*.type' => 'nullable|in:income,expense',
            'transfer_finance_items.*.label' => 'nullable|string|max:150',
            'transfer_finance_items.*.amount' => 'nullable|numeric|min:0',
            'transfer_finance_items_present' => 'nullable|boolean',
        ], [
            'paid_amount.lte' => 'المبلغ المدفوع لا يمكن أن يكون أكبر من المبلغ الكامل.',
            'payment_type.required' => 'يجب اختيار طريقة الدفع.',
            'payment_type.in' => 'طريقة الدفع غير صحيحة.',
            'payment_status.required' => 'يجب اختيار حالة الدفع.',
            'payment_status.in' => 'حالة الدفع غير صحيحة.',
            'payment_receipt.file' => 'صيغة إيصال الدفع غير صحيحة.',
            'payment_receipt.mimes' => 'صيغة إيصال الدفع يجب أن تكون pdf أو jpg أو jpeg أو png.',
            'payment_receipt.max' => 'حجم إيصال الدفع يجب ألا يتجاوز 4 ميجابايت.',
            'attachments.*.mimes' => 'صيغة المرفقات يجب أن تكون pdf أو jpg أو jpeg أو png أو doc أو docx.',
            'attachments.*.max' => 'حجم كل مرفق يجب ألا يتجاوز 4 ميجابايت.',
        ]);

        if (in_array($request->payment_status, ['paid', 'partial'], true) && !$request->hasFile('payment_receipt')) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'payment_receipt' => 'يجب إرفاق إيصال الدفع عند تحديد حالة الدفع "تم الدفع" أو "مدفوع جزئياً".',
            ]);
        }

        try {
            DB::beginTransaction();

            $oldContract = RentalContract::with('info')->findOrFail($id);

            if (!$oldContract->canCloseOrExtend()) {
                throw new \RuntimeException('لا يمكن إنهاء العقد أو نقل الكفالة قبل تفعيله');
            }

            $closureDate = $this->closureDateFromRequest($request);
            $this->assertClosureDateIsValidForContract($oldContract, $closureDate);

            $transferTotalAmount = (float) $request->total_amount;
            $transferAmount = $request->filled('transfer_amount')
                ? (float) $request->transfer_amount
                : $transferTotalAmount;
            $transferPaidAmount = min((float) $request->paid_amount, $transferTotalAmount);
            $transferRemainingAmount = max(0.0, $transferTotalAmount - $transferPaidAmount);

            // Close the old contract and set closure type to transferred
            $oldContract->status = 'completed';
            $oldContract->closure_type = 'transferred';
            $oldContract->closed_at = $closureDate;
            $oldContract->save();

            // Use the existing client from the rental contract
            $transferDetails = "تفاصيل نقل الكفالة بتاريخ " . $closureDate->format('Y-m-d') . ":\n" .
                "- مبلغ نقل الكفالة: " . number_format($transferAmount, 2) . " ريال\n" .
                "- المبلغ الإجمالي: " . number_format($request->total_amount, 2) . " ريال\n" .
                "- المبلغ المدفوع: " . number_format($transferPaidAmount, 2) . " ريال\n" .
                "- المبلغ المتبقي: " . number_format($transferRemainingAmount, 2) . " ريال";

            if ($request->filled('notes')) { $transferDetails .= "\n- ملاحظات إضافية: " . $request->notes; if (false) {
                throw new \Exception('لا يمكن العثور على بيانات العميل في عقد الإيجار');
            }

            }

            if (false) { // Create new contract for sponsorship transfer
            $newContract = Contract::create([
                'user_id' => $newClient->id,
                'maid_id' => $oldContract->maid_id,
                'status' => ContractStatusEnum::Msaned->value,
                'type' => 'recruitment', // Change to recruitment contract
                'contract_date' => now()
                // Note: previous_contract_id removed due to foreign key constraint between different table types
            ]);

            // Create contract payment record
            if ($request->total_amount > 0) {
                ContractPayment::create([
                    'contract_id' => $newContract->id,
                    'total_amount' => $request->total_amount,
                    'paid_amount' => $request->paid_amount,
                    'remaining_amount' => $request->remaining_amount,
                    'payment_date' => now(),
                    'notes' => 'نقل كفالة من العقد رقم ' . $oldContract->id
                ]);
            }

            }

            $transferIsPaid = $transferRemainingAmount <= 0.0;
            $paymentReceiptPath = $request->hasFile('payment_receipt')
                ? $request->file('payment_receipt')->store('rental-contracts/sponsorship-transfer/receipts', 'public')
                : null;
            $transferAttachments = [];

            foreach ($request->file('attachments', []) as $file) {
                $transferAttachments[] = [
                    'path' => $file->store('rental-contracts/sponsorship-transfer/attachments', 'public'),
                    'name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ];
            }

            $transferPaymentUpdates = [
                'sponsorship_transfer_payment_type' => $request->payment_type,
                'sponsorship_transfer_payment_status' => $request->payment_status,
                'sponsorship_transfer_payment_receipt_path' => $paymentReceiptPath,
                'sponsorship_transfer_attachments' => $transferAttachments ?: null,
                'sponsorship_transfer_payment_confirmed_at' => $transferPaidAmount > 0 ? now() : null,
                'sponsorship_transfer_payment_confirmed_by' => $transferPaidAmount > 0 ? auth('admin')->id() : null,
            ];

            if ($oldContract->info) {
                $existingNotes = $oldContract->info->notes ? $oldContract->info->notes . "\n\n" : '';
                $existingTransferAttachments = (array) ($oldContract->info->sponsorship_transfer_attachments ?? []);
                if (!empty($existingTransferAttachments) || !empty($transferAttachments)) {
                    $transferPaymentUpdates['sponsorship_transfer_attachments'] = array_values(array_merge(
                        $existingTransferAttachments,
                        $transferAttachments
                    ));
                }

                $oldContract->info->update([
                    'sponsorship_transfer_amount' => $transferAmount,
                    'sponsorship_transfer_total_amount' => $transferTotalAmount,
                    'sponsorship_transfer_paid_amount' => $transferPaidAmount,
                    'sponsorship_transfer_remaining_amount' => $transferRemainingAmount,
                    'notes' => $existingNotes . $transferDetails,
                ] + $transferPaymentUpdates);
            } else {
                RentalContractInfo::create([
                    'rental_contract_id' => $oldContract->id,
                    'sponsorship_transfer_amount' => $transferAmount,
                    'sponsorship_transfer_total_amount' => $transferTotalAmount,
                    'sponsorship_transfer_paid_amount' => $transferPaidAmount,
                    'sponsorship_transfer_remaining_amount' => $transferRemainingAmount,
                    'notes' => $transferDetails,
                ] + $transferPaymentUpdates);
            }

            if ($transferIsPaid) {
                $transferItems = $this->resolveTransferFinancialAdjustments($request, $transferAmount);
                $this->upsertTransferFinancialAdjustments($oldContract, $transferAmount, $transferItems);
            } else {
                $oldContract->financialAdjustments()
                    ->where('context', 'transfer')
                    ->delete();
            }

            // Update maid status
            if ($oldContract->maid) {
                $oldContract->maid->status = 'contracted';
                $oldContract->maid->available = false;
                $oldContract->maid->save();
            }

            // Log the action
            \Log::info('Sponsorship transferred', [
                'old_contract_id' => $oldContract->id,
                'transfer_amount' => $transferAmount,
                'total_amount' => $request->total_amount,
                'paid_amount' => $transferPaidAmount,
                'remaining_amount' => $transferRemainingAmount,
                'closure_date' => $closureDate->format('Y-m-d'),
                'transferred_by' => auth()->id()
            ]);

            DB::commit();

            $message = 'تم إغلاق عقد الإيجار وتوثيق نقل الكفالة بنجاح.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'new_contract_id' => null,
                'status' => 'transferred'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();

            throw $e;
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error transferring sponsorship', [
                'contract_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء نقل الكفالة: ' . $e->getMessage()
            ], 500);
        }
    }

    private function resolveTransferFinancialAdjustments(Request $request, float $fallbackAmount): array
    {
        $items = [];

        foreach ((array) $request->input('transfer_finance_items', []) as $item) {
            $label = trim((string) ($item['label'] ?? ''));
            $amount = round((float) ($item['amount'] ?? 0), 2);

            if ($label === '' || $amount <= 0) {
                continue;
            }

            $items[] = [
                'type' => ($item['type'] ?? 'income') === 'expense' ? 'expense' : 'income',
                'label' => Str::limit($label, 150, ''),
                'amount' => $amount,
            ];
        }

        if (empty($items) && $fallbackAmount > 0) {
            $items[] = [
                'type' => 'income',
                'label' => 'نقل خدمات',
                'amount' => round($fallbackAmount, 2),
            ];
        }

        return $items;
    }

    private function upsertTransferFinancialAdjustments(RentalContract $contract, float $transferAmount, array $items): void
    {
        $contract->financialAdjustments()
            ->where('context', 'transfer')
            ->delete();

        $adminId = auth('admin')->id() ?: auth()->id();

        foreach ($items as $item) {
            $contract->financialAdjustments()->create([
                'maid_id' => $contract->maid_id,
                'type' => $item['type'],
                'label' => $item['label'],
                'amount' => $item['amount'],
                'notes' => 'نقل كفالة من عقد إيجار بقيمة ' . number_format($transferAmount, 2) . ' ريال',
                'context' => 'transfer',
                'created_by' => $adminId,
                'settled_at' => now(),
                'settled_by' => $adminId,
            ]);
        }
    }

    // طرق workflow الجديدة مثل نقل الكفالة
    public function sendContract(Request $request, $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'تم إلغاء مرحلة إرسال العقد، وأصبحت عقود الإيجار تبدأ مباشرة بانتظار التوقيع'
        ], 410);
    }

    public function signContract(Request $request, $id)
    {
        try {
            $contract = RentalContract::findOrFail($id);

            if (!$contract->canSignContract()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن توقيع العقد في هذه المرحلة'
                ], 400);
            }

            $contract->update([
                'status' => 'contract_signed',
                'contract_sent_at' => $contract->contract_sent_at ?: now(),
                'contract_sent_by' => $contract->contract_sent_by ?: auth('admin')->id(),
                'contract_signed_at' => now(),
                'contract_signed_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تأكيد توقيع العقد بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deliverMaid(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $contract = RentalContract::with('maid')->lockForUpdate()->findOrFail($id);

            if (!$contract->canDeliverMaid()) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تسليم العاملة في هذه المرحلة'
                ], 400);
            }

            $contract->update([
                'status' => 'active',
                'maid_delivered_at' => now(),
                'maid_delivered_by' => auth('admin')->id() ?: auth()->id(),
                'trial_started_at' => now(),
                'trial_started_by' => auth('admin')->id() ?: auth()->id(),
            ]);

            $this->markShelterContractOutsideForRental($contract);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تأكيد تسليم العاملة وتفعيل العقد بنجاح'
            ]);

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }

    public function activateContract(Request $request, $id)
    {
        try {
            $simpleContract = RentalContract::findOrFail($id);

            if (!$simpleContract->canActivateContract()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تفعيل العقد في هذه المرحلة'
                ], 400);
            }

            $simpleContract->update([
                'status' => 'active',
                'trial_started_at' => now(),
                'trial_started_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تفعيل العقد بنجاح'
            ]);

            $contract = RentalContract::with(['maid', 'info', 'financialAdjustments'])->findOrFail($id);

            if (!$contract->info) {
                throw new \RuntimeException('بيانات عقد الإيجار غير مكتملة');
            }

            $summary = $this->buildRentalClosureSummary($contract);
            $adjustmentAmount = (float) ($validated['adjustment_amount'] ?? 0);
            $clearRemainingAmount = (float) ($validated['clear_remaining_amount'] ?? 0);
            $currentRemainingAmount = (float) ($summary['remaining_amount'] ?? 0);
            $newRemainingAmount = max(0, $currentRemainingAmount - $clearRemainingAmount);

            $infoUpdates = [
                'remaining_amount' => $newRemainingAmount,
            ];

            if (!empty($validated['close_payment_status'])) {
                $infoUpdates['payment_status'] = $validated['close_payment_status'];

                if ($validated['close_payment_status'] === 'paid') {
                    $infoUpdates['paid_amount'] = (float) ($summary['contract_value'] ?? 0);
                    $infoUpdates['remaining_amount'] = 0;
                }
            }

            if (!empty($validated['close_client_iban'])) {
                $infoUpdates['closure_client_iban'] = $validated['close_client_iban'];
            }

            if ($request->hasFile('close_payment_receipt')) {
                $infoUpdates['payment_receipt_path'] = $request->file('close_payment_receipt')
                    ->store('rental-contracts/closures/receipts', 'public');
            }

            if ($request->hasFile('close_client_iban_attachment')) {
                $infoUpdates['closure_client_iban_attachment_path'] = $request->file('close_client_iban_attachment')
                    ->store('rental-contracts/closures/iban', 'public');
            }

            $contract->info->update($infoUpdates);

            if (!$contract->canActivateContract()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تفعيل العقد في هذه المرحلة'
                ], 400);
            }

            $contract->update([
                'status' => 'active',
                'trial_started_at' => now(), // نعيد تسمية هذا الحقل لاحقاً إلى activated_at
                'trial_started_by' => auth()->id() // نعيد تسمية هذا الحقل لاحقاً إلى activated_by
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تفعيل العقد بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }

    public function freeze(Request $request, $id)
    {
        $validated = $request->validate([
            'freeze_reason' => 'required|string|max:1000',
        ], [
            'freeze_reason.required' => 'يرجى كتابة سبب تجميد العقد',
            'freeze_reason.string' => 'سبب التجميد يجب أن يكون نصاً',
            'freeze_reason.max' => 'سبب التجميد يجب ألا يتجاوز 1000 حرف',
        ]);

        try {
            DB::transaction(function () use ($id, $validated) {
                $contract = RentalContract::lockForUpdate()->findOrFail($id);

                if (!$contract->canFreeze()) {
                    throw new \RuntimeException('لا يمكن تجميد العقد في حالته الحالية');
                }

                $previousStatus = $contract->status === 'frozen'
                    ? ($contract->status_before_freeze ?: 'active')
                    : $contract->status;

                $contract->update([
                    'status' => 'frozen',
                    'status_before_freeze' => $previousStatus,
                    'freeze_reason' => $validated['freeze_reason'],
                    'frozen_at' => now(),
                    'frozen_by' => auth('admin')->id() ?: auth()->id(),
                    'resumed_at' => null,
                    'resumed_by' => null,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'تم تجميد عقد الإيجار بنجاح',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Error freezing rental contract', [
                'contract_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تجميد عقد الإيجار: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function resume(Request $request, $id)
    {
        try {
            DB::transaction(function () use ($id) {
                $contract = RentalContract::lockForUpdate()->findOrFail($id);

                if (!$contract->canResume()) {
                    throw new \RuntimeException('لا يمكن استئناف عقد غير مجمد');
                }

                $resumeStatus = $contract->status_before_freeze ?: 'active';

                if (in_array($resumeStatus, ['completed', 'cancelled', 'frozen'], true)) {
                    $resumeStatus = 'active';
                }

                $contract->update([
                    'status' => $resumeStatus,
                    'status_before_freeze' => null,
                    'resumed_at' => now(),
                    'resumed_by' => auth('admin')->id() ?: auth()->id(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'تم استئناف عقد الإيجار بنجاح',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Error resuming rental contract', [
                'contract_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء استئناف عقد الإيجار: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function extend(Request $request, $id)
    {
        $validated = $request->validate([
            'extension_months' => 'nullable|integer|min:0',
            'extension_days' => 'nullable|integer|min:0',
            'extension_payment_status' => 'required|in:paid,partial,pending,later,bank_pending',
            'extension_payment_receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'extension_notes' => 'nullable|string|max:1000',
        ], [
            'extension_months.integer' => 'مدة التمديد يجب أن تكون رقماً صحيحاً',
            'extension_months.min' => 'مدة التمديد بالشهور لا يمكن أن تكون أقل من صفر',
            'extension_days.integer' => 'أيام التمديد يجب أن تكون رقماً صحيحاً',
            'extension_days.min' => 'أيام التمديد لا يمكن أن تكون أقل من صفر',
            'extension_payment_status.required' => 'يرجى تحديد حالة دفع التمديد',
            'extension_payment_status.in' => 'حالة دفع التمديد غير صحيحة',
            'extension_payment_receipt.file' => 'صيغة إيصال الدفع غير صحيحة',
            'extension_payment_receipt.mimes' => 'صيغة إيصال الدفع يجب أن تكون pdf أو jpg أو jpeg أو png',
            'extension_payment_receipt.max' => 'حجم إيصال الدفع يجب ألا يتجاوز 4 ميجابايت',
        ]);

        if (in_array($validated['extension_payment_status'], ['paid', 'partial'], true) && !$request->hasFile('extension_payment_receipt')) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'extension_payment_receipt' => ['يجب إرفاق إيصال الدفع عند تحديد حالة الدفع "تم الدفع" أو "مدفوع جزئياً".'],
            ]);
        }

        $extensionMonths = (int) ($validated['extension_months'] ?? 0);
        $extraExtensionDays = (int) ($validated['extension_days'] ?? 0);
        $extensionDays = ($extensionMonths * 30) + $extraExtensionDays;

        if ($extensionDays <= 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'extension_months' => ['يرجى تحديد مدة التمديد بالشهور أو الأيام.'],
            ]);
        }

        try {
            DB::beginTransaction();

            $contract = RentalContract::with('info')->findOrFail($id);

            if (!$contract->info) {
                throw new \RuntimeException('بيانات العقد غير مكتملة');
            }

            if (!$contract->canCloseOrExtend()) {
                throw new \RuntimeException('لا يمكن تمديد العقد قبل تفعيله');
            }

            $baseEndDate = $contract->info->ingaz_to;
            if (!$baseEndDate) {
                $baseStartDate = $contract->info->ingaz_from ?: $contract->contract_date ?: now();
                $baseDays = (int) ($contract->info->contract_duration_days ?? 0);
                if ($baseDays <= 0) {
                    $baseDays = (int) ($contract->info->contract_duration_months ?? 0) * 30;
                }
                $baseEndDate = Carbon::parse($baseStartDate)->addDays($baseDays);
            }

            $baseEndDate = Carbon::parse($baseEndDate);
            $extensionStart = $baseEndDate->copy()->addDay();
            $extensionEnd = $baseEndDate->copy()->addDays($extensionDays);

            $extensionReceiptPath = null;
            if ($request->hasFile('extension_payment_receipt')) {
                $extensionReceiptPath = $request->file('extension_payment_receipt')
                    ->store('rental-contracts/extensions/receipts', 'public');
            }

            RentalContractExtension::create([
                'rental_contract_id' => $contract->id,
                'extension_months' => $extensionMonths,
                'extension_days' => $extensionDays,
                'start_date' => $extensionStart,
                'end_date' => $extensionEnd,
                'payment_status' => $validated['extension_payment_status'],
                'payment_receipt_path' => $extensionReceiptPath,
                'notes' => $validated['extension_notes'] ?? null,
                'created_by' => auth('admin')->id(),
            ]);

            $monthlyCost = (float) ($contract->info->monthly_cost ?? 0);
            $currentContractValue = (float) ($contract->info->contract_value ?? 0);
            $currentDurationDays = (int) ($contract->info->contract_duration_days ?? 0);
            if ($currentDurationDays <= 0) {
                $currentDurationDays = max(1, (int) ($contract->info->contract_duration_months ?? 0) * 30);
            }
            $dailyRate = $currentDurationDays > 0 && $currentContractValue > 0
                ? $currentContractValue / $currentDurationDays
                : $monthlyCost / 30;
            $extensionValue = round($dailyRate * $extensionDays, 2);

            $contract->info->update([
                'contract_duration_months' => $extensionMonths,
                'contract_duration_days' => $extensionDays,
                'contract_value' => $extensionValue,
                'ingaz_to' => $extensionEnd,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تمديد عقد التأجير بنجاح',
                'redirect_url' => route('RentContract.show', $contract->id),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error extending rental contract', [
                'contract_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تمديد عقد التأجير: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function printContract($id)
    {
        try {
            $contract = RentalContract::with([
                'user.nationality',
                'maid.nationality.translations',
                'maid.religion.translations',
                'info.package',
                'marketer'
            ])->findOrFail($id);

            // Get settings for contract printing
            $defaultContractSettings = [
                'license_number' => '4001772',
                'license_date' => '2022-10-13',
                'commercial_register' => '1010601958',
                'commercial_date' => '1441-03-03',
                'city' => 'الرياض',
            ];
            $contractSettings = [];
            $settingKeys = [
                'company_name', 'license_number', 'license_date', 'commercial_register',
                'commercial_date', 'city', 'address', 'po_box', 'postal_code',
                'phone', 'mobile', 'fax', 'email', 'default_trial_period',
                'default_working_hours', 'daily_deduction', 'notice_period', 'logo'
            ];

            foreach ($settingKeys as $key) {
                $setting = \App\Models\Setting::where('key', 'rent_contract_' . $key)->first();
                $value = $setting ? trim((string) $setting->value) : '';
                $contractSettings[$key] = $value !== '' ? $value : ($defaultContractSettings[$key] ?? null);
            }

            $contractSettings['company_name'] = documentSetting(
                'document_company_name',
                $contractSettings['company_name'] ?? documentSetting('website_name', config('app.name'))
            );
            $contractSettings['address'] = $contractSettings['address'] ?: documentSetting('location', 'الرياض');
            $contractSettings['phone'] = $contractSettings['phone'] ?: documentSetting('phone', '');
            $contractSettings['email'] = $contractSettings['email'] ?: documentSetting('email', '');
            $contractSettings['logo'] = $contractSettings['logo'] ?: null;

            // Use the same structured terms managed from the admin terms screen.
            $termsByPage = RentalContractTerm::active()
                ->orderBy('page_number')
                ->ordered()
                ->get()
                ->groupBy('page_number')
                ->sortKeys();
            $termsPage1 = $termsByPage->get(1, collect());
            $termsPage2 = $termsByPage->get(2, collect());
            $termsPage3 = $termsByPage->get(3, collect());

            // التوقيع متاح عندما يكون العقد بانتظار التوقيع أو بعده
            $allowSignature = in_array($contract->status, ['waiting_contract_send', 'contract_sent', 'contract_signed', 'maid_delivered', 'active']);

            return view('admin.print.rental-contract-dynamic', compact('contract', 'contractSettings', 'termsByPage', 'termsPage1', 'termsPage2', 'termsPage3', 'allowSignature'));

        } catch (\Exception $e) {
            \Log::error('Error printing rental contract: ' . $e->getMessage());
            return redirect()->back()->with('error', 'حدث خطأ أثناء طباعة العقد');
        }
    }

    public function saveSignature(Request $request, $id)
    {
        try {
            $contract = RentalContract::findOrFail($id);

            $signatureData = $request->input('signature');

            if (empty($signatureData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على بيانات التوقيع'
                ], 400);
            }

            $signatureData = str_replace('data:image/png;base64,', '', $signatureData);
            $signatureData = str_replace(' ', '+', $signatureData);
            $signatureImage = base64_decode($signatureData);

            if (!$signatureImage) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات التوقيع غير صحيحة'
                ], 400);
            }

            $fileName = 'signature_' . $id . '_' . time() . '.png';
            \Storage::disk('public')->put('signatures/' . $fileName, $signatureImage);

            $updates = [
                'signature' => $fileName,
            ];

            if ($contract->canSignContract()) {
                $updates = array_merge($updates, [
                    'status' => 'contract_signed',
                    'contract_sent_at' => $contract->contract_sent_at ?: now(),
                    'contract_sent_by' => $contract->contract_sent_by ?: (auth('admin')->id() ?: auth()->id()),
                    'contract_signed_at' => now(),
                    'contract_signed_by' => auth('admin')->id() ?: auth()->id(),
                ]);
            }

            $contract->update($updates);

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ التوقيع وتحديث حالة العقد بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ التوقيع: ' . $e->getMessage()
            ], 500);
        }
    }

    private function markShelterContractOutsideForRental(RentalContract $rentalContract): void
    {
        if (!$rentalContract->maid_id) {
            return;
        }

        $shelterContract = Contract::query()
            ->with('info')
            ->where('maid_id', $rentalContract->maid_id)
            ->where('status', ContractStatusEnum::Shelter->value)
            ->where(function ($query) {
                $query->whereDoesntHave('info')
                    ->orWhereHas('info', function ($infoQuery) {
                        $infoQuery->where(function ($outsideQuery) {
                            $outsideQuery->whereNull('is_outside_shelter')
                                ->orWhere('is_outside_shelter', false);
                        });
                    });
            })
            ->latest('id')
            ->lockForUpdate()
            ->first();

        if (!$shelterContract) {
            return;
        }

        $shelterContract->info()->updateOrCreate([], [
            'is_outside_shelter' => true,
            'outside_shelter_at' => $rentalContract->maid_delivered_at ?: now(),
            'outside_shelter_reason' => 'rental_delivery',
            'outside_shelter_reference_type' => RentalContract::class,
            'outside_shelter_reference_id' => $rentalContract->id,
            'outside_shelter_by' => auth('admin')->id() ?: auth()->id(),
        ]);

        $shelterContract->contract_status_logs()->create([
            'status' => ContractStatusEnum::Shelter->value,
            'date' => now(),
            'notes' => 'تم إخراج العاملة من داخل الإيواء بسبب تسليم عقد إيجار رقم ' . $rentalContract->id,
        ]);
    }

    private function returnShelterContractInsideForRental(RentalContract $rentalContract): void
    {
        if (!$rentalContract->maid_id) {
            return;
        }

        $shelterContract = Contract::query()
            ->with('info')
            ->where('maid_id', $rentalContract->maid_id)
            ->where('status', ContractStatusEnum::Shelter->value)
            ->whereHas('info', function ($infoQuery) use ($rentalContract) {
                $infoQuery->where('is_outside_shelter', true)
                    ->where('outside_shelter_reason', 'rental_delivery')
                    ->where('outside_shelter_reference_type', RentalContract::class)
                    ->where('outside_shelter_reference_id', $rentalContract->id);
            })
            ->latest('id')
            ->lockForUpdate()
            ->first();

        if (!$shelterContract || !$shelterContract->info) {
            return;
        }

        $shelterContract->info->update([
            'is_outside_shelter' => false,
        ]);

        $shelterContract->contract_status_logs()->create([
            'status' => ContractStatusEnum::Shelter->value,
            'date' => now(),
            'notes' => 'تمت إعادة العاملة إلى داخل الإيواء بعد إنهاء عقد إيجار رقم ' . $rentalContract->id,
        ]);
    }

    private function resolvePackagePeriodCost(?Package $package, int $contractDurationMonths): float
    {
        if (!$package) {
            return 0.0;
        }

        $total = (float) $package->price_with_tax;

        if (($package->rental_type === 'monthly' || $package->duration_unit === 'month') && $contractDurationMonths > 0) {
            return round($total / $contractDurationMonths, 2);
        }

        return $total;
    }

    private function buildPackagePriceSnapshot(?Package $package, float $totalWithTax): array
    {
        $totalWithTax = round(max(0, $totalWithTax), 2);
        $netPrice = $totalWithTax > 0 ? round($totalWithTax / 1.15, 2) : 0.0;
        $originalPrice = $netPrice;

        if ($package) {
            $packageTotal = round((float) $package->price_with_tax, 2);
            $originalPrice = round((float) $package->original_price, 2);

            if (abs($packageTotal - $totalWithTax) < 0.05) {
                $netPrice = round((float) $package->price_after_discount, 2);
                $totalWithTax = $packageTotal;
            }
        }

        $discountAmount = round(max(0, $originalPrice - $netPrice), 2);
        $vatAmount = round(max(0, $totalWithTax - $netPrice), 2);

        return [
            'package_original_price' => $originalPrice,
            'package_discount_amount' => $discountAmount,
            'package_price_after_discount' => $netPrice,
            'package_vat_amount' => $vatAmount,
            'package_total_with_tax' => $totalWithTax,
        ];
    }



}
