<?php

namespace App\Http\Controllers\ingaz;

use App\Models\RentalContract;
use App\Models\RentalContractInfo;
use App\Models\ingaz\Maid;
use App\Models\ingaz\User;
use App\Models\ingaz\Order;
use App\Models\ingaz\Nationality;
use App\Models\auth\Admin;
use Illuminate\Http\Request;
use App\Enums\ingaz\OrderEnum;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Actions\ingaz\RecruitmentOfficeAction;
use App\Http\Requests\StoreRentalContractRequest;

class RentContractController extends Controller
{
    public $folderPath = "admin.ingaz.RentContract";
    public $mainRoute = "RentContract";

    public function index(Request $request, $status = null)
    {
        if ($request->ajax()) {
            $status = $request->get('status', $status);

            $query = RentalContract::with(['user', 'maid', 'info', 'marketer']);

            if ($status) {
                $query->where('status', $status);
            }

            $contracts = $query->latest();

            return DataTables::eloquent($contracts)
                ->addIndexColumn()
                ->editColumn('status', function ($row) {
                    return $this->getStatusBadge($row->status);
                })
                ->editColumn('user_id', function ($row) {
                    return $row->user ? $row->user->name : 'غير محدد';
                })
                ->addColumn('phone', function ($row) {
                    return $row->user ? getPhoneWithTag($row->user->phone_code . $row->user->phone) : 'غير محدد';
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
                    $remainingDays = $row->calculateRemainingDays();
                    if ($remainingDays !== null) {
                        if ($remainingDays > 0) {
                            return '<span class="badge bg-success">' . $remainingDays . ' يوم متبقي</span>';
                        } else {
                            return '<span class="badge bg-danger">منتهي</span>';
                        }
                    }
                    return '<span class="badge bg-secondary">غير محدد</span>';
                })
                ->addColumn('actions', function ($row) {
                    $actions = '<div class="dropdown">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-cogs"></i> الإجراءات
                                    </button>
                                    <ul class="dropdown-menu">';

                    // عرض التفاصيل
                    $actions .= '<li><a class="dropdown-item" href="' . route($this->mainRoute . '.show', $row->id) . '">
                                    <i class="fas fa-eye"></i> عرض التفاصيل
                                </a></li>';

                    // تعديل العقد
                    if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_EDIT)) {
                        $actions .= '<li><a class="dropdown-item" href="' . route($this->mainRoute . '.edit', $row->id) . '">
                                        <i class="fas fa-edit"></i> تعديل العقد
                                    </a></li>';
                    }

                    // طباعة العقد
                    $actions .= '<li><a class="dropdown-item" href="' . route('print.rental.contract', $row->id) . '" target="_blank">
                                    <i class="fas fa-print"></i> طباعة العقد
                                </a></li>';

                    // إنهاء العقد
                    if ($row->status !== 'completed' && $row->status !== 'cancelled') {
                        $actions .= '<li><button class="dropdown-item complete-contract" data-id="' . $row->id . '">
                                        <i class="fas fa-check"></i> إنهاء العقد
                                    </button></li>';
                    }

                    // حذف العقد
                    if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_DELETE)) {
                        $actions .= '<li><button class="dropdown-item text-danger delete-contract" data-id="' . $row->id . '">
                                        <i class="fas fa-trash"></i> حذف العقد
                                    </button></li>';
                    }

                    $actions .= '</ul></div>';

