<?php

namespace App\Http\Controllers\ingaz;

use App\Models\RentalContract;
use App\Models\RentalContractInfo;
use App\Models\Contract;
use App\Models\ContractPayment;
use App\Models\ingaz\Maid;
use App\Models\ingaz\User;
use App\Models\ingaz\Order;
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

            $query = RentalContract::with(['user', 'maid', 'info', 'marketer']);

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
                                        <small class="text-muted">' . $row->maid->passport_num . '</small>
                                    </div>
                                </div>';
                    }
                    return '<span class="text-muted">لم يتم اختيار عاملة</span>';
                })
                ->addColumn('contract_info', function ($row) {
                    if ($row->info) {
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
        \Log::info('RentContract create method called', [
            'request_params' => $request->all(),
            'has_order_id' => $request->has('order_id'),
            'order_id' => $request->get('order_id')
        ]);

        // الحصول على الطلبات المنتهية من نوع تأجير (job_type_id = 2)
        $endedOrders = Order::where('job_type_id', 2)
                           ->whereIn('status', [OrderEnum::Done->value])
                           ->with(['user', 'maid'])
                           ->latest()
                           ->get();

        // إذا تم تمرير order_id، احصل على بيانات الطلب
        $selectedOrder = null;
        if ($request->has('order_id')) {
            $selectedOrder = Order::with(['user', 'maid', 'details.package'])
                                ->where('job_type_id', 2)
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
        $availableRentalMaids = Maid::Shown()
                                   ->whereHas('jobTypes', function($query) {
                                       $query->where('job_type_id', 2); // تأجير
                                   })
                                   ->where('available', true)
                                   ->with(['nationality.translations', 'religion.translations'])
                                   ->latest()
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
                    'profession' => $request->profession,
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
                    'profession' => $request->profession,
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

            // حساب تاريخ انتهاء الإنجاز
            $contractDurationDays = $request->contract_duration_days ?: ($request->contract_duration_months * 30);
            $ingazTo = $request->ingaz_to ?: \Carbon\Carbon::parse($request->ingaz_from)->addDays($contractDurationDays);

            // حساب قيمة العقد
            $contractValue = $request->total_contract_value ?: ($request->monthly_cost * $request->contract_duration_months);

            // إنشاء معلومات العقد
            RentalContractInfo::create([
                'rental_contract_id' => $rentalContract->id,
                'working_hours_per_day' => $request->working_hours ?: 8,
                'hourly_rate' => $request->hourly_rate,
                'monthly_salary' => $request->monthly_salary,
                'sponsorship_transfer' => $request->sponsorship_transfer,
                'rental_type' => $request->rental_type,
                'monthly_cost' => $request->monthly_cost,
                'contract_duration_months' => $request->contract_duration_months,
                'contract_duration_days' => $contractDurationDays,
                'contract_value' => $contractValue,
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

        return view($this->folderPath . '.show', compact('contract'));
    }

    public function edit($id)
    {
        $contract = RentalContract::with(['user', 'maid', 'info', 'marketer'])->findOrFail($id);

        // الحصول على العاملات المتاحة من نوع تأجير
        $availableRentalMaids = Maid::Shown()
                                   ->whereHas('jobTypes', function($query) {
                                       $query->where('job_type_id', 2);
                                   })
                                   ->where(function($query) use ($contract) {
                                       $query->where('available', true)
                                             ->orWhere('id', $contract->maid_id);
                                   })
                                   ->latest()
                                   ->get();

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
                // إرجاع العاملة السابقة للإتاحة
                Maid::where('id', $oldMaidId)->update([
                    'status' => 'available',
                    'available' => true,
                ]);
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

            // إرجاع العاملة للإتاحة
            if ($contract->maid) {
                $contract->maid->update([
                    'status' => 'available',
                    'available' => true,
                ]);
            }

            // حذف العقد (سيحذف معلومات العقد تلقائياً بسبب cascade)
            $contract->delete();

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

    /**
     * Get contract details for closing modal
     */
    public function getContractDetails($id)
    {
        try {
            $contract = RentalContract::with(['user', 'maid'])->findOrFail($id);

            // Calculate remaining days
            $endDate = $contract->end_date ? Carbon::parse($contract->end_date) : null;
            $remainingDays = $endDate ? max(0, Carbon::now()->diffInDays($endDate, false)) : 0;

            $contractData = [
                'id' => $contract->id,
                'client_name' => $contract->user->name ?? 'غير محدد',
                'maid_name' => $contract->maid->name ?? 'غير محدد',
                'status_text' => $this->getStatusText($contract->status),
                'start_date' => $contract->start_date ? Carbon::parse($contract->start_date)->format('Y-m-d') : null,
                'end_date' => $contract->end_date ? Carbon::parse($contract->end_date)->format('Y-m-d') : null,
                'remaining_days' => $remainingDays . ' يوم',
                'amount' => $contract->amount ?? 0
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
    public function closeAndMakeAvailable($id)
    {
        try {
            DB::beginTransaction();

            $contract = RentalContract::findOrFail($id);

            // Update contract status to completed and set closure type
            $contract->status = 'completed';
            $contract->closure_type = 'available';
            $contract->closed_at = now();
            $contract->save();

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
                'closed_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إغلاق العقد وإرجاع العاملة متاحة بنجاح'
            ]);

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
            'notes' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $oldContract = RentalContract::findOrFail($id);

            // Close the old contract and set closure type to transferred
            $oldContract->status = 'completed';
            $oldContract->closure_type = 'transferred';
            $oldContract->closed_at = now();
            $oldContract->save();

            // Use the existing client from the rental contract
            $newClient = $oldContract->user;

            if (!$newClient) {
                throw new \Exception('لا يمكن العثور على بيانات العميل في عقد الإيجار');
            }

            // Create new contract for sponsorship transfer
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

            // Update maid status
            if ($oldContract->maid) {
                $oldContract->maid->status = 'contracted';
                $oldContract->maid->save();
            }

            // Log the action
            \Log::info('Sponsorship transferred', [
                'old_contract_id' => $oldContract->id,
                'new_contract_id' => $newContract->id,
                'new_client_id' => $newClient->id,
                'transferred_by' => auth()->id()
            ]);

            // Link the old contract to the new one
            $oldContract->transferred_to_contract_id = $newContract->id;
            $oldContract->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إغلاق عقد الإيجار ونقل الكفالة للعميل بنجاح. العقد الجديد رقم: ' . $newContract->id,
                'new_contract_id' => $newContract->id,
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
            $contract = RentalContract::findOrFail($id);

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
