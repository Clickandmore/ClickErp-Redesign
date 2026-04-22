<?php

namespace App\Http\Controllers\ingaz;

use App\Models\RentalContract;
use App\Models\RentalContractInfo;
use App\Models\RentalContractExtension;
use App\Models\Contract;
use App\Models\ContractPayment;
use App\Models\ingaz\Maid;
use App\Models\ingaz\User;
use App\Models\ingaz\Order;
use App\Models\ingaz\Package;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Nationality;
use App\Models\Country;
use App\Models\auth\Admin;
use Illuminate\Http\Request;
use App\Enums\ingaz\OrderEnum;
use App\Enums\ingaz\ContractStatusEnum;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Actions\ingaz\RecruitmentOfficeAction;
use App\Http\Requests\StoreRentalContractRequest;
use Carbon\Carbon;

class RentContractController extends Controller
{
    public $folderPath = "admin.ingaz.RentContract";
    public $mainRoute = "RentContract";

    public function index(Request $request, $status = null)
    {
        if ($request->ajax()) {
            $status = $request->get('status', $status);

            $query = RentalContract::with(['user', 'maid', 'info', 'marketer'])
                ->withCount('extensions');

            // Apply status filter with closure type consideration
            if ($status) {
                if ($status === 'transferred') {
                    // Show only transferred contracts
                    $query->where('status', 'completed')->where('closure_type', 'transferred');
                } elseif ($status === 'available') {
                    // Show only contracts closed and made available
                    $query->where('status', 'completed')->where('closure_type', 'available');
                } else {
                    // Other statuses
                    $query->where('status', $status);
                }
            }

            // Apply other filters
            if ($request->filled('status_filter')) {
                $statusFilter = $request->status_filter;
                if ($statusFilter === 'transferred') {
                    $query->where('status', 'completed')->where('closure_type', 'transferred');
                } elseif ($statusFilter === 'available') {
                    $query->where('status', 'completed')->where('closure_type', 'available');
                } else {
                    $query->where('status', $statusFilter);
                }
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
                        return '<div class="small">
                                    <div><strong>المدة:</strong> ' . ($row->info->contract_duration_months ?? 'غير محدد') . ' شهر</div>
                                    <div><strong>التكلفة الشهرية:</strong> ' . number_format($row->info->monthly_cost ?? 0, 2) . ' ريال</div>
                                    <div><strong>قيمة العقد:</strong> ' . number_format($row->info->contract_value ?? 0, 2) . ' ريال</div>
                                </div>';
                    }
                    return 'غير محدد';
                })
                ->addColumn('marketer', function ($row) {
                    return $row->marketer ? $row->marketer->name : 'غير محدد';
                })
                ->addColumn('remaining_days', function ($row) {
                    // Only show remaining days for active contracts
                    if ($row->status !== 'completed') {
                        $remainingDays = $row->calculateRemainingDays();
                        if ($remainingDays !== null) {
                            if ($remainingDays > 0) {
                                return '<span class="badge bg-success">' . $remainingDays . ' يوم متبقي</span>';
                            } else {
                                return '<span class="badge bg-danger">منتهي</span>';
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
                                    <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" >
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

                    // إجراءات workflow الجديدة
                    if ($row->canSendContract()) {
                        $actions .= '<li><button class="dropdown-item text-info send-contract" data-id="' . $row->id . '">
                                        <i class="fas fa-paper-plane"></i> تأكيد إرسال العقد
                                    </button></li>';
                    }

                    if ($row->canSignContract()) {
                        $actions .= '<li><button class="dropdown-item text-success sign-contract" data-id="' . $row->id . '">
                                        <i class="fas fa-signature"></i> تأكيد توقيع العقد
                                    </button></li>';
                    }

                    if ($row->canDeliverMaid()) {
                        $actions .= '<li><button class="dropdown-item text-primary deliver-maid" data-id="' . $row->id . '">
                                        <i class="fas fa-handshake"></i> تأكيد تسليم العاملة
                                    </button></li>';
                    }

                    if ($row->canActivateContract()) {
                        $actions .= '<li><button class="dropdown-item text-success activate-contract" data-id="' . $row->id . '">
                                        <i class="fas fa-power-off"></i> تفعيل العقد
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
                    if ($row->status !== 'completed' && $row->status !== 'cancelled') {
                        $contractLabel = $row->contract_number ?: $row->id;
                        $currentEndDate = optional($row->info?->ingaz_to)->format('Y-m-d') ?: '';
                        $monthlyCost = (float) ($row->info->monthly_cost ?? 0);

                        $actions .= '<li><button class="dropdown-item text-info open-extend-modal"
                                        data-extend-url="' . route('RentContract.extend', $row->id) . '"
                                        data-contract-number="' . e($contractLabel) . '"
                                        data-ingaz-to="' . e($currentEndDate) . '"
                                        data-monthly-cost="' . $monthlyCost . '">
                                        <i class="fas fa-calendar-plus"></i> تمديد العقد
                                    </button></li>';
                    }

                    if ($row->status !== 'completed' && $row->status !== 'cancelled') {
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

        // الحصول على الطلبات المنتهية من نوع تأجير (job_type_id = 2)
        $endedOrders = Order::where('job_type_id', $rentalJobTypeId)
                           ->whereIn('status', [OrderEnum::Done->value])
                           ->with(['user', 'maid'])
                           ->latest()
                           ->get();

        // إذا تم تمرير order_id، احصل على بيانات الطلب
        $selectedOrder = null;
        if ($request->has('order_id')) {
            $selectedOrder = Order::with(['user', 'maid.nationality.translations', 'maid.religion.translations', 'details.package'])
                                ->where('job_type_id', $rentalJobTypeId)
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
            ->orderBy('days_count')
            ->orderBy('price_with_tax')
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

    public function store(StoreRentalContractRequest $request)
    {
        try {
            DB::beginTransaction();

            $profession = $request->filled('profession')
                ? $request->profession
                : $request->new_client_job;

            // إنشاء أو تحديث بيانات العميل
            if ($request->new_existing_client_id) {
                // استخدام عميل موجود وتحديث بياناته
                $user = User::find($request->new_existing_client_id);
                $user->update([
                    'name' => $request->new_client_name,
                    'phone' => $request->new_client_phone,
                    'nationality_id' => $request->client_nationality_id,
                    'email' => $request->new_client_email,
                    'national_num' => $request->new_client_national_num,
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
                ]);
            } else {
                // إنشاء عميل جديد
                $user = User::create([
                    'name' => $request->new_client_name,
                    'phone' => $request->new_client_phone,
                    'phone_code' => '+966',
                    'nationality_id' => $request->client_nationality_id,
                    'email' => $request->new_client_email,
                    'national_num' => $request->new_client_national_num,
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
                ]);
            }

            // إنشاء عقد التأجير
            $rentalContract = RentalContract::create([
                'user_id' => $user->id,
            'maid_id' => $request->maid_id,
                'marketer_id' => $request->marketer_id,
                'order_id' => $request->order_id,
                'status' => 'waiting_contract_send',
                'contract_date' => $request->contract_date,
                'contract_number' => $this->generateContractNumber(),
            ]);

            $selectedPackage = Package::find($request->package_id);
            $contractDurationDays = (int) ($request->contract_duration_days ?: ($selectedPackage?->days_count ?: ($request->contract_duration_months * 30)));
            $contractDurationMonths = (int) ($request->contract_duration_months ?: max(1, (int) ceil($contractDurationDays / 30)));
            $monthlyCost = (float) ($request->monthly_cost ?: ($selectedPackage && $contractDurationMonths > 0
                ? round((float) $selectedPackage->price_with_tax / $contractDurationMonths, 2)
                : 0));
            $ingazTo = $request->ingaz_to ?: \Carbon\Carbon::parse($request->ingaz_from)->addDays($contractDurationDays);

            $contractValue = (float) ($request->total_contract_value ?: ($selectedPackage?->price_with_tax ?: ($monthlyCost * $contractDurationMonths)));
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
                'package_id' => $request->package_id,
                'payment_type' => $request->payment_type,
                'payment_status' => $request->payment_status,
                'payment_receipt_path' => $paymentReceiptPath,
                'payment_confirmed_at' => $paidAmount > 0 ? now() : null,
                'payment_confirmed_by' => $paidAmount > 0 ? auth('admin')->id() : null,
                'working_hours_per_day' => $request->working_hours ?: 8,
                'hourly_rate' => $request->hourly_rate,
                'monthly_salary' => $request->monthly_salary,
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

            // تحديث حالة العاملة
            if ($request->maid_id) {
                Maid::where('id', $request->maid_id)->update([
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
            'info',
            'marketer',
            'order'
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
                'contract_sent_at' => 'تاريخ إرسال العقد',
                'contract_signed_at' => 'تاريخ توقيع العقد',
                'maid_delivered_at' => 'تاريخ تسليم العاملة',
                'warranty_status' => 'حالة الضمان',
                'warranty_start_date' => 'بداية الضمان',
                'warranty_end_date' => 'نهاية الضمان',
            ],
            'عقد التأجير'
        );

        return view($this->folderPath . '.show', compact('contract', 'availableReplacementMaids', 'activities'));
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

        $marketers = Admin::whereNull('deleted_at')->orderBy('name')->get();
        $nationalities = Nationality::with('translations')->get();

        return view($this->folderPath . '.edit', compact(
            'contract',
            'availableRentalMaids',
            'marketers',
            'nationalities'
        ));
    }

    public function update(StoreRentalContractRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $contract = RentalContract::with(['user', 'info'])->findOrFail($id);

            // تحديث بيانات العميل
            $contract->user->update([
                'name' => $request->client_name,
                'phone' => $request->client_phone,
                'nationality_id' => $request->client_nationality_id,
                'email' => $request->client_email,
                'national_num' => $request->client_national_num,
                'birthdate' => $request->client_birthdate,
                'address' => $request->client_address,
                'city' => $request->client_city,
                'family_num' => $request->family_num,
                'children' => $request->children,
                'profession' => $request->profession,
                'monthly_income' => $request->monthly_income,
            ]);

            // تحديث بيانات العقد
            $oldMaidId = $contract->maid_id;
            $contract->update([
                'maid_id' => $request->maid_id,
                'marketer_id' => $request->marketer_id,
                'contract_date' => $request->contract_date,
            ]);

            // حساب تاريخ انتهاء الإنجاز
            $ingazTo = \Carbon\Carbon::parse($request->ingaz_from)
                                  ->addDays($request->contract_duration_days);

            // حساب قيمة العقد
            $contractValue = $request->monthly_cost * $request->contract_duration_months;

            // تحديث معلومات العقد
            $contract->info->update([
                'working_hours_per_day' => $request->working_hours_per_day,
                'hourly_rate' => $request->hourly_rate,
                'monthly_salary' => $request->monthly_salary,
                'sponsorship_transfer_amount' => $request->sponsorship_transfer_amount,
                'salary_after_sponsorship' => $request->salary_after_sponsorship,
                'monthly_cost' => $request->monthly_cost,
                'contract_duration_months' => $request->contract_duration_months,
                'contract_duration_days' => $request->contract_duration_days,
                'contract_value' => $contractValue,
                'ingaz_from' => $request->ingaz_from,
                'ingaz_to' => $ingazTo,
                'notes' => $request->notes,
                'additional_terms' => $request->additional_terms,
            ]);

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

            return redirect()->route($this->mainRoute . '.index')->with('success', 'تم تحديث عقد التأجير بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating rental contract: ' . $e->getMessage());

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
            $contract = RentalContract::findOrFail($id);

            $contract->update([
                'status' => 'completed',
                'warranty_status' => 'active',
                'warranty_start_date' => now(),
                'warranty_end_date' => now()->addMonths(3), // 3 أشهر ضمان افتراضي
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إنهاء العقد بنجاح'
            ]);

        } catch (\Exception $e) {
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
            'waiting_contract_send' => '<span class="badge bg-warning">انتظار إرسال العقد</span>',
            'contract_sent' => '<span class="badge bg-info">تم إرسال العقد</span>',
            'contract_signed' => '<span class="badge bg-success">تم توقيع العقد</span>',
            'maid_delivered' => '<span class="badge bg-primary">تم تسليم العاملة</span>',
            'active' => '<span class="badge bg-success">نشط</span>',
            'cancelled' => '<span class="badge bg-danger">ملغي</span>',
            'suspended' => '<span class="badge bg-warning">معلق</span>',
        ];

        return $badges[$status] ?? '<span class="badge bg-secondary">' . $status . '</span>';
    }

    public function statistics()
    {
        try {
            $total = RentalContract::count();
            $active = RentalContract::whereNotIn('status', ['completed', 'cancelled'])->count();
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
            if ($request->warranty_status) {
                $query->where('warranty_status', $request->warranty_status);
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

    private function getStatusText($status)
    {
        $statuses = [
            'new' => 'جديد',
            'active' => 'نشط',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            'suspended' => 'معلق',
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

    private function getAvailableRentalMaidsQuery(?int $currentMaidId = null)
    {
        $rentalJobTypeId = $this->getRentalJobTypeId();

        return Maid::query()
            ->Shown()
            ->whereHas('jobTypes', function ($query) use ($rentalJobTypeId) {
                $query->where('job_type_id', $rentalJobTypeId);
            })
            ->where('status', '!=', 'backout')
            ->where(function ($query) use ($currentMaidId) {
                $query->where(function ($availableQuery) {
                    $availableQuery->where('available', true)
                        ->where('status', 'available');
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
        return ['new', 'waiting_contract_send', 'contract_sent', 'contract_signed', 'maid_delivered', 'active', 'transferred', 'suspended'];
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
        return [
            ContractStatusEnum::New->value,
            ContractStatusEnum::AccreditationContracts->value,
            ContractStatusEnum::Msaned->value,
            ContractStatusEnum::Tafwed->value,
            ContractStatusEnum::Taffez->value,
            ContractStatusEnum::Tazker->value,
            ContractStatusEnum::Arrival->value,
            ContractStatusEnum::Sari->value,
            ContractStatusEnum::Shelter->value,
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

        $clients = User::select('id', 'name', 'phone', 'email', 'nationality_id', 'national_num', 'birthdate', 'address', 'city', 'family_num', 'children', 'profession', 'monthly_income')
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
                      ->orWhere('national_num', 'like', "%{$search}%");
            });
        }

        $clients = $clients->orderBy('name')
                          ->limit(50)
                          ->get();

        return response()->json([
            'success' => true,
            'clients' => $clients,
            'count' => $clients->count()
        ]);
    }

    protected function buildRentalClosureSummary(RentalContract $contract): array
    {
        $contract->loadMissing(['info', 'financialAdjustments']);

        $info = $contract->info;
        $monthlyCost = (float) ($info->monthly_cost ?? 0);
        $durationMonths = (int) ($info->contract_duration_months ?? 0);
        $durationDays = (int) ($info->contract_duration_days ?? ($durationMonths * 30));
        $contractValue = (float) ($info->contract_value ?? ($monthlyCost * $durationMonths));
        $paidAmount = is_numeric($info?->paid_amount) ? (float) $info->paid_amount : 0.0;

        if ($paidAmount <= 0 && ($info?->payment_status ?? null) === 'paid') {
            $paidAmount = $contractValue;
        }

        $remainingAmount = is_numeric($info?->remaining_amount)
            ? (float) $info->remaining_amount
            : max(0.0, $contractValue - $paidAmount);

        $remainingDaysValue = null;
        if ($info?->ingaz_to) {
            $remainingDaysValue = max(0, Carbon::now()->startOfDay()->diffInDays(Carbon::parse($info->ingaz_to)->startOfDay(), false));
        }

        $usedDays = $durationDays > 0
            ? max(0, $durationDays - (int) ($remainingDaysValue ?? 0))
            : null;

        $refundValue = (float) $contract->financialAdjustments()
            ->where('context', 'closure')
            ->where('type', 'expense')
            ->whereNull('settled_at')
            ->sum('amount');

        return [
            'monthly_cost' => $monthlyCost,
            'duration_months' => $durationMonths,
            'duration_days' => $durationDays,
            'contract_value' => $contractValue,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'used_days' => $usedDays,
            'remaining_days_value' => $remainingDaysValue,
            'refund_value' => $refundValue,
            'payment_status' => $info?->payment_status,
            'closure_client_iban' => $info?->closure_client_iban,
            'closure_client_iban_attachment_url' => $info?->closure_client_iban_attachment_url,
        ];
    }

    /**
     * Get contract details for closing modal
     */
    public function getContractDetails($id)
    {
        try {
            $contract = RentalContract::with(['user', 'maid', 'info', 'financialAdjustments'])->findOrFail($id);
            $summary = $this->buildRentalClosureSummary($contract);
            $remainingDays = (int) ($summary['remaining_days_value'] ?? 0);

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
                'contract_value' => $summary['contract_value'],
                'paid_amount' => $summary['paid_amount'],
                'remaining_amount' => $summary['remaining_amount'],
                'used_days' => $summary['used_days'],
                'remaining_days_value' => $summary['remaining_days_value'],
                'refund_value' => $summary['refund_value'],
                'payment_status' => $summary['payment_status'],
                'closure_client_iban' => $summary['closure_client_iban'],
                'closure_client_iban_attachment_url' => $summary['closure_client_iban_attachment_url']
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
            'adjustment_type' => 'nullable|in:refund',
            'adjustment_amount' => 'required|numeric|min:0',
            'clear_remaining_amount' => 'nullable|numeric|min:0',
            'close_payment_status' => 'nullable|in:paid,partial,pending,later,bank_pending',
            'close_payment_receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'close_client_iban' => 'nullable|string|max:34',
            'close_client_iban_attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        try {
            DB::beginTransaction();

            $contract = RentalContract::findOrFail($id);
            $validated = [];

            if (false && !$contract->info) {
                throw new \RuntimeException('بيانات عقد الإيجار غير مكتملة');
            }

            $summary = $this->buildRentalClosureSummary($contract);
            $adjustmentAmount = (float) ($validated['adjustment_amount'] ?? 0);
            $clearRemainingAmount = (float) ($validated['clear_remaining_amount'] ?? 0);
            $currentRemainingAmount = (float) ($summary['remaining_amount'] ?? 0);
            $newRemainingAmount = max(0, $currentRemainingAmount - $clearRemainingAmount);

            if ($adjustmentAmount > 0 && empty($validated['close_client_iban']) && !$request->hasFile('close_client_iban_attachment')) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'close_client_iban' => ['عند وجود مبلغ استرجاع يجب إدخال آيبان العميل أو إرفاق المرفق البنكي.'],
                ]);
            }

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

            if ($contract->info) {
                $contract->info->update($infoUpdates);
            }

            $contract->status = 'completed';
            $contract->closure_type = 'available';
            $contract->closed_at = now();
            $contract->save();

            if ($adjustmentAmount > 0) {
                $contract->financialAdjustments()->create([
                    'maid_id' => $contract->maid_id,
                    'type' => 'expense',
                    'label' => 'استرجاع عند إغلاق العقد',
                    'amount' => $adjustmentAmount,
                    'notes' => 'استرجاع مسجل أثناء إنهاء عقد الإيجار',
                    'context' => 'closure',
                    'created_by' => auth('admin')->id(),
                ]);
            }

            // Make maid available
            if ($contract->maid) {
                $contract->maid->status = 'available';
                $contract->maid->available = 1; // Set available to true
                $contract->maid->available_at = now(); // Set availability timestamp
                $contract->maid->save();
            }

            // Log the action
            \Log::info('Rent contract closed and maid made available', [
                'contract_id' => $contract->id,
                'maid_id' => $contract->maid_id,
                'closed_by' => auth()->id(),
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

    /**
     * Close contract and transfer sponsorship
     */
    public function transferSponsorship($id, Request $request)
    {
        $request->validate([
            'transfer_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'remaining_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'transfer_finance_items' => 'nullable|array',
            'transfer_finance_items.*.type' => 'nullable|in:income,expense',
            'transfer_finance_items.*.label' => 'nullable|string|max:150',
            'transfer_finance_items.*.amount' => 'nullable|numeric|min:0',
            'transfer_finance_items_present' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $oldContract = RentalContract::with('info')->findOrFail($id);

            // Close the old contract and set closure type to transferred
            $oldContract->status = 'completed';
            $oldContract->closure_type = 'transferred';
            $oldContract->closed_at = now();
            $oldContract->save();

            // Use the existing client from the rental contract
            $transferDetails = "تفاصيل نقل الكفالة بتاريخ " . now()->format('Y-m-d H:i') . ":\n" .
                "- مبلغ نقل الكفالة: " . number_format($request->transfer_amount, 2) . " ريال\n" .
                "- المبلغ الإجمالي: " . number_format($request->total_amount, 2) . " ريال\n" .
                "- المبلغ المدفوع: " . number_format($request->paid_amount, 2) . " ريال\n" .
                "- المبلغ المتبقي: " . number_format($request->remaining_amount, 2) . " ريال";

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

            $transferTotalAmount = (float) $request->total_amount;
            $transferPaidAmount = (float) $request->paid_amount;
            $transferRemainingAmount = max(0.0, (float) $request->remaining_amount);
            $transferIsPaid = $transferRemainingAmount <= 0.0;

            if ($oldContract->info) {
                $existingNotes = $oldContract->info->notes ? $oldContract->info->notes . "\n\n" : '';
                $oldContract->info->update([
                    'sponsorship_transfer_amount' => $request->transfer_amount,
                    'sponsorship_transfer_total_amount' => $transferTotalAmount,
                    'sponsorship_transfer_paid_amount' => $transferPaidAmount,
                    'sponsorship_transfer_remaining_amount' => $transferRemainingAmount,
                    'notes' => $existingNotes . $transferDetails,
                ]);
            } else {
                RentalContractInfo::create([
                    'rental_contract_id' => $oldContract->id,
                    'sponsorship_transfer_amount' => $request->transfer_amount,
                    'sponsorship_transfer_total_amount' => $transferTotalAmount,
                    'sponsorship_transfer_paid_amount' => $transferPaidAmount,
                    'sponsorship_transfer_remaining_amount' => $transferRemainingAmount,
                    'notes' => $transferDetails,
                ]);
            }

            if ($transferIsPaid) {
                $transferItems = $this->resolveTransferFinancialAdjustments($request);
                $this->upsertTransferFinancialAdjustments($oldContract, (float) $request->transfer_amount, $transferItems);
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
                'transfer_amount' => $request->transfer_amount,
                'total_amount' => $request->total_amount,
                'paid_amount' => $request->paid_amount,
                'remaining_amount' => $request->remaining_amount,
                'transferred_by' => auth()->id()
            ]);

            DB::commit();

            $newContract = (object) ['id' => null];
            $message = 'تم إغلاق عقد الإيجار وتوثيق نقل الكفالة بنجاح.';

            return response()->json([
                'success' => true,
                'message' => 'تم إغلاق عقد الإيجار ونقل الكفالة للعميل بنجاح. العقد الجديد رقم: ' . $newContract->id,
                'new_contract_id' => $newContract->id,
                'message' => $message,
                'new_contract_id' => null,
                'status' => 'transferred'
            ]);

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

    // طرق workflow الجديدة مثل نقل الكفالة
    public function sendContract(Request $request, $id)
    {
        try {
            $contract = RentalContract::findOrFail($id);

            if (!$contract->canSendContract()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إرسال العقد في هذه المرحلة'
                ], 400);
            }

            $contract->update([
                'status' => 'contract_sent',
                'contract_sent_at' => now(),
                'contract_sent_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تأكيد إرسال العقد بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
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
            $contract = RentalContract::findOrFail($id);

            if (!$contract->canDeliverMaid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تسليم العاملة في هذه المرحلة'
                ], 400);
            }

            $contract->update([
                'status' => 'maid_delivered',
                'maid_delivered_at' => now(),
                'maid_delivered_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تأكيد تسليم العاملة بنجاح'
            ]);

        } catch (\Exception $e) {
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

            if ($adjustmentAmount > 0 && empty($validated['close_client_iban']) && !$request->hasFile('close_client_iban_attachment')) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'close_client_iban' => ['عند وجود مبلغ استرجاع يجب إدخال آيبان العميل أو إرفاق المرفق البنكي.'],
                ]);
            }

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

    public function extend(Request $request, $id)
    {
        $validated = $request->validate([
            'extension_months' => 'required|integer|min:1',
            'extension_payment_status' => 'required|in:paid,partial,pending,later,bank_pending',
            'extension_payment_receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'extension_notes' => 'nullable|string|max:1000',
        ], [
            'extension_months.required' => 'يرجى تحديد مدة التمديد',
            'extension_months.integer' => 'مدة التمديد يجب أن تكون رقماً صحيحاً',
            'extension_months.min' => 'مدة التمديد يجب أن تكون شهراً واحداً على الأقل',
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

        try {
            DB::beginTransaction();

            $contract = RentalContract::with('info')->findOrFail($id);

            if (!$contract->info) {
                throw new \RuntimeException('بيانات العقد غير مكتملة');
            }

            $extensionMonths = (int) $validated['extension_months'];
            $extensionDays = $extensionMonths * 30;

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

            $updatedMonths = (int) ($contract->info->contract_duration_months ?? 0) + $extensionMonths;
            $updatedDays = (int) ($contract->info->contract_duration_days ?? 0) + $extensionDays;
            $monthlyCost = (float) ($contract->info->monthly_cost ?? 0);

            $contract->info->update([
                'contract_duration_months' => $updatedMonths,
                'contract_duration_days' => $updatedDays,
                'contract_value' => $monthlyCost * $updatedMonths,
                'ingaz_to' => $extensionEnd,
            ]);

            DB::commit();

            $newContract = (object) ['id' => null];
            $message = 'تم إغلاق عقد الإيجار وتوثيق نقل الكفالة بنجاح.';

            $newContract = (object) ['id' => null];
            $message = 'تم إغلاق عقد الإيجار وتوثيق نقل الكفالة بنجاح.';

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
                'info',
                'marketer'
            ])->findOrFail($id);

            // Get settings for contract printing
            $contractSettings = [];
            $settingKeys = [
                'company_name', 'license_number', 'license_date', 'commercial_register',
                'commercial_date', 'city', 'address', 'po_box', 'postal_code',
                'phone', 'mobile', 'fax', 'email', 'default_trial_period',
                'default_working_hours', 'daily_deduction', 'notice_period', 'logo'
            ];

            foreach ($settingKeys as $key) {
                $setting = \App\Models\Setting::where('key', 'rent_contract_' . $key)->first();
                $contractSettings[$key] = $setting ? $setting->value : null;
            }

            $contractSettings['company_name'] = documentSetting(
                'document_company_name',
                $contractSettings['company_name'] ?? 'شركة بلاتنيوم الشرقية للاستقدام'
            );
            $contractSettings['address'] = $contractSettings['address'] ?: documentSetting('location', 'الرياض');
            $contractSettings['phone'] = $contractSettings['phone'] ?: documentSetting('phone', '');
            $contractSettings['email'] = $contractSettings['email'] ?: documentSetting('email', '');
            $contractSettings['logo'] = documentSetting('document_logo', $contractSettings['logo'] ?? null);

            // Get contract terms from database
            $termsPage1 = collect(); // Empty collection for now
            $termsPage2 = collect();
            $termsPage3 = collect();

            // Try to get terms from RentContractTerm
            try {
                // Since we don't have page field in RentContractTerm, we'll split them by order
                $allTerms = \App\Models\RentContractTerm::active()->ordered()->get();
                $termsPage1 = $allTerms->take(5); // First 5 terms on page 1
                $termsPage2 = $allTerms->slice(5, 5); // Next 5 terms on page 2
                $termsPage3 = $allTerms->slice(10); // Remaining terms on page 3
            } catch (\Exception $e) {
                // If there's an issue with terms, use empty collections
            }

            // Check if contract allows signature (after being sent)
            $allowSignature = in_array($contract->status, ['contract_sent', 'contract_signed', 'maid_delivered', 'active']);

            return view('admin.print.rental-contract-dynamic', compact('contract', 'contractSettings', 'termsPage1', 'termsPage2', 'termsPage3', 'allowSignature'));

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

            $contract->update([
                'signature' => $signatureData
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ التوقيع بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ التوقيع: ' . $e->getMessage()
            ], 500);
        }
    }



}