                    return $actions;
                })
                ->rawColumns(['maid_id', 'contract_info', 'remaining_days', 'actions', 'status'])
                ->make(true);
        }

        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["modalType"] = "modal-lg";
        return view($this->folderPath . '.index', $data);
    }

    public function create(RecruitmentOfficeAction $officeAction)
    {
        // الحصول على الطلبات المنتهية من نوع تأجير (job_type_id = 2)
        $endedOrders = Order::where('job_type_id', 2)
                           ->whereIn('status', [OrderEnum::Done->value])
                           ->with(['user', 'maid'])
                           ->latest()
                           ->get();

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

        // الحصول على العملاء الموجودين (للبحث السريع)
        $existing_clients = User::select('id', 'name', 'phone', 'email', 'nationality_id', 'national_num', 'birthdate', 'address', 'city', 'family_num', 'children', 'profession', 'monthly_income', 'phone2', 'rooms_num', 'job')
                              ->whereNotNull('name')
                              ->where('name', '!=', '')
                              ->where('name', 'not like', '+966%')
                              ->where('name', 'not like', '10%')
                              ->where('name', 'not like', '05%')
                              ->where('name', 'not like', '2%')
                              ->where('name', 'not like', '3%')
                              ->where('name', 'not like', '4%')
                              ->where('name', 'not like', '5%')
                              ->where('name', 'not like', '6%')
                              ->where('name', 'not like', '7%')
                              ->where('name', 'not like', '8%')
                              ->where('name', 'not like', '9%')
                              ->where('name', 'not like', '1%')
                              ->where(function($query) {
                                  $query->where('name', 'like', '%ا%')
                                        ->orWhere('name', 'like', '%ب%')
                                        ->orWhere('name', 'like', '%ت%')
                                        ->orWhere('name', 'like', '%ث%')
                                        ->orWhere('name', 'like', '%ج%')
                                        ->orWhere('name', 'like', '%ح%')
                                        ->orWhere('name', 'like', '%خ%')
                                        ->orWhere('name', 'like', '%د%')
                                        ->orWhere('name', 'like', '%ذ%')
                                        ->orWhere('name', 'like', '%ر%')
                                        ->orWhere('name', 'like', '%ز%')
                                        ->orWhere('name', 'like', '%س%')
                                        ->orWhere('name', 'like', '%ش%')
                                        ->orWhere('name', 'like', '%ص%')
                                        ->orWhere('name', 'like', '%ض%')
                                        ->orWhere('name', 'like', '%ط%')
                                        ->orWhere('name', 'like', '%ظ%')
                                        ->orWhere('name', 'like', '%ع%')
                                        ->orWhere('name', 'like', '%غ%')
                                        ->orWhere('name', 'like', '%ف%')
                                        ->orWhere('name', 'like', '%ق%')
                                        ->orWhere('name', 'like', '%ك%')
                                        ->orWhere('name', 'like', '%ل%')
                                        ->orWhere('name', 'like', '%م%')
                                        ->orWhere('name', 'like', '%ن%')
                                        ->orWhere('name', 'like', '%ه%')
                                        ->orWhere('name', 'like', '%و%')
                                        ->orWhere('name', 'like', '%ي%')
                                        ->orWhere('name', 'like', '%ى%')
                                        ->orWhere('name', 'like', '%ة%')
                                        ->orWhere('name', 'like', '%أ%')
                                        ->orWhere('name', 'like', '%إ%')
                                        ->orWhere('name', 'like', '%آ%')
                                        ->orWhere('name', 'like', '%ؤ%')
                                        ->orWhere('name', 'like', '%ئ%');
                              })
                              ->whereNotNull('phone')
                              ->orderBy('name')
                              ->limit(300) // زيادة العدد
                              ->get();

        // تسجيل عدد العملاء للتشخيص
        \Log::info('Existing clients loaded for rent contract create', [
            'count' => $existing_clients->count(),
            'first_3' => $existing_clients->take(3)->pluck('name', 'id')->toArray()
        ]);

        $offices = $officeAction->getAllOffices();

        // إضافة متغير لتحديد ما إذا كانت هناك طلبات متاحة
        $hasEndedOrders = $endedOrders->count() > 0;

        return view($this->folderPath . '.create', compact(
            'endedOrders',
            'availableRentalMaids',
            'marketers',
            'nationalities',
            'existing_clients',
            'offices',
            'hasEndedOrders'
        ));
    }

    public function store(StoreRentalContractRequest $request)
    {
        try {
            DB::beginTransaction();

            // إنشاء أو تحديث بيانات العميل
            if ($request->order_id) {
                // إذا كان هناك طلب موجود
                $order = Order::find($request->order_id);
                $user = $order->user;
                $user->update([
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
            } else {
                // إنشاء عميل جديد
                $user = User::create([
                    'name' => $request->client_name,
                    'phone' => $request->client_phone,
                    'phone_code' => '+966', // افتراضي
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
                'status' => 'new',
                'contract_date' => $request->contract_date,
                'contract_number' => $this->generateContractNumber(),
            ]);

            // حساب تاريخ انتهاء الإنجاز
            $ingazTo = \Carbon\Carbon::parse($request->ingaz_from)
                                  ->addDays($request->contract_duration_days);

            // حساب قيمة العقد
            $contractValue = $request->monthly_cost * $request->contract_duration_months;

            // إنشاء معلومات العقد
            RentalContractInfo::create([
                'rental_contract_id' => $rentalContract->id,
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

            return redirect()->route($this->mainRoute . '.index')
                           ->with('success', 'تم إنشاء عقد التأجير بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating rental contract: ' . $e->getMessage());

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

            return redirect()->route($this->mainRoute . '.index')
                           ->with('success', 'تم تحديث عقد التأجير بنجاح');

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

    private function getStatusBadge($status)
    {
        $badges = [
            'new' => '<span class="badge bg-primary">جديد</span>',
            'active' => '<span class="badge bg-success">نشط</span>',
            'completed' => '<span class="badge bg-info">مكتمل</span>',
            'cancelled' => '<span class="badge bg-danger">ملغي</span>',
            'suspended' => '<span class="badge bg-warning">معلق</span>',
        ];

        return $badges[$status] ?? '<span class="badge bg-secondary">' . $status . '</span>';
    }

    public function statistics()
    {
        try {
            $total = RentalContract::count();
            $active = RentalContract::where('status', 'active')->count();
            $completed = RentalContract::where('status', 'completed')->count();
            $totalValue = RentalContract::with('info')
                                      ->get()
                                      ->sum(function($contract) {
                                          return $contract->info ? $contract->info->contract_value : 0;
                                      });

            return response()->json([
                'total' => $total,
                'active' => $active,
                'completed' => $completed,
                'totalValue' => $totalValue
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'total' => 0,
                'active' => 0,
                'completed' => 0,
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
}
