<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Enums\ingaz\OrderEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\StoreContractRequest;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ContractStatusLog;
use App\Models\ingaz\Contact;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Order;
use App\Models\ingaz\User;
use App\Models\ingaz\UserAddress;
use App\Models\TravelHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Country;
use App\Actions\OrderAction;
use App\Actions\MaidAction;
use App\Actions\ClientAction;
use App\Actions\NationalityAction;
use App\Actions\ReligionAction;
use App\Actions\JobTitleAction;
use App\Actions\JobTypeAction;
use Illuminate\Support\Facades\DB;

class RecruitmentContractController extends Controller
{
    public $folderPath = "admin.ingaz.RecruitmentContract";
    public $mainRoute = "RecruitmentContract";
    public $contractInfoData = [
        'visa_number',
        'visa_hijry_date',
        'visa_milad_date',
        'working_hours_num'
        ,
        'sponsorship_price',
        'salary_after_sponsorship',
        'monthly_cost',
        'contract_duration'
        ,
        'contract_value',
        'contract_date',
        'ingaz_from',
        'ingaz_to',
        'previous_sponsorship',
        'msaned_number',
        'tafwed_number'
    ];


    public function index(Request $request, $status = null)
    {
        // التحقق من صلاحية عرض العقود
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_VIEW)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لعرض العقود'
            ], 403);
        }

        if ($request->ajax()) {
            \Log::info('RecruitmentContract index AJAX request', [
                'request_data' => $request->all(),
                'status' => $status
            ]);

            $query = Contract::with(['user', 'maid', 'info'])
                ->where('type', 'recruitment');

            // فلتر الحالة
            if ($status) {
                $query->where('status', $status);
            }

            // فلتر التاريخ من
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            // فلتر التاريخ إلى
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // فلتر رقم جوال العميل
            if ($request->filled('client_phone')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('phone', 'like', '%' . $request->client_phone . '%');
                });
            }

            // فلتر اسم العميل
            if ($request->filled('client_name')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->client_name . '%');
                });
            }

            // فلتر رقم جواز العاملة
            if ($request->filled('maid_passport')) {
                $query->whereHas('maid', function($q) use ($request) {
                    $q->where('passport_num', 'like', '%' . $request->maid_passport . '%');
                });
            }

            // فلتر اسم العاملة
            if ($request->filled('maid_name')) {
                $query->whereHas('maid', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->maid_name . '%');
                });
            }

            // فلتر رقم العقد
            if ($request->filled('contract_number')) {
                $query->where('id', 'like', '%' . $request->contract_number . '%');
            }

            // فلتر جنسية العاملة
            if ($request->filled('nationality')) {
                $query->whereHas('maid.nationality', function($q) use ($request) {
                    $q->where('sub_title', 'like', '%' . $request->nationality . '%');
                });
            }

            // فلتر ديانة العاملة
            if ($request->filled('religion')) {
                $query->whereHas('maid.religion', function($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->religion . '%');
                });
            }

            // فلتر مدينة العميل
            if ($request->filled('city')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('city', 'like', '%' . $request->city . '%');
                });
            }

            $rows = $query->orderBy('id', 'desc');

            \Log::info('RecruitmentContract query results', [
                'total_count' => $rows->count(),
                'sql' => $rows->toSql(),
                'bindings' => $rows->getBindings()
            ]);

            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('status', function ($row) {
                    $statusText = getEnumValue('contract_statuses', $row->status);
                    $statusColor = getContractStatusColor($row->status);

                    $statusDisplay = '<span class="badge ' . $statusColor . ' status-badge">' . $statusText . '</span>';

                    // إضافة معلومات إضافية حسب الحالة
                    if ($row->status === ContractStatusEnum::Arrival->value) {
                        $warrantyEndDate = $row->updated_at->addDays(90);
                        $remainingDays = now()->diffInDays($warrantyEndDate, false);

                        if ($remainingDays > 0) {
                            $statusDisplay .= '<br><small class="text-info">متبقي ' . $remainingDays . ' يوم للضمان</small>';
                        } else {
                            $statusDisplay .= '<br><small class="text-danger">انتهى الضمان</small>';
                        }
                    }

                    // إضافة تاريخ آخر تحديث
                    $lastUpdate = $row->updated_at ? $row->updated_at->format('Y-m-d') : '';
                    if ($lastUpdate) {
                        $statusDisplay .= '<br><small class="text-muted">آخر تحديث: ' . $lastUpdate . '</small>';
                    }

                    return $statusDisplay;
                })
                ->editColumn('user_id', function ($row) {
                    $userInfo = '';
                    if ($row->user) {
                        $userInfo .= '<div class="user-info">';
                        $userInfo .= '<br><strong>' . $row->user->name . '</strong>';
                        if ($row->user->birthdate) {
                            $userInfo .= '<br><small class="text-muted">تاريخ الميلاد: ' . \Carbon\Carbon::parse($row->user->birthdate)->format('Y-m-d') . '</small>';
                        }
                        if ($row->user->national_num) {
                            $userInfo .= '<br><small class="text-muted">رقم الهوية: ' . $row->user->national_num . '</small>';
                        }
                        if ($row->user->address) {
                            $userInfo .= '<br><small class="text-muted">العنوان: ' . $row->user->address . '</small>';
                        }
                        $userInfo .= '</div>';
                    }
                    return $userInfo;
                })
                ->editColumn('phone', function ($row) {
                    return ($row->user) ? getPhoneWithTag($row->user->phone_code . $row->user->phone) : '';
                })
                ->editColumn('maid_id', function ($row) {
                    $maidInfo = '';
                    if ($row->maid) {
                        $maidInfo .= '<div class="maid-info">';
                        // إضافة تعليق لصورة العاملة
                        // $maidInfo .= '<div class="maid-image-container" title="صورة العاملة: ' . $row->maid->name . '">';
                        // $maidInfo .= getImgTag($row->maid->cv_file, "80px", "80px");
                        // $maidInfo .= '</div>';
                        $maidInfo .= '<br><strong>' . $row->maid->name . '</strong>';
                        if ($row->maid->passport_num) {
                            $maidInfo .= '<br><small class="text-muted">جواز: ' . $row->maid->passport_num . '</small>';
                        }
                        if ($row->maid->nationality) {
                            $maidInfo .= '<small class="text-muted">الجنسية: ' . $row->maid->nationality->sub_title . '</small>';
                        }
                       $maidInfo .= '</div>';
                    }
                    return $maidInfo;
                })
                ->addColumn('contract_info', function ($row) {
                    $contractInfo = '';
                    if ($row->info) {
                        $contractInfo .= '<div class="contract-info">';
                        if ($row->info->msaned_number) {
                            $contractInfo .= '<br><div><strong>مساند:</strong> ' . $row->info->msaned_number . '</div>';
                        }
                        // if ($row->info->tafwed_number) {
                        //     $contractInfo .= '<div><strong>تفويض:</strong> ' . $row->info->tafwed_number . '</div>';
                        // }
                        if ($row->info->visa_number) {
                            $contractInfo .= '<br><div><strong>تأشيرة:</strong> ' . $row->info->visa_number . '</div>';
                        }
                        if ($row->info->contract_date) {
                            $contractInfo .= '<br><div><strong>تاريخ العقد:</strong> ' . \Carbon\Carbon::parse($row->info->contract_date)->format('Y-m-d') . '</div>';
                        }
                        $contractInfo .= '</div>';
                    }
                    return $contractInfo ?: '<span class="text-muted">غير محدد</span>';
                })
                ->addColumn('show_details', function ($row) {
                    return '<a class="btn btn-sm btn-info btn-action" href="' . route('contracts.details', $row->id) . '" title="عرض تفاصيل العقد" target="_blank">
                        <i class="fas fa-eye me-1"></i>عرض التفاصيل
                    </a>';
                })
                ->addColumn('updateStatus', function ($row) {
                    if ($row->status == ContractStatusEnum::PendingMaidSelection->value) {
                        // زر إضافة عاملة للعقود التي في حالة بانتظار اختيار العاملة
                        return '<a href="' . route('RecruitmentContract.selectMaid', $row->id) . '" class="btn btn-warning btn-sm btn-action" title="إضافة عاملة">
                            <i class="fas fa-user-plus me-1"></i>إضافة عاملة
                        </a>';
                    } else if ($row->status == ContractStatusEnum::Tazker->value) {
                        // التحقق من صلاحيات إدارة الرحلات
                        $hasTravelPermission = @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_VIEW_TRAVEL);
                        $hasAddTravelPermission = @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_ADD_TRAVEL);
                        $hasCompleteTravelPermission = @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL);

                        // check if there is a travel history for this contract
                        $travelHistory = TravelHistory::where('order_id', $row->id)->first();

                        if ($travelHistory) {
                            if ($travelHistory->status === 'completed') {
                                return '<span class="badge bg-success">تم إكمال الرحلة</span>';
                            } else if ($travelHistory->status === 'active') {
                                if ($hasCompleteTravelPermission) {
                                    return '<button class="btn btn-success btn-sm complete-trip-btn btn-action" data-trip-id="' . $travelHistory->id . '" title="إكمال الرحلة">
                                        <i class="fas fa-check me-1"></i>إكمال الرحلة
                                    </button>';
                                } else {
                                    return '<span class="badge bg-warning">رحلة نشطة</span>';
                                }
                            } else {
                                return '<span class="badge bg-info">رحلة قيد الإعداد</span>';
                            }
                        } else {
                            if ($hasAddTravelPermission) {
                                return '<button class="btn btn-primary btn-sm add-travel-btn btn-action" data-contract-id="' . $row->id . '" title="إضافة رحلة">
                                    <i class="fas fa-plane me-1"></i>إضافة رحلة
                                </button>';
                            } else {
                                return '<span class="text-muted">لا توجد صلاحية لإضافة رحلة</span>';
                            }
                        }
                    } else {
                        // التحقق من الصلاحيات حسب حالة العقد
                        $hasPermission = false;
                        $permissionRequired = '';
                        $nextStatus = '';
                        $buttonText = '';

                        switch ($row->status) {
                            case ContractStatusEnum::New->value:
                                $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_MSANED;
                                $nextStatus = ContractStatusEnum::Msaned->value;
                                $buttonText = 'انتقال إلى مساند';
                                if (@AdminGuardHasPerm($permissionRequired)) {
                                    $hasPermission = true;
                                }
                                break;
                            case ContractStatusEnum::Msaned->value:
                                $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFWED;
                                $nextStatus = ContractStatusEnum::Tafwed->value;
                                $buttonText = 'انتقال إلى تفويض';
                                if (@AdminGuardHasPerm($permissionRequired)) {
                                    $hasPermission = true;
                                }
                                break;
                            case ContractStatusEnum::Tafwed->value:
                                $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFFEZ;
                                $nextStatus = ContractStatusEnum::Taffez->value;
                                $buttonText = 'انتقال إلى تفييز';
                                if (@AdminGuardHasPerm($permissionRequired)) {
                                    $hasPermission = true;
                                }
                                break;
                            case ContractStatusEnum::Taffez->value:
                                $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAZKER;
                                $nextStatus = ContractStatusEnum::Tazker->value;
                                $buttonText = 'انتقال إلى تذكرة';
                                if (@AdminGuardHasPerm($permissionRequired)) {
                                    $hasPermission = true;
                                }
                                break;
                            case ContractStatusEnum::Arrival->value:
                                // التحقق من صلاحية إغلاق العقد بعد الضمان
                                $nextStatus = ContractStatusEnum::Closed->value;
                                $buttonText = 'إغلاق العقد';
                                if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CLOSE_AFTER_WARRANTY)) {
                                    $hasPermission = true;
                                }
                                break;
                            default:
                                break;
                        }

                        if ($hasPermission) {
                            $lastContractStatusLog = $row->getLastContractStatusLog();
                            return '<button class="btn btn-success btn-sm update-status-btn btn-action" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-row-id="' . $row->id . '" data-status="' . $row->status . '" data-next-status="' . $nextStatus . '" data-date="' . ($lastContractStatusLog ? $lastContractStatusLog->date : \Carbon\Carbon::now()->format('Y-m-d')) . '" title="' . $buttonText . '">
                                <i class="fas fa-arrow-up me-1"></i>' . $buttonText . '
                            </button>';
                        } else {
                            return '<span class="text-muted">لا توجد صلاحية</span>';
                        }
                    }
                })
                ->addColumn('back', function ($row) {
                    if ($row->status == ContractStatusEnum::Back->value) {
                        return '<span class="badge bg-danger">مرتجع</span>';
                    } else {
                        // التحقق من صلاحية إرجاع العقد
                        if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_RETURN)) {
                            return '<button class="btn btn-danger btn-sm backContract btn-action" data-row-id="' . $row->id . '" title="إرجاع العقد">
                                <i class="fas fa-undo"></i>
                            </button>';
                        } else {
                            return '<span class="text-muted">لا توجد صلاحية للإرجاع</span>';
                        }
                    }
                })
                ->addColumn('actions', function ($row) {
                    $buttons = '';

                    // زر الطباعة
                    $buttons .= '<a href="' . route('contracts.details', $row->id) . '" class="btn btn-sm btn-secondary btn-action me-1" title="طباعة العقد" target="_blank">
                        <i class="fas fa-print"></i>
                    </a>';

                    // زر التعديل
                    if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EDIT)) {
                        $buttons .= '<a href="' . route($this->mainRoute . '.edit', $row->id) . '" class="btn btn-sm btn-warning btn-action me-1" title="تعديل العقد">
                            <i class="fas fa-edit"></i>
                        </a>';
                    }

                    // زر الحذف
                    if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_DELETE)) {
                        $buttons .= deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }

                    return $buttons;
                })
                ->escapeColumns([])
                ->make(true);
        }

        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["modalType"] = "modal-lg";
        $data["status"] = $status ?? $request->get('status', null);
        return view($this->folderPath . '.index', $data);
    }


    public function details($id)
    {
        // التحقق من صلاحية عرض تفاصيل العقد
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_VIEW)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لعرض تفاصيل العقد'
            ], 403);
        }

        $contract = Contract::with([
            'user',
            'maid',
            'info',
            'contract_status_logs',
            'order.details.admin' // إضافة بيانات المسوق من الطلب
        ])->findOrFail($id);

        $info = ContractInfo::where('contract_id', $contract->id)->first();

        // الحصول على بيانات المسوق
        $marketer = null;
        if ($contract->order && $contract->order->details) {
            $marketer = $contract->order->details->admin;
        }

        //        return $contract;
        return view('admin.ingaz.RecruitmentContract.details', compact('contract', 'info', 'marketer'));
    }

    public function create()
    {
        // التحقق من صلاحية إنشاء عقد جديد
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CREATE)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لإنشاء عقد جديد'
            ], 403);
        }

        $orderId = request('orderId');
        \Log::info('Loading create page with orderId: ' . $orderId);

        // Load the selected order with all necessary relationships
        $selectedOrder = null;
        if ($orderId) {
            $selectedOrder = Order::with([
                'user.nationality',
                'maid.nationality',
                'maid.religion',
                'maid.job_types',
                'jobType.translations'
            ])->find($orderId);
            \Log::info('Selected order loaded:', ['order' => $selectedOrder ? $selectedOrder->toArray() : null]);
        }

        // Get new orders with relationships
        $orders = Order::with([
            'user.nationality',
            'maid.nationality',
            'maid.religion',
            'maid.job_types',
            'jobType.translations'
        ])
        ->where('status', 'new')
        ->get();

        // Get ended orders with relationships
        $endedOrders = Order::with([
            'user.nationality',
            'maid.nationality',
            'maid.religion',
            'maid.job_types',
            'jobType.translations'
        ])
        ->where('status', 'ended')
        ->get();

        // Get all available maids with relationships
        $all_available_maids = Maid::with([
            'nationality',
            'religion',
            'jobTitles'
        ])->get();

        // Get existing clients for new contract
        $existing_clients = User::with('nationality')->get();

        // If we have a selected order and it's not in the new orders list, add it
        if ($selectedOrder && !$orders->contains('id', $selectedOrder->id)) {
            $orders->prepend($selectedOrder);
        }

        $countries = Country::active()->ordered()->get();
        return view('admin.ingaz.RecruitmentContract.create', compact('orders', 'endedOrders', 'countries', 'all_available_maids', 'selectedOrder', 'orderId', 'existing_clients'));
    }


    public function store(StoreContractRequest $request)
    {
        // التحقق من صلاحية إنشاء العقد
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CREATE)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لإنشاء عقد جديد'
            ], 403);
        }

        \Log::info('Recruitment Contract Creation Request:', $request->all());

        try {
            DB::beginTransaction();

            // دعم حالة عقد بدون عاملة أو تأجيل اختيار العاملة
            $maidId = $request->maid_id ?? $request->new_maid_id;
            $isNoMaid = ($maidId === 'no_maid');
            $isMaidSelectionPostponed = $request->maid_selection_postponed == '1';

            // تحديد حالة العقد
            $contractStatus = ContractStatusEnum::New->value;
            if ($isNoMaid || $isMaidSelectionPostponed) {
                $contractStatus = ContractStatusEnum::PendingMaidSelection->value;
            }

            $contractData = [
                'status' => $contractStatus,
                'maid_id' => ($isNoMaid || $isMaidSelectionPostponed) ? null : $maidId,
            ];

            if ($request->order_id) {
                $order = Order::findOrFail($request->order_id);
                \Log::info('Found order:', ['order' => $order->toArray()]);

                $contractData['user_id'] = $order->user_id;
                $contractData['maid_id'] = $isNoMaid ? null : $order->maid_id;

                \Log::info('Contract data from order:', $contractData);
            } else {
                // Handle new contract without existing order
                $user = User::where('phone', $request->client_phone)->first();
                if (!$user) {
                    $user = User::create([
                        'name' => $request->client_name,
                        'phone_code' => '+966',
                        'phone' => $request->client_phone,
                        'password' => bcrypt('123456'), // Default password
                        'nationality_id' => $request->client_nationality_id,
                        'national_num' => $request->client_national_num,
                        'email' => $request->client_email,
                        'phone2' => $request->client_phone2,
                        'city' => $request->client_city,
                        'beds_num' => $request->client_beds_num,
                        'family_num' => $request->client_family_num,
                        'children' => $request->client_children,
                        'job' => $request->client_job,
                        'address' => $request->client_address,
                        'birthdate' => $request->client_birthdate,
                    ]);
                    \Log::info('Created new user:', ['user' => $user->toArray()]);
                } else {
                    // Update existing user with new data
                    $user->update([
                        'name' => $request->client_name,
                        'phone_code' => '+966',
                        'nationality_id' => $request->client_nationality_id,
                        'national_num' => $request->client_national_num,
                        'email' => $request->client_email,
                        'phone2' => $request->client_phone2,
                        'city' => $request->client_city,
                        'beds_num' => $request->client_beds_num,
                        'family_num' => $request->client_family_num,
                        'children' => $request->client_children,
                        'job' => $request->client_job,
                        'address' => $request->client_address,
                        'birthdate' => $request->client_birthdate,
                        'password' => bcrypt('123456'), // Update password
                    ]);
                    \Log::info('Updated existing user:', ['user' => $user->toArray()]);
                }
                $contractData['user_id'] = $user->id;
            }

            \Log::info('Creating contract with data:', $contractData);

            $contract = Contract::create($contractData);
            \Log::info('Contract created:', ['contract' => $contract->toArray()]);

            // حفظ معلومات العقد
            $contractInfo = [
                'contract_date' => $request->contract_date,
                'msaned_number' => $request->msaned_number,
                'tafwed_number' => $request->tafwed_number,
                'visa_number' => $request->visa_number,
                'visa_milad_date' => $request->visa_milad_date,
                'visa_hijry_date' => $request->visa_hijry_date,
                'contract_duration' => $request->contract_duration,
            ];

            \Log::info('Creating contract info:', $contractInfo);

            $contract->info()->create($contractInfo);
            \Log::info('Contract info created');

            // حفظ سجل حالة العقد
            $contract->contract_status_logs()->create(['status' => $contractStatus]);
            \Log::info('Contract status log created');

            // تحديث حالة الطلب إذا كان العقد من طلب
            if ($request->order_id) {
                $order->update(['status' => OrderEnum::Completed->value]);
                \Log::info('Order status updated to completed');
            }

            DB::commit();

            \Log::info('Contract created successfully', ['contract_id' => $contract->id]);

            $successMessage = 'تم إنشاء العقد بنجاح';
            if ($isNoMaid || $isMaidSelectionPostponed) {
                $successMessage = 'تم إنشاء العقد بنجاح. يمكنك إضافة عاملة لاحقاً من صفحة العقود.';
            }

            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'contract' => $contract->load(['user', 'maid', 'info'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error creating contract:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء العقد: ' . $e->getMessage()
            ], 500);
        }
    }


    public function show($id, Request $request)
    {
        try {
            $row = Contract::find($id);

            // التحقق من وجود العقد
            if (!$row) {
                \Log::warning('Contract not found', ['id' => $id, 'request' => $request->all()]);
                return response()->json([
                    'success' => false,
                    'message' => 'العقد غير موجود'
                ], 404);
            }

            // التحقق من أن العقد من نوع الاستقدام
            // تم إزالة هذا التحقق لأن جميع العقود في هذا Controller هي عقود استقدام
            // if ($row->type !== 'recruitment') {
            //     \Log::warning('Contract is not recruitment type', ['id' => $id, 'type' => $row->type]);
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'هذا العقد ليس من نوع الاستقدام'
            //     ], 400);
            // }

            // إذا كان الطلب لتحديث الحالة
            if ($request->has('status') && $request->has('next_status')) {
                $currentStatus = $request->status;
                $nextStatus = $request->next_status;
                $statusDate = $request->date ?? now()->format('Y-m-d');

                // التحقق من الصلاحيات حسب حالة العقد الحالية
                $hasPermission = false;
                $permissionRequired = '';

                switch ($currentStatus) {
                    case ContractStatusEnum::New->value:
                        $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_MSANED;
                        if (@AdminGuardHasPerm($permissionRequired)) {
                            $hasPermission = true;
                        }
                        break;
                    case ContractStatusEnum::Msaned->value:
                        $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFWED;
                        if (@AdminGuardHasPerm($permissionRequired)) {
                            $hasPermission = true;
                        }
                        break;
                    case ContractStatusEnum::Tafwed->value:
                        $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFFEZ;
                        if (@AdminGuardHasPerm($permissionRequired)) {
                            $hasPermission = true;
                        }
                        break;
                    case ContractStatusEnum::Taffez->value:
                        $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAZKER;
                        if (@AdminGuardHasPerm($permissionRequired)) {
                            $hasPermission = true;
                        }
                        break;
                    case ContractStatusEnum::Arrival->value:
                        // التحقق من صلاحية إغلاق العقد بعد الضمان
                        if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CLOSE_AFTER_WARRANTY)) {
                            $hasPermission = true;
                        }
                        break;
                    default:
                        break;
                }

                if (!$hasPermission) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ليس لديك صلاحية لتغيير حالة العقد من ' . getContractStatus($currentStatus) . ' إلى المرحلة التالية'
                    ], 403);
                }

                // تحديث حالة العقد
                $row->status = $nextStatus;
                $row->save();

                // حفظ سجل حالة العقد
                $row->contract_status_logs()->create([
                    'status' => $nextStatus,
                    'date' => $statusDate
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم تحديث حالة العقد بنجاح من ' . getContractStatus($currentStatus) . ' إلى ' . getContractStatus($nextStatus)
                ]);
            }

            // إذا كان الطلب لعرض معلومات العقد فقط
            return response()->json([
                'success' => true,
                'message' => 'تم تحميل معلومات العقد بنجاح',
                'contract' => $row->load(['user', 'maid', 'info'])
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in show contract:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء معالجة الطلب: ' . $e->getMessage()
            ], 500);
        }
    }

    public function back($id)
    {
        try {
            // التحقق من صلاحية إرجاع العقد
            if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_RETURN)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لإرجاع العقد'
                ], 403);
            }

            $contract = Contract::find($id);
            if (!$contract) {
                \Log::warning('Contract not found in back function', ['id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => 'العقد غير موجود'
                ], 404);
            }

            // // التحقق من أن العقد من نوع الاستقدام
            // // تم إزالة هذا التحقق لأن جميع العقود في هذا Controller هي عقود استقدام
            // if ($contract->type !== 'recruitment') {
            //     \Log::warning('Contract is not recruitment type in back function', ['id' => $id, 'type' => $contract->type]);
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'هذا العقد ليس من نوع الاستقدام'
            //     ], 400);
            // }

            $newStatus = contractBackStatus($contract->status);
            $contract->update(['status' => $newStatus->value]);
            $contract->contract_status_logs()->create(['status' => $newStatus->value]);

            return response()->json([
                'success' => true,
                'message' => 'تم إرجاع العقد بنجاح إلى ' . getContractStatus($newStatus->value)
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in back contract', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرجاع العقد: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
    }

    public function destroy($id)
    {
        // التحقق من صلاحية حذف العقد
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_DELETE)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لحذف العقد'
            ], 403);
        }

        $contract = Contract::find($id);
        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'العقد غير موجود'
            ], 404);
        }

        $contract->delete();
        return response()->json([
            'success' => true,
            'message' => 'تم حذف العقد بنجاح'
        ]);
    }

    public function getMaidByNationality($nationalityId)
    {
        $maids = Maid::where('nationality_id', $nationalityId)->get();
        return response()->json($maids->toArray());
    }

    public function getClientDetails($id)
    {
        $client = User::with('nationality')->find($id);
        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }
        return response()->json($client);
    }

    public function exportPdf()
    {
        // التحقق من صلاحية تصدير العقود
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EXPORT)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لتصدير العقود'
            ], 403);
        }

        $contracts = Contract::RecruitmentContract()->with('user', 'maid', 'info')->get();

        // Create HTML content with RTL support and better styling
        $html = '<!DOCTYPE html>
        <html dir="rtl">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <title>قائمة عقود الاستقدام</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    direction: rtl;
                    margin: 20px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    padding: 10px;
                    background-color: #f8f9fa;
                    border-radius: 5px;
                }
                .header h1 {
                    color: #333;
                    margin-bottom: 10px;
                }
                .print-button {
                    display: inline-block;
                    padding: 10px 20px;
                    background-color: #4CAF50;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                    margin-bottom: 20px;
                    cursor: pointer;
                    border: none;
                    font-size: 16px;
                }
                .print-button:hover {
                    background-color: #45a049;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                    font-size: 12px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: right;
                }
                th {
                    background-color: #f2f2f2;
                    color: #333;
                    font-weight: bold;
                }
                tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                @media print {
                    .print-button {
                        display: none;
                    }
                    body {
                        margin: 0;
                        padding: 20px;
                    }
                    table {
                        page-break-inside: auto;
                    }
                    tr {
                        page-break-inside: avoid;
                        page-break-after: auto;
                    }
                }
            </style>
            <script>
                function printDocument() {
                    window.print();
                }
                window.onload = function() {
                    window.print();
                }
            </script>
        </head>
        <body>
            <div class="header">
                <h1>قائمة عقود الاستقدام</h1>
                <p>تم التصدير في: ' . date('Y-m-d H:i:s') . '</p>
                <button class="print-button" onclick="printDocument()">طباعة</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>رقم العقد</th>
                        <th>العميل</th>
                        <th>رقم هاتف العميل</th>
                        <th>العاملة</th>
                        <th>رقم جواز العامل</th>
                        <th>تاريخ العقد</th>
                        <th>تاريخ البداية</th>
                        <th>تاريخ النهاية</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($contracts as $contract) {
            $html .= '<tr>
                <td>' . $contract->id . '</td>
                <td>' . ($contract->user ? $contract->user->name : '') . '</td>
                <td>' . ($contract->user ? getPhoneWithTag($contract->user->phone_code . $contract->user->phone) : '') . '</td>
                <td>' . ($contract->maid ? $contract->maid->name : '') . '</td>
                <td>' . ($contract->maid ? $contract->maid->passport_num : '') . '</td>
                <td>' . ($contract->info ? $contract->info->contract_date : '') . '</td>
                <td>' . ($contract->info ? $contract->info->ingaz_from : '') . '</td>
                <td>' . ($contract->info ? $contract->info->ingaz_to : '') . '</td>
                <td>' . getEnumValue('contract_statuses', $contract->status) . '</td>
            </tr>';
        }

        $html .= '</tbody></table></body></html>';

        // Return HTML with print styles
        $headers = [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'inline; filename="contracts.html"',
        ];

        return response($html, 200, $headers);
    }

    public function exportExcel()
    {
        // التحقق من صلاحية تصدير العقود
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EXPORT)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لتصدير العقود'
            ], 403);
        }

        $contracts = Contract::RecruitmentContract()->with('user', 'maid', 'info')->get();

        // Create new Spreadsheet object
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set RTL direction
        $sheet->setRightToLeft(true);

        // Set headers
        $headers = [
            'رقم العقد',
            'العميل',
            'رقم هاتف العميل',
            'العاملة',
            'رقم جواز العامل',
            'تاريخ العقد',
            'تاريخ البداية',
            'تاريخ النهاية',
            'الحالة'
        ];

        // Set headers in first row
        $sheet->fromArray($headers, NULL, 'A1');

        // Style headers
        $headerStyle = [
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'F2F2F2',
                ],
            ],
        ];
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

        // Set data
        $row = 2;
        foreach ($contracts as $contract) {
            $sheet->setCellValue('A' . $row, $contract->id);
            $sheet->setCellValue('B' . $row, $contract->user ? $contract->user->name : '');
            $sheet->setCellValue('C' . $row, $contract->user ? $contract->user->phone_code . $contract->user->phone : '');
            $sheet->setCellValue('D' . $row, $contract->maid ? $contract->maid->name : '');
            $sheet->setCellValue('E' . $row, $contract->maid ? $contract->maid->passport_num : '');
            $sheet->setCellValue('F' . $row, $contract->info ? $contract->info->contract_date : '');
            $sheet->setCellValue('G' . $row, $contract->info ? $contract->info->ingaz_from : '');
            $sheet->setCellValue('H' . $row, $contract->info ? $contract->info->ingaz_to : '');
            $sheet->setCellValue('I' . $row, getEnumValue('contract_statuses', $contract->status));
            $row++;
        }

        // Auto-size columns
        foreach(range('A','I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Create Excel writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        // Set headers for download
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="contracts.xlsx"',
        ];

        // Save to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $writer->save($tempFile);

        // Return file
        return response()->download($tempFile, 'contracts.xlsx', $headers)->deleteFileAfterSend(true);
    }

    public function completeTrip($tripId)
    {
        try {
            // التحقق من صلاحية إكمال الرحلة
            if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لإكمال الرحلة'
                ], 403);
            }

            \Log::info('Starting trip completion process for trip ID: ' . $tripId);

            $travelHistory = TravelHistory::findOrFail($tripId);
            \Log::info('Found travel history:', ['travel' => $travelHistory->toArray()]);

            $contract = Contract::where('id', $travelHistory->order_id)->first();
            \Log::info('Found contract:', ['contract' => $contract ? $contract->toArray() : null]);

            if (!$contract) {
                \Log::error('Contract not found for travel history ID: ' . $tripId);
                return response()->json([
                    'success' => false,
                    'message' => __('admin.contract_not_found')
                ]);
            }

            // التحقق من أن العقد في مرحلة التذكرة
            if ($contract->status !== ContractStatusEnum::Tazker->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'يمكن إكمال الرحلة فقط للعقود في مرحلة التذكرة'
                ], 400);
            }

            // Update travel history status
            $travelHistory->status = 'completed';
            $travelHistory->save();
            \Log::info('Updated travel history status to completed');

            // Update contract status to Arrival
            $contract->status = ContractStatusEnum::Arrival->value;
            $contract->save();
            \Log::info('Updated contract status to Arrival');

            // Create contract status log
            $contract->contract_status_logs()->create([
                'status' => ContractStatusEnum::Arrival->value,
                'notes' => 'تم اكتمال الرحلة والانتقال إلى مرحلة تحت الضمان',
                'date' => now()
            ]);
            \Log::info('Created contract status log');

            // Force refresh the contract status
            $contract->refresh();

            return response()->json([
                'success' => true,
                'message' => __('admin.trip_completed_success'),
                'contract_status' => $contract->status
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in completeTrip: ' . $e->getMessage(), [
                'tripId' => $tripId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => __('admin.something_went_wrong')
            ]);
        }
    }

    /**
     * جلب العاملات المتاحة
     */
    public function getAvailableMaids(Request $request)
    {
        try {
            $query = Maid::with(['nationality', 'religion', 'jobTitles'])
                ->where('available', true)
                ->whereIn('status', [Maid::STATUS_AVAILABLE['ar'], Maid::STATUS_AVAILABLE['en']])
                ->where('is_show', true)
                ->where('available_at', '<=', now()->toDateString())
                ->whereNotNull('name')
                ->where('name', '!=', '')
                ->whereDoesntHave('contracts', function($q) {
                    $q->whereIn('status', ['active', 'pending']);
                })
                ->whereDoesntHave('orders', function($q) {
                    $q->whereIn('status', ['pending', 'approved', 'in_progress']);
                });

            // فلترة حسب الجنسية
            if ($request->has('nationality_id') && $request->nationality_id) {
                $query->where('nationality_id', $request->nationality_id);
            }

            // فلترة حسب الديانة
            if ($request->has('religion_id') && $request->religion_id) {
                $query->where('religion_id', $request->religion_id);
            }

            // البحث بالاسم أو رقم الجواز
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('passport_num', 'LIKE', "%{$search}%")
                      ->orWhere('id_number', 'LIKE', "%{$search}%");
                });
            }

            $maids = $query->get();

            // تحويل البيانات للعرض
            $maidsData = $maids->map(function($maid) {
                if (!is_object($maid) || !property_exists($maid, 'status')) {
                    \Log::warning('Maid is not object or missing status', ['maid' => $maid]);
                    return null;
                }
                return [
                    'id' => $maid?->id ?? '',
                    'name' => $maid?->name ?? '',
                    'passport_num' => $maid?->passport_num ?? '',
                    'nationality' => $maid?->nationality?->sub_title ?? '',
                    'religion' => $maid?->religion?->title ?? '',
                    'job' => $maid?->jobTitles?->first()?->title ?? '',
                    'salary' => $maid?->salary ?? '',
                    'experience_years' => $maid?->experience_years ?? '',
                    'age' => $maid?->age ?? '',
                    'cv_file' => $maid?->cv_file ?? '',
                    'image' => $maid?->image ?? '',
                    'status' => $maid?->status ?? 'available'
                ];
            })->filter(fn($item) => !is_null($item))->values();

            return response()->json([
                'success' => true,
                'data' => $maidsData
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getAvailableMaids', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب العاملات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب قوائم الجنسيات والديانات للفلترة
     */
    public function getFilterOptions()
    {
        try {
            $nationalities = \App\Models\ingaz\Nationality::with('translations')->get()->map(function($nationality) {
                return [
                    'id' => $nationality->id,
                    'title' => $nationality->sub_title
                ];
            });

            $religions = \App\Models\ingaz\Religion::with('translations')->get()->map(function($religion) {
                return [
                    'id' => $religion->id,
                    'title' => $religion->title
                ];
            });

            return response()->json([
                'success' => true,
                'nationalities' => $nationalities,
                'religions' => $religions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب قوائم الفلترة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * إضافة عاملة للعقد
     */
    public function addMaidToContract(Request $request, $contractId)
    {
        try {
            $contract = Contract::findOrFail($contractId);

            // التحقق من أن العقد في حالة بانتظار اختيار العاملة
            if ($contract->status !== ContractStatusEnum::PendingMaidSelection->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إضافة عاملة لهذا العقد'
                ], 400);
            }

            // التحقق من صحة البيانات
            $request->validate([
                'maid_id' => 'required|exists:maids,id'
            ]);

            // التحقق من أن العاملة متاحة
            $maid = Maid::find($request->maid_id);
            if (!$maid || !$maid->available) {
                return response()->json([
                    'success' => false,
                    'message' => 'العاملة غير متاحة'
                ], 400);
            }

            // تحديث العقد
            $contract->update([
                'maid_id' => $request->maid_id,
                'status' => ContractStatusEnum::New->value
            ]);

            // إضافة سجل حالة العقد
            $contract->contract_status_logs()->create([
                'status' => ContractStatusEnum::New->value,
                'date' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة العاملة للعقد بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة العاملة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * صفحة اختيار عاملة للعقد
     */
    public function selectMaidForContract($contractId, Request $request)
    {
        try {
            $contract = Contract::with(['user', 'info'])->find($contractId);

            // التحقق من وجود العقد
            if (!$contract) {
                // البحث عن عقود بانتظار اختيار العاملة
                $pendingContracts = Contract::where('status', ContractStatusEnum::PendingMaidSelection->value)->pluck('id')->toArray();

                $errorMessage = 'العقد غير موجود';
                if (!empty($pendingContracts)) {
                    $errorMessage .= ' - العقود المتاحة بانتظار اختيار العاملة: ' . implode(', ', $pendingContracts);
                }

                return redirect()->route('RecruitmentContract.index')
                    ->with('error', $errorMessage);
            }

            // التحقق من أن العقد في حالة بانتظار اختيار العاملة
            if ($contract->status !== ContractStatusEnum::PendingMaidSelection->value) {
                return redirect()->route('RecruitmentContract.index')
                    ->with('error', 'لا يمكن إضافة عاملة لهذا العقد - الحالة الحالية: ' . $contract->status);
            }

            // جلب العاملات المتاحة مع الفلاتر
            $query = Maid::with(['nationality', 'religion', 'jobTitles'])
                ->where('available', true)
                ->whereIn('status', [Maid::STATUS_AVAILABLE['ar'], Maid::STATUS_AVAILABLE['en']])
                ->where('is_show', true)
                ->where('available_at', '<=', now()->toDateString())
                ->whereNotNull('name')
                ->where('name', '!=', '')
                ->whereDoesntHave('contracts', function($q) {
                    $q->whereIn('status', ['active', 'pending']);
                })
                ->whereDoesntHave('orders', function($q) {
                    $q->whereIn('status', ['pending', 'approved', 'in_progress']);
                });

            // تطبيق الفلاتر
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('passport_num', 'LIKE', "%{$search}%")
                      ->orWhere('id_number', 'LIKE', "%{$search}%");
                });
            }

            if ($request->filled('nationality_id')) {
                $query->where('nationality_id', $request->nationality_id);
            }

            if ($request->filled('religion_id')) {
                $query->where('religion_id', $request->religion_id);
            }

            if ($request->filled('job_title_id')) {
                $query->whereHas('jobTitles', function($q) use ($request) {
                    $q->where('job_title_id', $request->job_title_id);
                });
            }

            $availableMaids = $query->latest()->paginate(20)->withQueryString();

            // Log debug info
            \Log::info('نتائج البحث عن العاملات:', [
                'count' => $availableMaids->count(),
                'search' => $request->search,
                'nationality_id' => $request->nationality_id,
                'religion_id' => $request->religion_id,
                'job_title_id' => $request->job_title_id,
                'query' => $query->toSql(),
                'bindings' => $query->getBindings(),
            ]);

            // جلب قوائم الفلترة
            $nationalities = \App\Models\ingaz\Nationality::all();
            $religions = \App\Models\ingaz\Religion::all();
            $jobTitles = \App\Models\ingaz\JobTitle::all();



            return view('admin.ingaz.RecruitmentContract.select_maid', compact(
                'contract',
                'availableMaids',
                'nationalities',
                'religions',
                'jobTitles'
            ));

        } catch (\Exception $e) {
            \Log::error('Error in selectMaidForContract', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'contractId' => $contractId,
                'request' => $request->all()
            ]);

            return redirect()->route('RecruitmentContract.index')
                ->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    public function statistics(Request $request)
    {
        try {
            $query = Contract::where('type', 'recruitment');

            // فلتر الحالة
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // فلتر التاريخ من
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            // فلتر التاريخ إلى
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // فلتر رقم جوال العميل
            if ($request->filled('client_phone')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('phone', 'like', '%' . $request->client_phone . '%');
                });
            }

            // فلتر اسم العميل
            if ($request->filled('client_name')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->client_name . '%');
                });
            }

            // فلتر رقم جواز العاملة
            if ($request->filled('maid_passport')) {
                $query->whereHas('maid', function($q) use ($request) {
                    $q->where('passport_num', 'like', '%' . $request->maid_passport . '%');
                });
            }

            // فلتر اسم العاملة
            if ($request->filled('maid_name')) {
                $query->whereHas('maid', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->maid_name . '%');
                });
            }

            // فلتر رقم العقد
            if ($request->filled('contract_number')) {
                $query->where('id', 'like', '%' . $request->contract_number . '%');
            }

            // فلتر جنسية العاملة
            if ($request->filled('nationality')) {
                $query->whereHas('maid.nationality', function($q) use ($request) {
                    $q->where('sub_title', 'like', '%' . $request->nationality . '%');
                });
            }

            // فلتر ديانة العاملة
            if ($request->filled('religion')) {
                $query->whereHas('maid.religion', function($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->religion . '%');
                });
            }

            // فلتر مدينة العميل
            if ($request->filled('city')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('city', 'like', '%' . $request->city . '%');
                });
            }

            $total = $query->count();
            $active = $query->whereIn('status', [
                ContractStatusEnum::New->value,
                ContractStatusEnum::Msaned->value,
                ContractStatusEnum::Tafwed->value,
                ContractStatusEnum::Taffez->value,
                ContractStatusEnum::Tazker->value,
                ContractStatusEnum::Arrival->value
            ])->count();
            $pending = $query->where('status', ContractStatusEnum::PendingMaidSelection->value)->count();
            $completed = $query->where('status', ContractStatusEnum::Arrival->value)->count();
            $returned = $query->where('status', ContractStatusEnum::Back->value)->count();
            $closed = $query->where('status', ContractStatusEnum::Closed->value)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'active' => $active,
                    'pending' => $pending,
                    'completed' => $completed,
                    'returned' => $returned,
                    'closed' => $closed
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in contract statistics:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الإحصائيات'
            ], 500);
        }
    }
}

