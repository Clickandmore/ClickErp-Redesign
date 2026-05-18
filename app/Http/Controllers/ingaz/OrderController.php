<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\NotificationActionEnum;
use App\Enums\ingaz\OrderEnum;
use App\Http\Actions\ingaz\OrderAction as objAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Traits\SendSMSTrait;
use App\Http\Traits\StoreMessageTrait;
use App\Models\ingaz\JobTitle;
use App\Models\ingaz\Maid;
use App\Models\ingaz\MaidJobTitles;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Notification;
use App\Models\ingaz\Order;
use App\Models\ingaz\Order as objModel;
use App\Models\ingaz\OrderTransfers;
use App\Models\ingaz\Religion;
use App\Models\ingaz\User;
use App\Models\ingaz\UserAddress;
use App\Models\Contract;
use App\Models\MaidUnavailableRecord;
use App\Support\AutomationSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class OrderController extends Controller
{
    use SendSMSTrait, StoreMessageTrait;
    public $folderPath = "admin.ingaz.orders";
    public $postData = ["status"];
    public $mainRoute = "orders";

    public function __construct()
    {
        // $this->middleware('permission:الطلبات-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:الطلبات-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:الطلبات-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:الطلبات-حذف,admin', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            \Log::info('Orders AJAX request received', [
                'request_data' => $request->all(),
                'user_id' => auth('admin')->id()
            ]);

            $query = objModel::query();

            // Eager load relationships to avoid N+1 queries
            $query->with([
                'user',
                'maid.nationality',
                'maid.office',
                'jobType',
                'details.admin',
                'customer',
                'contracts'
            ]);

            // Order by created_at desc to show newest orders first
            $query->orderBy('created_at', 'desc');

            // Filter for recruitment orders only (job_type_id = 1)
            $query->where('job_type_id', 1);

            // Apply status filter
            if ($request->has('status') && $request->status != '') {
                $statusFilter = $this->getSelectedStatus($request->status);
                $query->whereIn('status', $statusFilter);
            }

            // Apply date range filter
            if ($request->filled('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // Apply job type filter
            if ($request->filled('job_type')) {
                $query->where('job_type_id', $request->job_type);
            }

            // Apply admin filter
            if ($request->filled('admin_id')) {
                $query->whereResponsibleAdmin((int) $request->admin_id);
            }

            // Apply client name search
            if ($request->filled('client_name')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->client_name . '%');
                });
            }

            // Apply phone search
            if ($request->filled('phone')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('phone', 'like', '%' . $request->phone . '%')
                        ->orWhere('phone_code', 'like', '%' . $request->phone . '%');
                });
            }

            // Apply passport number search
            if ($request->filled('passport_num')) {
                $query->whereHas('maid', function ($q) use ($request) {
                    $q->where('passport_num', 'like', '%' . $request->passport_num . '%');
                });
            }

            if ($request->filled('request_type')) {
                $query->whereHas('maid', function ($q) use ($request) {
                    $q->ofRequestType($request->request_type);
                });
            }

            // Apply nationality filter
            if ($request->filled('nationality_id')) {
                $query->whereHas('maid', function ($q) use ($request) {
                    $q->where('nationality_id', $request->nationality_id);
                });
            }

            // Apply recruitment office name search
            if ($request->filled('office_name')) {
                $officeName = trim($request->office_name);
                $query->whereHas('maid.office.translations', function ($q) use ($officeName) {
                    $q->where('title', 'like', '%' . $officeName . '%')
                        ->where('locale', app()->getLocale());
                });
            }

            // Apply timer status filter
            if ($request->filled('timer_status')) {
                $urgentThresholdHours = AutomationSettings::orderUrgentThresholdHours();
                $query->where(function ($q) use ($request, $urgentThresholdHours) {
                    switch ($request->timer_status) {
                        case 'overdue':
                            $q->where('deadline_at', '<', now());
                            break;
                        case 'urgent':
                            $q->where('deadline_at', '>', now())
                                ->where('deadline_at', '<', now()->addHours($urgentThresholdHours));
                            break;
                        case 'normal':
                            $q->where('deadline_at', '>', now()->addHours($urgentThresholdHours));
                            break;
                    }
                });
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('admin_name', function ($row) {
                    return $row->resolveResponsibleAdmin()?->name ?? '-';
                })
                ->addColumn('created_at', function ($row) {
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->editColumn('status', function ($row) {
                    $statusHtml = $this->getStatusHtml($row->status);

                    if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_EDIT)) {
                        $statusHtml .= ' <button type="button" class="edit-status" data-id="' . $row->id . '" title="تعديل الحالة">
                                <i class="fa fa-pen"></i>
                            </button>';
                    }

                    return '<div class="d-flex align-items-center gap-2">' . $statusHtml . '</div>';
                })
                ->addColumn('timer', function ($row) {
                    $office = $row->maid?->office ?? $row->maid?->recruitmentOffice;
                    $officeTitle = $office?->title ?? '-';

                    return '<div>'. $this->getTimerHtml($row) .' <div class="text-muted text-medium mt-2 d-flex align-items-center gap-2 p-0">'
                        . '<i class="fas fa-building text-secondary ps-1"></i>' . __('ingaz.recruitment_office') . ": " . $officeTitle
                        . '</div></div>';
                })
                ->addColumn('recruitment_office', function ($row) {
                    $office = $row->maid?->office ?? $row->maid?->recruitmentOffice;
                    return $office?->title ?? '-';
                })
                ->editColumn('job_type_id', function ($row) {
                    return ($row->jobType) ? $row->jobType->title : '';
                })
                ->editColumn('user_id', function ($row) {
                    return '<span class="client-name text-nowrap">' . (($row->user) ? $row->user->name : '-') . '</span>';
                })
                ->editColumn('phone', function ($row) {
                    return '<span class="phone-number">' . (($row->user) ? getPhoneWithTag($row->user->phone_code . $row->user->phone) : '-') . '</span>';
                })
                ->editColumn('maid_id', function ($row) {
                    return ($row->maid) ? getImgTag($row->maid->cv_file, "50px", "50px", "maid-image") : '<span class="text-muted">-</span>';
                })
                ->editColumn('nationality_name', function ($row) {
                    return ($row->maid) ? @$row->maid->nationality->sub_title : '';
                })
                ->editColumn('maid_passport_num', function ($row) {
                    if (!$row->maid) {
                        return '';
                    }

                    return '<div class="d-flex flex-column gap-1">'
                        . '<span>' . e($row->maid->passport_num) . '</span>'
                        . '<small class="text-muted">' . helperTrans('ingaz.request_type') . ': ' . e($row->maid->request_type_label) . '</small>'
                        . '</div>';
                })
                ->editColumn('deliver_status', function ($row) {
                    if (@$row->details->deliver_maid == '1') {
                        return '<span class="badge bg-success status-badge">' . __('web.yes') . '</span>';
                    } else {
                        return '<span class="badge bg-danger status-badge">' . __('web.no') . '</span>';
                    }
                })
                ->addColumn('contract_status', function ($row) {
                    if ($row->contracts()->exists()) {
                        return '<span class="badge bg-info status-badge">' . __('ingaz.has_contract') . '</span>';
                    }
                    return '<span class="badge bg-danger status-badge">' . __('ingaz.no_contract') . '</span>';
                })
                ->editColumn('maid_photo', function ($row) {
                    $rawPath = $row?->maid?->image ?: ($row?->maid?->cv_preview ?: $row?->maid?->cv_file);
                    $imageUrl = $rawPath ? showFile($rawPath) : asset('admin/assets/img/avatar.png');
                    return '<img src="' . $imageUrl . '" alt="maid-avatar" class="border" style="width:65px;height:80px;object-fit:cover;border-radius: 5px;">';
                })
                ->editColumn('created_at', function ($row) {
                    return '<span class="text-muted">' . $row->created_at->format('Y-m-d H:i') . '</span>';
                })
                ->editColumn('admin_name', function ($row) {
                    return '<span class="text-info">' . ($row->resolveResponsibleAdmin()?->name ?? '-') . '</span>';
                })
                ->addColumn('actions', function ($row) {
                    $isCompletedOrder = $this->isCompletedOrder($row);

                    $actions = ' <div class="dropdown actions-dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" aria-expanded="false" title="الإجراءات المتاحة">
                                        <i class="fas fa-cogs me-1"></i> الإجراءات
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg top-0">';

                    // View button
                    if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_VIEW)) {
                        $actions .= '<li><a class="dropdown-item" href="' . route('showSingleOrder', ['orderId' => $row->id]) . '">
                            <i class="fa fa-eye"></i> عرض التفاصيل
                        </a></li>';
                    }


                    // Edit status button (show for all orders)
                    if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_EDIT)) {
                        $actions .= '<li><button class="dropdown-item edit-status" data-id="' . $row->id . '">
                            <i class="fa fa-edit"></i> تعديل الحالة
                        </button></li>';
                    }

                    // Add Contract button only for "under process" orders
                    if ($row->status == OrderEnum::UnderProcess->value) {
                        $actions .= '<li><button class="dropdown-item addNewContract" data-id="' . $row->id . '">
                            <i class="fa fa-plus"></i> إضافة عقد
                        </button></li>';
                    }

                    // Change Maid button for new orders
                    if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_EDIT) && $row->status == OrderEnum::New->value) {
                        $actions .= '<li><button class="dropdown-item change-maid" data-id="' . $row->id . '">
                            <i class="fa fa-user"></i> تغيير العاملة
                        </button></li>';
                    }

                    // Change Responsible Employee button
                    if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_EDIT) && !$isCompletedOrder) {
                        $actions .= '<li><button class="dropdown-item change-responsible" data-id="' . $row->id . '">
                            <i class="fa fa-user-secret"></i> تغيير الموظف المسؤول
                        </button></li>';
                    }

                    // Refuse/Return button for new and under_process orders
                    if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_EDIT) && !$isCompletedOrder) {
                        if (in_array($row->status, [OrderEnum::New->value, OrderEnum::UnderProcess->value])) {
                            $actions .= '<li><button class="dropdown-item refuse-order" data-id="' . $row->id . '">
                                <i class="fa fa-times"></i> رفض/إرجاع الطلب
                            </button></li>';
                        }
                    }

                    // Delete button
                    if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_DELETE) && !$isCompletedOrder) {
                        $actions .= '<li><hr class="dropdown-divider"></li>
                        <li><button class="dropdown-item delete-order" data-id="' . $row->id . '">
                            <i class="fa fa-trash"></i> حذف الطلب
                        </button></li>';
                    }

                    $actions .= '</ul></div>';
                    return $actions;
                })
                ->escapeColumns([])
                ->rawColumns(['actions', 'status', 'timer', 'user_id', 'phone', 'maid_id', 'maid_passport_num', 'deliver_status', 'contract_status', 'order_code', 'created_at', 'admin_name'])
                ->make(true);
        }

        // Preload data for the main page to avoid multiple queries
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["modalType"] = "modal-lg";

        // Cache filter data for 5 minutes to avoid repeated queries
        $data['jobTypes'] = \Cache::remember('job_types_list', 300, function () {
            return JobTitle::with('translations')->get();
        });

        $data['nationalities'] = \Cache::remember('nationalities_list', 300, function () {
            return Nationality::with('translations')->get()->sortBy('title');
        });

        $data['admins'] = \Cache::remember('admins_list', 300, function () {
            return \App\Models\auth\Admin::select('id', 'name')->whereNull('deleted_at')->get();
        });

        $completedStatuses = [
            OrderEnum::Completed->value,
            OrderEnum::Done->value,
            OrderEnum::Contracted->value,
            OrderEnum::ContractedWithoutContract->value,
        ];

        $notCompletedStatuses = [
            OrderEnum::Refused->value,
            OrderEnum::Return->value,
            OrderEnum::CanceledBySystem->value,
        ];

        // Get statistics with optimized queries (cache for 1 minute) - Recruitment orders only
        $data['totalOrders'] = \Cache::remember('recruitment_orders_count', 60, function () {
            return objModel::where('job_type_id', 1)->count();
        });

        $data['newOrders'] = \Cache::remember('recruitment_new_orders_count', 60, function () {
            return objModel::where('job_type_id', 1)->where('status', OrderEnum::New->value)->count();
        });

        $data['underProcessOrders'] = \Cache::remember('recruitment_under_process_orders_count', 60, function () {
            return objModel::where('job_type_id', 1)->where('status', OrderEnum::UnderProcess->value)->count();
        });

        $data['completedOrders'] = \Cache::remember('recruitment_completed_orders_count', 60, function () use ($completedStatuses) {
            return objModel::where('job_type_id', 1)->whereIn('status', $completedStatuses)->count();
        });

        $data['notCompletedOrders'] = \Cache::remember('recruitment_not_completed_orders_count', 60, function () use ($notCompletedStatuses) {
            return objModel::where('job_type_id', 1)->whereIn('status', $notCompletedStatuses)->count();
        });

        return view($this->folderPath . '.index', $data);
    }

    public function quickRecruitment(Request $request)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::ORDERS_CREATE)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية إنشاء طلب سريع.'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
        ], [
            'name.required' => 'اسم العميل مطلوب.',
            'phone.required' => 'رقم الجوال مطلوب.',
        ]);

        $normalizedPhone = $this->normalizeSaudiPhone($validated['phone']);
        if (!$normalizedPhone) {
            return response()->json([
                'success' => false,
                'message' => 'رقم الجوال غير صحيح. الصيغة المتاحة: 05XXXXXXXX أو 5XXXXXXXX أو +9665XXXXXXXX.',
            ], 422);
        }

        $defaultPassword = '123456';
        $user = User::where('phone', $normalizedPhone)->first();
        $isNewClient = false;

        if (!$user) {
            $user = User::create([
                'name' => $validated['name'],
                'phone_code' => '+966',
                'phone' => $normalizedPhone,
                'password' => bcrypt($defaultPassword),
                'register_from' => 'dashboard',
                'is_blocked' => 0,
            ]);
            $isNewClient = true;
        }

        return response()->json([
            'success' => true,
            'message' => $isNewClient
                ? 'تم إنشاء العميل وتحويلك لاختيار السيرة الذاتية.'
                : 'تم العثور على عميل بنفس رقم الجوال وتحويلك لاختيار السيرة الذاتية.',
            'client_id' => $user->id,
            'redirect_url' => route('admin.recruitment.select.maid', ['client_id' => $user->id]),
            'default_password_set' => $isNewClient,
        ]);
    }

    private function getSelectedStatus($status)
    {
        return match ($status) {
            'current' => [OrderEnum::New->value, OrderEnum::UnderProcess->value],
            'ended', 'completed' => [
                OrderEnum::Completed->value,
                OrderEnum::Done->value,
                OrderEnum::Contracted->value,
                OrderEnum::ContractedWithoutContract->value,
            ],
            'not_completed' => [OrderEnum::Refused->value, OrderEnum::Return->value, OrderEnum::CanceledBySystem->value],
            default => OrderEnum::tryFrom($status)
                ? [$status]
                : array_map(fn($case) => $case->value, OrderEnum::cases()),
        };
    }

    private function normalizeSaudiPhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (!$digits) {
            return null;
        }

        if (str_starts_with($digits, '00966')) {
            $digits = substr($digits, 5);
        } elseif (str_starts_with($digits, '966')) {
            $digits = substr($digits, 3);
        }

        if (str_starts_with($digits, '05')) {
            $digits = substr($digits, 1);
        }

        return preg_match('/^5\d{8}$/', $digits) ? $digits : null;
    }

    private function isCompletedOrder(Order $order): bool
    {
        return in_array($order->status, [
            OrderEnum::Completed->value,
            OrderEnum::Done->value,
        ], true);
    }

    private function completedOrderLockedResponse()
    {
        return jsonError('لا يمكن تعديل المسؤول أو حذف أو إلغاء الطلب المكتمل.', 403);
    }

    public function edit(Request $request, $id)
    {
        if ($request->ajax()) {
            try {
                $obj = Order::with([
                    'user',
                    'maid',
                    'jobType',
                    'details.admin',
                    'details.package',
                    'details.address'
                ])->findOrFail($id);

                $returnHTML = view($this->folderPath . '.edit')
                    ->with(['obj' => $obj])->render();
                return response()->json([
                    'success' => true,
                    'html' => $returnHTML
                ]);
            } catch (\Exception $e) {
                \Log::error('Error loading edit form', [
                    'order_id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء تحميل نموذج التعديل: ' . $e->getMessage()
                ]);
            }
        }
    }

    public function update(request $request, objAction $action, $id)
    {
        try {
            $validated = $request->validate([
                'status' => ['required', Rule::in(array_map(fn($case) => $case->value, OrderEnum::cases()))],
                'refuse_reason' => ['nullable', 'string', 'max:500'],
            ]);

            $order = Order::findOrFail($id);
            $newStatus = $validated['status'];

            if ($this->isCompletedOrder($order) && $newStatus !== $order->status) {
                return $this->completedOrderLockedResponse();
            }

            \Log::info('Update order request', [
                'order_id' => $id,
                'new_status' => $newStatus,
                'payload' => $validated,
            ]);

            $order->status = $newStatus;
            $order->status_changed_at = now();

            $order->deadline_at = $order->getDeadlineHours() > 0
                ? now()->addHours($order->getDeadlineHours())
                : null;

            if ($this->isRefusalStatus($newStatus)) {
                $order->refuse_reason = $this->resolveRefuseReason($validated['refuse_reason'] ?? null, $newStatus);
            } else {
                $order->refuse_reason = null;
            }

            $order->save();

            // Status Notification
            Notification::create([
                'title' => __('ingaz.update_order_status'),
                'description' => __('ingaz.order_status_updated', ['order_id' => $order->id]) . " " . __('ingaz.' . $order->status),
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'type' => 'order_status',
            ]);

            if ($this->isRefusalStatus($newStatus)) {
                $this->releaseMaid($order);
            }

            \Log::info('Order updated successfully', [
                'order_id' => $id,
                'new_status' => $newStatus
            ]);

            return jsonSuccess(['success' => true], __('ingaz.order_updated'), 200);
        } catch (\Illuminate\Validation\ValidationException $validationException) {
            $messages = collect($validationException->errors())->flatten();
            return jsonError([
                'message' => $messages->first() ?? $validationException->getMessage(),
                'errors' => $validationException->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error updating order status', [
                'order_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return jsonError(['success' => false], __('ingaz.update_error'), 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $order = Order::withTrashed()->findOrFail($id);
        if (!$order) {
            return jsonError(__('index.order_not_found'), 404);
        }
        if ($this->isCompletedOrder($order)) {
            return $this->completedOrderLockedResponse();
        }
        if ($order->trashed()) {
            $order->forceDelete();
        } else {
            $order->delete();
        }
        return jsonSuccess(['success' => true], __("ingaz.order_deleted_successfully"), 200);
    }

    public function restore($id)
    {
        try {
            $order = Order::withTrashed()->findOrFail($id);
            $order->restore();

            // Restore Notification
            Notification::create([
                'title' => __('ingaz.update_order_restored'),
                'description' => __('ingaz.order_restored_msg', ['order_id' => $order->id]),
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'type' => 'order_status',
            ]);

            return jsonSuccess(['success' => 'تم استعادة الطلب بنجاح']);
        } catch (\Exception $e) {
            return jsonError(['success' => 'حدث خطأ أثناء استعادة الطلب']);
        }
    }

    public function getStatusHtml($status)
    {
        $arabicTranslation = getEnumValue('orders_statuses', $status);

        if ($arabicTranslation !== "undefined") {
            $colorClass = $this->getStatusColor($status);
            return '<span class="badge bg-' . $colorClass . ' status-badge" style="width:fit-content;">' . $arabicTranslation . '</span>';
        }

        return '<span class="text-muted">غير معروف</span>';
    }

    public function getTimerHtml($order)
    {
        if (!$order instanceof Order) {
            $order = Order::find($order);
            if (!$order) {
                return '<span class="text-muted">-</span>';
            }
        }

        return $this->renderTimerChip($order);
    }

    public function showDataOfOrder(request $request)
    {
        $request->validate([
            'orderId' => 'required|integer|exists:orders,id'
        ]);

        $order = Order::with([
            'user',
            'maid.nationality',
            'maid.religion',
            'maid.jobTitles',
            'jobType',
            'details.admin',
            'customer',
            'address'
        ])->findOrFail($request->orderId);

        $maid = $order->maid;
        $user = $order->user;
        $address = $order->address;

        $maidJobs = $maid?->jobTitles
            ? $maid->jobTitles->pluck('title')->filter()->unique()->implode('، ')
            : null;

        $data = [
            'order' => [
                'id' => $order->id,
                'code' => $order->order_code,
                'status' => $order->status,
                'status_label' => getEnumValue('orders_statuses', $order->status),
                'status_color' => $this->getStatusColor($order->status),
                'job_type' => $order->jobType->title ?? __('ingaz.not_specified'),
                'created_at' => optional($order->created_at)->format('Y-m-d H:i'),
                'deadline_at' => $order->deadline_at ? $order->deadline_at->format('Y-m-d H:i') : null,
                'admin' => optional($order->resolveResponsibleAdmin())->name,
                'timer' => $this->buildTimerMeta($order),
                'timer_html' => $this->renderTimerChip($order),
            ],
            'user' => [
                'name' => $user?->name,
                'phone' => $user ? getPhoneWithTag($user->phone_code . $user->phone) : null,
                'raw_phone' => $user ? $user->phone_code . $user->phone : null,
                'email' => $user?->email,
                'address' => $address?->address,
                'city' => $address?->title,
                'id_number' => $user?->id_number,
            ],
            'maid' => [
                'id' => $maid?->id,
                'name' => $maid?->name,
                'passport' => $maid?->passport_num,
                'nationality' => $maid?->nationality?->title,
                'religion' => $maid?->religion?->title,
                'job_titles' => $maidJobs,
                'status' => $this->translateMaidStatus($maid?->status),
                'photo' => $this->getMaidPhoto($maid),
            ],
        ];

        return jsonSuccess(["data" => $data]);
    }

    public function showDataOfMaid(request $request)
    {
        // adjust maid data
        $data['maid'] = Maid::find($request->maidId);
        $data['maid']['nationality'] = Nationality::find($data['maid']->nationality_id)->title;
        $data['maid']['religion'] = Religion::find($data['maid']->religion_id)->title;
        $jobsIDS = MaidJobTitles::where('maid_id', $data['maid']->id)->pluck('job_title_id')->toArray();
        $JobTitles = JobTitle::whereIn('id', $jobsIDS)->latest()->get();
        foreach ($JobTitles as $job) {
            $data['maid']['job'] = $data['maid']['job'] . " " . $job->title;
        }

        return jsonSuccess(["data" => $data]);
    }

    public function showUserFromContract(request $request)
    {
        // adjust user data
        $data['user'] = User::find($request->contractId);
        $address = UserAddress::where('user_id', $data['user']->id)->latest()->first();
        $data['user']['address'] = $address ? $address->address . ' - ' . $address->title : null;
        return jsonSuccess(["data" => $data]);
    }

    public function showSingleOrder($orderId)
    {
        $data = Order::withTrashed()->with([
            'user.nationality',
            'maid.nationality',
            'maid.religion',
            'maid.jobTitles',
            'jobType',
            'details.admin',
            'customer',
            'details.package',
            'details.address',
            'transfers',
            'contracts'
        ])->find($orderId);

        if (!$data) {
            abort(404);
        }

        $activities = $this->buildOrderActivitiesTimeline($data);

        return view('admin.ingaz.orders.show', compact('data', 'activities'));
    }

    public function show($orderId)
    {
        $data = Order::withTrashed()->with([
            'user.nationality',
            'maid.nationality',
            'maid.religion',
            'maid.jobTitles',
            'jobType',
            'details.admin',
            'customer',
            'details.package',
            'details.address',
            'transfers',
            'contracts'
        ])->find($orderId);

        if (!$data) {
            abort(404);
        }

        $activities = $this->buildOrderActivitiesTimeline($data);

        return view('admin.ingaz.orders.show', compact('data', 'activities'));
    }

    private function buildOrderActivitiesTimeline(Order $order)
    {
        $activities = collect();

        if (method_exists($order, 'activities')) {
            $activities = $activities->merge($order->activities()->with('causer')->latest()->get());
        }

        if ($order->details && method_exists($order->details, 'activities')) {
            $activities = $activities->merge($order->details->activities()->with('causer')->latest()->get());
        }

        foreach ($order->transfers as $transfer) {
            if (method_exists($transfer, 'activities')) {
                $activities = $activities->merge($transfer->activities()->with('causer')->latest()->get());
            }
        }

        return $this->buildActivityTimeline(
            $activities->sortByDesc('created_at')->take(15)->values(),
            [
                'status' => 'حالة الطلب',
                'maid_id' => 'العاملة',
                'customer_id' => 'الموظف المسؤول',
                'user_id' => 'العميل',
                'job_type_id' => 'نوع الوظيفة',
                'payment_method' => 'طريقة الدفع',
                'refuse_reason' => 'سبب الرفض أو الإرجاع',
                'status_changed_at' => 'وقت تغيير الحالة',
                'deadline_at' => 'الموعد النهائي',
                'admin_id' => 'الموظف المسؤول',
                'package_id' => 'الباقة',
                'address_id' => 'العنوان',
                'deliver_maid' => 'تسليم العاملة',
                'name' => 'الاسم',
                'phone' => 'رقم الهاتف',
                'address' => 'العنوان',
                'notes' => 'الملاحظات',
                'file' => 'الملف',
            ],
            'الطلب'
        );
    }

    public function download($id)
    {
        $transfer = OrderTransfers::find($id); // Corrected the namespace

        if (!$transfer) {
            abort(404);
        }

        $filePath = storage_path('app/public/' . $transfer->file); // Adjusted the path for the 'public' disk

        if (!file_exists($filePath)) {
            abort(404);
        }

        return response()->download($filePath, basename($transfer->file));
    }

    public function completeOrder(Request $request, $id)
    {
        $order = objModel::find($id);
        if ($order) {
            return jsonSuccess($order);
        }
        return jsonError(['error' => 'Order not Found!']);
    }

    public function getOrderById(Request $request, $id)
    {
        $order = objModel::with('user')->where('id', $id)->first();
        if ($order) {
            return jsonSuccess($order);
        }
        return jsonError(['error' => 'Order not Found!']);
    }

    public function changeMaid(Request $request, $id)
    {
        try {
            $order = Order::findOrFail($id);

            // Validate input
            $request->validate([
                'maid_id' => 'required|exists:maids,id',
                'old_maid_status' => 'required|in:available,backout',
                'reason' => 'required_if:old_maid_status,backout|nullable|string|max:1000'
            ]);

            $newMaidId = $request->maid_id;
            $oldMaidStatus = $request->old_maid_status;
            $reason = $request->reason;

            // Check if new maid is available
            $newMaid = \App\Models\ingaz\Maid::findOrFail($newMaidId);

            if (!$newMaid->available || $newMaid->status !== 'available') {
                return response()->json([
                    'success' => false,
                    'message' => 'العاملة المختارة غير متاحة حالياً'
                ], 400);
            }

            $hasActiveContract = Contract::where('maid_id', $newMaidId)
                ->whereIn('status', [
                    'pending_maid_selection',
                    'new',
                    'msaned',
                    'tafwed',
                    'taffez',
                    'tazker',
                    'arrival',
                ])
                ->exists();

            if ($hasActiveContract) {
                return response()->json([
                    'success' => false,
                    'message' => 'العاملة المختارة لها عقد نشط ولا يمكن استخدامها'
                ], 400);
            }

            // Check if maid is already assigned to another active order
            $existingOrder = Order::where('maid_id', $newMaidId)
                ->where('id', '!=', $id)
                ->whereNotIn('status', ['completed', 'refused', 'done'])
                ->first();

            if ($existingOrder) {
                return response()->json([
                    'success' => false,
                    'message' => 'العاملة المختارة محجوزة لطلب آخر'
                ], 400);
            }

            $oldMaid = $order->maid;
            if (!$oldMaid) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا توجد عاملة حالية مرتبطة بهذا الطلب'
                ], 400);
            }

            \DB::beginTransaction();

            try {
                // Save old maid info
                $oldMaidId = $oldMaid->id;

                \Log::info('Changing maid in order', [
                    'order_id' => $id,
                    'old_maid_id' => $oldMaidId,
                    'new_maid_id' => $newMaidId,
                    'old_maid_status' => $oldMaidStatus,
                    'reason' => $reason
                ]);

                // Update order with new maid
                $order->update([
                    'maid_id' => $newMaidId
                ]);

                // Update new maid status to reserved
                $newMaid->update([
                    'status' => 'reserved',
                    'available' => false
                ]);

                if ($oldMaidStatus === 'backout') {
                    $oldMaid->update([
                        'status' => 'backout',
                        'available' => false,
                        'back_out_reason' => $reason,
                    ]);

                    MaidUnavailableRecord::create([
                        'maid_id' => $oldMaidId,
                        'reason' => $reason,
                        'admin_id' => auth()->guard('admin')->id() ?: auth()->id(),
                    ]);
                } else {
                    $oldMaidHasActiveOrder = Order::where('maid_id', $oldMaidId)
                        ->whereIn('status', [
                            OrderEnum::New->value,
                            OrderEnum::UnderProcess->value,
                            OrderEnum::Contracted->value,
                            OrderEnum::ContractedWithoutContract->value,
                        ])
                        ->exists();

                    $oldMaidHasActiveContract = Contract::where('maid_id', $oldMaidId)
                        ->whereIn('status', [
                            'pending_maid_selection',
                            'new',
                            'msaned',
                            'tafwed',
                            'taffez',
                            'tazker',
                            'arrival',
                        ])
                        ->exists();

                    $oldMaid->update([
                        'status' => ($oldMaidHasActiveOrder || $oldMaidHasActiveContract) ? 'reserved' : 'available',
                        'available' => !($oldMaidHasActiveOrder || $oldMaidHasActiveContract),
                        'back_out_reason' => null,
                    ]);
                }

                \Log::info('Maid changed in order successfully', [
                    'order_id' => $id,
                    'old_maid_id' => $oldMaidId,
                    'new_maid_id' => $newMaidId,
                    'old_maid_new_status' => $oldMaid->status,
                    'new_maid_status' => $newMaid->status
                ]);

                \DB::commit();

                // Change Maid Notification
                Notification::create([
                    'title' => __('ingaz.update_order_change_maid'),
                    'description' => __('ingaz.order_changed_maid_msg', ['order_id' => $order->id]),
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'type' => 'order_status',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم تغيير العاملة بنجاح',
                    'data' => [
                        'order_id' => $id,
                        'old_maid' => [
                            'id' => $oldMaid->id,
                            'name' => $oldMaid->name,
                            'status' => $oldMaid->status,
                            'available' => $oldMaid->available
                        ],
                        'new_maid' => [
                            'id' => $newMaid->id,
                            'name' => $newMaid->name,
                            'status' => $newMaid->status,
                            'available' => $newMaid->available
                        ]
                    ]
                ]);
            } catch (\Exception $e) {
                \DB::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            return jsonError('حدث خطأ أثناء تغيير العاملة', 500);
        }
    }

    public function changeResponsible(Request $request, $id = null)
    {
        try {
            // Get order ID from either route parameter or request
            $orderId = $id ?? $request->order_id;
            $order = Order::findOrFail($orderId);

            if ($this->isCompletedOrder($order)) {
                return $this->completedOrderLockedResponse();
            }

            // Get customer service admins only
            $admin = \App\Models\auth\Admin::where('id', $request->admin_id)
                ->where('admin_type', 'customer_service')
                ->where(function ($query) {
                    $query->whereNull('status')->orWhere('status', 'active');
                })
                ->first();

            if (!$admin) {
                return jsonError(['message' => 'You can only select a customer service employee']);
            }

            $order->syncResponsibleAdmin((int) $request->admin_id);

            // Change Responsible Employee Notification
            Notification::create([
                'title' => __('ingaz.update_order_change_responsible'),
                'description' => __('ingaz.order_changed_responsible_msg', ['order_id' => $order->id]),
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'type' => 'order_status',
            ]);

            return jsonSuccess(['success' => true], __('ingaz.responsible_updated'), 200);
        } catch (\Exception $e) {
            return jsonError(['message' => 'An error occurred while updating the responsible employee']);
        }
    }

    public function refuseReturn(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'action' => ['required', Rule::in([OrderEnum::Refused->value, OrderEnum::Return->value])],
                'reason' => ['nullable', 'string', 'max:500'],
            ]);

            $order = Order::findOrFail($id);
            if ($this->isCompletedOrder($order)) {
                return $this->completedOrderLockedResponse();
            }

            $order->status = $validated['action'];
            $order->status_changed_at = now();
            $order->refuse_reason = $this->resolveRefuseReason($validated['reason'] ?? null, $validated['action']);
            $order->save();

            // Send SMS
            if ($validated['action'] == "refused") {
                $body = "عزيزي العميل، تم رفض طلب الاستقدام رقم {$order->order_code}. لمزيد من التفاصيل يرجى التواصل مع خدمة العملاء.";
            } else if ($validated['action'] == "return") {
                $body = "عزيزي العميل، نود إشعاركم بأنه تم إرجاع طلب الاستقدام رقم {$order->order_code}. يرجى مراجعة الطلب عبر حسابكم.";
            }
            $userPhone = formatSaudiPhone($order->user->phone);
            $customerPhone = $order->customer?->formatted_verification_phone;
            $recipients = array_values(array_filter([$userPhone, $customerPhone]));
            $result = !empty($recipients) ? $this->sendSMS($recipients, $body) : null;

            if (isset($result['code']) && $result['code'] == "1") {
                $this->storeMessage('ارسال رسالة نصية الى العميل', $body, NotificationActionEnum::SMS->value, $order->user->id);
            }


            // Refuse/Return Notification
            Notification::create([
                'title' => __('ingaz.update_order_status'),
                'description' => __('ingaz.order_status_updated', ['order_id' => $order->id]) . " " . __('ingaz.' . $order->status),
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'type' => 'order_status',
            ]);

            $this->releaseMaid($order);

            return jsonSuccess(['success' => true], __('ingaz.status_updated'), 200);
        } catch (\Exception $e) {
            return jsonError(['message' => 'An error occurred while updating the order status']);
        }
    }

    private function isRefusalStatus(string $status): bool
    {
        return in_array($status, [OrderEnum::Refused->value, OrderEnum::Return->value], true);
    }

    private function resolveRefuseReason(?string $reason, string $status): string
    {
        $trimmed = trim((string) $reason);
        if ($trimmed !== '') {
            return $trimmed;
        }

        return $status === OrderEnum::Return->value
            ? __('ingaz.auto_return_reason')
            : __('ingaz.auto_refuse_reason');
    }

    private function releaseMaid(Order $order): void
    {
        $order->loadMissing('maid');

        if (!$order->maid) {
            return;
        }

        $maidId = $order->maid->id;

        $hasOtherActiveOrders = Order::where('maid_id', $maidId)
            ->where('id', '!=', $order->id)
            ->whereNotIn('status', [
                OrderEnum::Completed->value,
                OrderEnum::Done->value,
                OrderEnum::Refused->value,
                OrderEnum::Return->value,
            ])->exists();

        if ($hasOtherActiveOrders) {
            \Log::info('Maid not released due to other active orders', [
                'maid_id' => $maidId,
                'order_id' => $order->id,
            ]);
            return;
        }

        $order->maid->update([
            'status' => 'available',
            'available' => true,
            'available_at' => now(),
        ]);

        \Log::info('Maid released after order refusal/return', [
            'maid_id' => $maidId,
            'order_id' => $order->id,
        ]);
    }

    private function getStatusColor(string $status): string
    {
        $colorMap = [
            'new' => 'primary',
            'paid' => 'success',
            'completed' => 'success',
            'started' => 'success',
            'done' => 'success',
            'refused' => 'danger',
            'msaned' => 'danger',
            'training' => 'danger',
            'arrived' => 'danger',
            'escape' => 'danger',
            'deportation' => 'danger',
            'delivered' => 'danger',
            'under_process' => 'warning',
            'contracted' => 'info',
            'contracted_without_contract' => 'info',
        ];

        return $colorMap[$status] ?? 'secondary';
    }

    private function buildTimerMeta(Order $order): array
    {
        $remaining = $order->getRemainingTime();
        $state = $order->getTimerStatus();
        $stateLabels = [
            'overdue' => __('ingaz.timer_expired'),
            'urgent' => __('ingaz.timer_urgent'),
            'normal' => __('ingaz.timer_normal'),
            'no_deadline' => __('ingaz.no_deadline'),
        ];

        $deadlineFormatted = $order->deadline_at ? $order->deadline_at->format('Y-m-d H:i') : null;
        $phaseLabel = $this->getTimerPhaseLabel($order->status);

        if (!$remaining) {
            return [
                'has_deadline' => false,
                'phase_label' => $phaseLabel,
                'state' => 'no_deadline',
                'state_label' => $stateLabels['no_deadline'],
                'remaining_pretty' => __('ingaz.no_deadline'),
                'deadline_formatted' => $deadlineFormatted,
            ];
        }

        $remainingPretty = $remaining['is_overdue']
            ? '-' . $remaining['overdue_hours'] . 'س ' . $remaining['overdue_minutes'] . 'د'
            : $remaining['hours'] . 'س ' . str_pad($remaining['minutes'], 2, '0', STR_PAD_LEFT) . 'د';

        return [
            'has_deadline' => true,
            'phase_label' => $phaseLabel,
            'state' => $state,
            'state_label' => $stateLabels[$state] ?? $stateLabels['normal'],
            'remaining_pretty' => $remainingPretty,
            'deadline_formatted' => $deadlineFormatted,
            'is_overdue' => $remaining['is_overdue'],
        ];
    }

    private function renderTimerChip(Order $order): string
    {
        $meta = $this->buildTimerMeta($order);

        if (!$meta['has_deadline']) {
            return '<div class="timer-chip timer-chip--muted"><span class="timer-chip__value text-nowrap">' . e($meta['remaining_pretty']) . '</span></div>';
        }

        $stateClass = match ($meta['state']) {
            'overdue' => 'danger',
            'urgent' => 'warning',
            'normal' => 'info',
            default => 'muted',
        };

        $hintParts = array_filter([$meta['state_label'], $meta['deadline_formatted'] ? __('ingaz.deadline') . ': ' . $meta['deadline_formatted'] : null]);
        $hintText = implode(' · ', $hintParts);

        return '<div class="timer-chip timer-chip--' . $stateClass . '">
                    <div class="timer-chip__phase">' . e($meta['phase_label']) . '</div>
                    <div class="timer-chip__value">' . e($meta['remaining_pretty']) . '</div>
                    <small class="timer-chip__hint">' . e($hintText) . '</small>
                </div>';
    }

    private function getTimerPhaseLabel(?string $status): string
    {
        return match ($status) {
            OrderEnum::New->value => __('ingaz.timer_phase_new_base') . ' (' . AutomationSettings::orderNewDeadlineHours() . ' ' . __('ingaz.hours') . ')',
            OrderEnum::UnderProcess->value => __('ingaz.timer_phase_under_process_base') . ' (' . AutomationSettings::orderUnderProcessDeadlineHours() . ' ' . __('ingaz.hours') . ')',
            default => __('ingaz.timer_phase_default'),
        };
    }

    private function translateMaidStatus(?string $status): string
    {
        if (!$status) {
            return __('ingaz.maid_status_unknown');
        }

        $key = 'ingaz.maid_status_' . $status;
        $translation = __($key);

        return $translation === $key ? __('ingaz.maid_status_unknown') : $translation;
    }

    private function getMaidPhoto(?Maid $maid): ?string
    {
        if (!$maid) {
            return null;
        }

        if ($maid->image) {
            return showFile($maid->image);
        }

        if ($maid->cv_preview) {
            return showFile($maid->cv_preview);
        }

        if ($maid->cv_file) {
            return showFile($maid->cv_file);
        }

        return null;
    }

    public function exportPdf()
    {
        $orders = objModel::with('user', 'maid', 'jobType', 'details.admin')->get();

        // Create HTML content with RTL support and better styling
        $html = '<!DOCTYPE html>
        <html dir="rtl">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <title>قائمة طلبات الاستقدام</title>
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
                .status-active {
                    color: green;
                }
                .status-blocked {
                    color: red;
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
                <h1>قائمة طلبات الاستقدام</h1>
                <p>تم التصدير في: ' . date('Y-m-d H:i:s') . '</p>
                <button class="print-button" onclick="printDocument()">طباعة</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>رقم الطلب</th>
                        <th>كود الطلب</th>
                        <th>العميل</th>
                        <th>رقم هاتف العميل</th>
                        <th>العاملة</th>
                        <th>رقم جواز العامل</th>
                        <th>نوع الوظيفة</th>
                        <th>طريقة الدفع</th>
                        <th>الحالة</th>
                        <th>تاريخ الإنشاء</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($orders as $order) {
            $statusText = getEnumValue('orders_statuses', $order->status);
            $html .= '<tr>
                <td>' . $order->id . '</td>
                <td>' . $order->order_code . '</td>
                <td>' . ($order->user ? $order->user->name : '') . '</td>
                <td>' . ($order->user ? getPhoneWithTag($order->user->phone_code . $order->user->phone) : '') . '</td>
                <td>' . ($order->maid ? $order->maid->name : '') . '</td>
                <td>' . ($order->maid ? $order->maid->passport_num : '') . '</td>
                <td>' . ($order->jobType ? $order->jobType->title : '') . '</td>
                <td>' . $order->payment_method . '</td>
                <td>' . $statusText . '</td>
                <td>' . $order->created_at->format('Y-m-d H:i A') . '</td>
            </tr>';
        }

        $html .= '</tbody></table></body></html>';

        // Return HTML with print styles
        $headers = [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'inline; filename="orders.html"',
        ];

        return response($html, 200, $headers);
    }

    public function exportExcel()
    {
        $orders = objModel::with('user', 'maid', 'jobType', 'details.admin')->get();

        // Create new Spreadsheet object
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set RTL direction
        $sheet->setRightToLeft(true);

        // Set headers
        $headers = [
            'رقم الطلب',
            'كود الطلب',
            'العميل',
            'رقم هاتف العميل',
            'العاملة',
            'رقم جواز العامل',
            'نوع الوظيفة',
            'طريقة الدفع',
            'الحالة',
            'تاريخ الإنشاء'
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
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

        // Set data
        $row = 2;
        foreach ($orders as $order) {
            $statusText = getEnumValue('orders_statuses', $order->status);
            $sheet->setCellValue('A' . $row, $order->id);
            $sheet->setCellValue('B' . $row, $order->order_code);
            $sheet->setCellValue('C' . $row, $order->user ? $order->user->name : '');
            $sheet->setCellValue('D' . $row, $order->user ? $order->user->phone_code . $order->user->phone : '');
            $sheet->setCellValue('E' . $row, $order->maid ? $order->maid->name : '');
            $sheet->setCellValue('F' . $row, $order->maid ? $order->maid->passport_num : '');
            $sheet->setCellValue('G' . $row, $order->jobType ? $order->jobType->title : '');
            $sheet->setCellValue('H' . $row, $order->payment_method);
            $sheet->setCellValue('I' . $row, $statusText);
            $sheet->setCellValue('J' . $row, $order->created_at->format('Y-m-d H:i A'));
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Create Excel writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        // Set headers for download
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="orders.xlsx"',
        ];

        // Save to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $writer->save($tempFile);

        // Return file
        return response()->download($tempFile, 'orders.xlsx', $headers)->deleteFileAfterSend(true);
    }

    /**
     * تحديث حالة الطلب
     */
    public function changeStatus(Request $request, $id)
    {
        try {
            $order = objModel::findOrFail($id);

            if ($this->isCompletedOrder($order) && $request->status !== $order->status) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تعديل المسؤول أو حذف أو إلغاء الطلب المكتمل.'
                ], 403);
            }

            // التحقق من أن الحالة الجديدة صحيحة
            $validStatuses = ['new', 'paid', 'started', 'completed', 'done', 'refused', 'canceled'];
            if (!in_array($request->status, $validStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'الحالة غير صحيحة'
                ], 400);
            }

            $oldStatus = $order->status;
            $order->status = $request->status;
            $order->save();

            // إرسال SMS إذا كان مطلوباً
            if ($request->status == 'started') {
                $objAction = new objAction($order);
                // إرسال SMS للعميل بتأكيد بدء معالجة الطلب
                $message = "تم بدء معالجة طلبكم رقم #{$order->id}. شكراً لثقتكم بنا.";
                $objAction->sendSMS($order->client_phone ? formatSaudiPhone($order->client_phone) : formatSaudiPhone($order->phone), $message);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة الطلب بنجاح من ' . $oldStatus . ' إلى ' . $request->status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الحالة: ' . $e->getMessage()
            ], 500);
        }
    }
}
