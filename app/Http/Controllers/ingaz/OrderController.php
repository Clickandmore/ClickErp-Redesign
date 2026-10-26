<?php

namespace App\Http\Controllers\ingaz;

use Illuminate\Support\Facades\DB;

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
use App\Models\Branch;
use App\Models\Contract;
use App\Models\MaidUnavailableRecord;
use App\Services\ManagedFileStorage;
use App\Support\AutomationSettings;
use App\Support\SystemModuleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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
            $branchesEnabled = SystemModuleManager::isEnabled('branches')
                && Schema::hasColumn('orders', 'branch_id');

            \Log::info('Orders AJAX request received', [
                'request_data' => $request->all(),
                'user_id' => auth('admin')->id()
            ]);

            $query = objModel::query();

            // Eager load relationships to avoid N+1 queries
            $query->with([
                'user',
                'maid.nationality',
                'maid.office.translations',
                'jobType.translations',
                'details.admin',
                'customer',
                'createdByAdmin',
                'branch',
                'contracts'
            ])->withCount('notes');

            // Keep actionable bookings at the top, then show the newest records first.
            $query->orderByRaw(
                'CASE WHEN orders.status = ? THEN 0 WHEN orders.status = ? THEN 1 ELSE 2 END',
                [OrderEnum::New->value, OrderEnum::UnderProcess->value]
            )->orderByDesc('orders.created_at');

            $query->recruitmentRequests();

            // Apply status filter
            if ($request->has('status') && $request->status != '') {
                $this->applyEffectiveStatusFilter($query, $request->status);
            }

            // Apply date range filter
            if ($request->filled('from_date')) {
                $query->where('created_at', '>=', \Carbon\Carbon::parse($request->from_date)->startOfDay());
            }
            if ($request->filled('to_date')) {
                $query->where('created_at', '<', \Carbon\Carbon::parse($request->to_date)->addDay()->startOfDay());
            }

            // Apply job type filter
            if ($request->filled('job_type')) {
                $query->where('job_type_id', $request->job_type);
            }

            // Apply admin filter
            if ($request->filled('admin_id')) {
                $query->whereResponsibleAdmin((int) $request->admin_id);
            }

            $this->applyRequestBranchFilter($query, $request, 'orders');

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

            if ($request->filled('maid_name')) {
                $query->whereHas('maid', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . trim($request->maid_name) . '%');
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

            // Source totals intentionally exclude only the source filter itself, so
            // they stay in sync with date/month, status, branch and search filters.
            $sourceCounts = (clone $query)->reorder()->toBase()->selectRaw(
                'COALESCE(SUM(created_by_admin_id IS NULL), 0) AS external_count, '
                . 'COALESCE(SUM(created_by_admin_id IS NOT NULL), 0) AS employee_count'
            )->first();

            if ($request->source === 'employee') {
                $query->createdByEmployee();
            } elseif ($request->source === 'external') {
                $query->createdExternally();
            }

            // Permission resolution is relatively expensive with branch-aware roles;
            // resolve it once per response instead of once for every rendered row.
            $currentAdmin = auth('admin')->user();
            $permissions = \App\Support\BranchScopedPermissions::withCachedPermissions(function () use ($currentAdmin) {
                return [
                    'edit' => (bool) @AdminGuardHasPerm(\App\PermKeys::ORDERS_EDIT),
                    'view' => (bool) @AdminGuardHasPerm(\App\PermKeys::ORDERS_VIEW),
                    'change_status' => (bool) @AdminGuardHasPerm(\App\PermKeys::ORDERS_CHANGE_STATUS),
                    'add_contract' => (bool) @AdminGuardHasPerm(\App\PermKeys::ORDERS_ADD_CONTRACT),
                    'change_maid' => (bool) @AdminGuardHasPerm(\App\PermKeys::ORDERS_CHANGE_MAID),
                    'change_responsible' => (bool) @AdminGuardHasPerm(\App\PermKeys::ORDERS_CHANGE_RESPONSIBLE),
                    'reject' => \App\Support\AdminRoutePermissionMatrix::adminHasAnyPermission($currentAdmin, [\App\PermKeys::ORDERS_REJECT]),
                    'delete' => \App\Support\AdminRoutePermissionMatrix::adminHasAnyPermission($currentAdmin, [\App\PermKeys::ORDERS_DELETE]),
                ];
            });
            $canEdit = $permissions['edit'];
            $canView = $permissions['view'];
            $canChangeStatus = $permissions['change_status'];
            $canAddContract = $permissions['add_contract'];
            $canChangeMaid = $permissions['change_maid'];
            $canChangeResponsible = $permissions['change_responsible'];
            $canReject = $permissions['reject'];
            $canDelete = $permissions['delete'];

            return DataTables::of($query)
                // Relations remain available to callbacks, but excluding them from
                // model serialization avoids costly Maid appended accessors.
                ->makeHidden(['maid', 'user', 'details', 'customer', 'createdByAdmin', 'branch', 'contracts', 'jobType'])
                ->orderColumn('maid_passport_num', function ($query, $direction) {
                    $query->orderBy(
                        Maid::query()->select('name')->whereColumn('maids.id', 'orders.maid_id')->limit(1),
                        $direction
                    );
                })
                ->filterColumn('maid_passport_num', function ($query, $keyword) {
                    $query->whereHas('maid', function ($maidQuery) use ($keyword) {
                        $maidQuery->where(function ($detailsQuery) use ($keyword) {
                            $detailsQuery->where('name', 'like', '%' . $keyword . '%')
                                ->orWhere('passport_num', 'like', '%' . $keyword . '%');
                        });
                    });
                })
                ->addIndexColumn()
                ->addColumn('admin_name', function ($row) {
                    return $row->resolveResponsibleAdmin()?->name ?? '-';
                })
                ->addColumn('order_source', function ($row) {
                    if ($creator = $row->resolveCreatorAdmin()) {

                        return '<span class="order-source-badge order-source-badge--employee" title="أُنشئ من لوحة التحكم">'
                            . '<i class="fas fa-user-tie"></i><span>موظف الفرع</span><small>' . e($creator->name ?: 'غير محدد') . '</small></span>';
                    }

                    return '<span class="order-source-badge order-source-badge--external" title="وصل من الموقع الخارجي">'
                        . '<i class="fas fa-globe"></i><span>الموقع الخارجي</span></span>';
                })
                ->addColumn('created_at', function ($row) {
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->editColumn('status', function ($row) use ($canEdit) {
                    $statusHtml = $this->getStatusHtml($this->effectiveOrderStatus($row));

                    if ($canEdit && !$this->isCompletedOrder($row)) {
                        $statusHtml .= ' <button type="button" class="edit-status" data-id="' . $row->id . '" title="تعديل الحالة">
                                <i class="fa fa-pen"></i>
                            </button>';
                    }

                    return '<div class="d-flex align-items-center gap-2">' . $statusHtml . '</div>';
                })
                ->addColumn('timer', function ($row) {
                    $html = '<div class="order-alerts d-flex flex-column align-items-start gap-2">'
                        . $this->getTimerHtml($row);

                    $notesCount = (int) ($row->notes_count ?? 0);
                    if ($notesCount > 0) {
                        $url = route('showSingleOrder', ['orderId' => $row->id]) . '#order-notes';
                        $html .= '<a class="order-notes-indicator" href="' . e($url) . '" title="عرض ملاحظات الطلب">'
                            . '<i class="fas fa-sticky-note"></i>'
                            . '<span>عليه ملاحظات</span>'
                            . '<span class="badge bg-warning text-dark">' . $notesCount . '</span>'
                            . '</a>';
                    }

                    return $html . '</div>';
                })
                // Keep already-open pages from failing after the notes column was
                // merged into alerts. The current table no longer renders this key.
                ->addColumn('notes', fn () => '')
                ->addColumn('recruitment_office', function ($row) {
                    $office = $row->maid?->office;
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

                    $office = $row->maid->office;
                    $officeTitle = $office?->title ?? '-';

                    return '<div class="d-flex flex-column gap-1">'
                        . '<span class="fw-bold">' . e($row->maid->name ?: '-') . '</span>'
                        . '<span class="maid-passport-badge" dir="ltr" title="' . e(helperTrans('ingaz.passport_num')) . '">'
                        . '<i class="fas fa-passport"></i>'
                        . '<span>' . e($row->maid->passport_num ?: '-') . '</span>'
                        . '</span>'
                        . '<small class="text-muted">' . helperTrans('ingaz.request_type') . ': ' . e($row->maid->request_type_label) . '</small>'
                        . '<small class="text-muted d-flex align-items-center gap-1">'
                        . '<i class="fas fa-building text-secondary"></i>'
                        . '<span>' . __('ingaz.recruitment_office') . ': ' . e($officeTitle) . '</span>'
                        . '</small>'
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
                    if ($row->contracts->isNotEmpty()) {
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
                ->addColumn('branch', function ($row) use ($branchesEnabled) {
                    if (!$branchesEnabled) {
                        return '-';
                    }

                    return $row->branch ? e($row->branch->name) : '-';
                })
                ->addColumn('actions', function ($row) use ($canView, $canChangeStatus, $canAddContract, $canChangeMaid, $canChangeResponsible, $canReject, $canDelete, $currentAdmin) {
                    $isCompletedOrder = $this->isCompletedOrder($row);

                    $actions = ' <div class="dropdown actions-dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" aria-expanded="false" title="الإجراءات المتاحة">
                                        <i class="fas fa-cogs me-1"></i> الإجراءات
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg top-0">';

                    // View button
                    if ($canView) {
                        $actions .= '<li><a class="dropdown-item" href="' . route('showSingleOrder', ['orderId' => $row->id]) . '">
                            <i class="fa fa-eye"></i> عرض التفاصيل
                        </a></li>';
                    }


                    // A linked contract makes the order complete and locks its status.
                    if ($canChangeStatus && !$isCompletedOrder) {
                        $actions .= '<li><button class="dropdown-item edit-status" data-id="' . $row->id . '">
                            <i class="fa fa-edit"></i> تعديل الحالة
                        </button></li>';
                    }

                    // Add Contract button for open orders and legacy ended orders that still have no contract.
                    if ($canAddContract && $this->canAddRecruitmentContract($row)) {
                        $actions .= '<li><button class="dropdown-item addNewContract" data-id="' . $row->id . '">
                            <i class="fa fa-plus"></i> إضافة عقد
                        </button></li>';
                    }

                    // Change Maid button for new orders
                    if ($canChangeMaid && $row->status == OrderEnum::New->value) {
                        $actions .= '<li><button class="dropdown-item change-maid" data-id="' . $row->id . '">
                            <i class="fa fa-user"></i> تغيير العاملة
                        </button></li>';
                    }

                    // Change Responsible Employee button
                    if ($canChangeResponsible && !$isCompletedOrder) {
                        $actions .= '<li><button class="dropdown-item change-responsible" data-id="' . $row->id . '">
                            <i class="fa fa-user-secret"></i> تغيير الموظف المسؤول
                        </button></li>';
                    }

                    // Refuse/Return button for new and under_process orders
                    if (($row->wasCreatedByAdmin($currentAdmin) || $canReject) && !$isCompletedOrder) {
                        if (in_array($row->status, [OrderEnum::New->value, OrderEnum::UnderProcess->value])) {
                            $actions .= '<li><button class="dropdown-item refuse-order" data-id="' . $row->id . '">
                                <i class="fa fa-times"></i> رفض/إرجاع الطلب
                            </button></li>';
                        }
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_CHANGE_CLIENT)
                        && in_array($row->status, ['new', 'under_process'], true) && !$isCompletedOrder) {
                        $actions .= '<li><button class="dropdown-item change-order-client" data-id="' . $row->id . '" data-client-id="' . e((string) $row->user_id) . '"><i class="fa fa-user-edit"></i> تغيير العميل</button></li>';
                    }
                    // Delete button
                    if (($row->wasCreatedByAdmin($currentAdmin) || $canDelete) && !$isCompletedOrder) {
                        $actions .= '<li><hr class="dropdown-divider"></li>
                        <li><button class="dropdown-item delete-order" data-id="' . $row->id . '">
                            <i class="fa fa-trash"></i> حذف الطلب
                        </button></li>';
                    }

                    $actions .= '</ul></div>';
                    return $actions;
                })
                ->escapeColumns([])
                ->rawColumns(['actions', 'status', 'timer', 'user_id', 'phone', 'maid_id', 'maid_passport_num', 'deliver_status', 'contract_status', 'order_code', 'created_at', 'admin_name', 'order_source'])
                ->with('source_counts', [
                    'external' => (int) ($sourceCounts->external_count ?? 0),
                    'employee' => (int) ($sourceCounts->employee_count ?? 0),
                ])
                ->make(true);
        }

        // Preload data for the main page to avoid multiple queries
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["modalType"] = "modal-lg";
        $data['branchesEnabled'] = SystemModuleManager::isEnabled('branches')
            && Schema::hasColumn('orders', 'branch_id');
        $data['branches'] = $data['branchesEnabled']
            ? Branch::query()->active()->ordered()->get(['id', 'name_ar', 'name_en'])
            : collect();

        // Cache filter data for 5 minutes to avoid repeated queries
        $data['jobTypes'] = \Cache::remember('job_types_list', 300, function () {
            return JobTitle::with('translations')->get();
        });

        $data['nationalities'] = \Cache::remember('nationalities_list', 300, function () {
            return Nationality::with('translations')->get()->sortBy('title');
        });

        $data['admins'] = \Cache::remember('admins_list.active_for_operations', 300, function () {
            return \App\Models\auth\Admin::query()->activeForOperations()->select('id', 'name')->get();
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

        // Use the same branch scope as the DataTable so the summary cards always
        // describe the records that the current admin can actually see.
        $statisticsQuery = objModel::query()->recruitmentRequests();
        $this->applyRequestBranchFilter($statisticsQuery, $request, 'orders');

        $contractExists = 'EXISTS (SELECT 1 FROM contracts AS source_contracts WHERE source_contracts.order_id = orders.id)';
        $completedPlaceholders = implode(',', array_fill(0, count($completedStatuses), '?'));
        $notCompletedPlaceholders = implode(',', array_fill(0, count($notCompletedStatuses), '?'));
        $statistics = (clone $statisticsQuery)->toBase()->selectRaw(
            "COUNT(*) AS total_count,
            COALESCE(SUM(created_by_admin_id IS NULL), 0) AS external_count,
            COALESCE(SUM(created_by_admin_id IS NOT NULL), 0) AS employee_count,
            COALESCE(SUM(status = ? AND NOT $contractExists), 0) AS new_count,
            COALESCE(SUM(status = ? AND NOT $contractExists), 0) AS under_process_count,
            COALESCE(SUM(status = ? AND deadline_at IS NOT NULL AND deadline_at < ? AND NOT $contractExists), 0) AS new_overdue_count,
            COALESCE(SUM(status = ? AND deadline_at IS NOT NULL AND deadline_at < ? AND NOT $contractExists), 0) AS process_overdue_count,
            COALESCE(SUM(status IN ($completedPlaceholders) OR $contractExists), 0) AS completed_count,
            COALESCE(SUM(status IN ($notCompletedPlaceholders) AND NOT $contractExists), 0) AS not_completed_count",
            [
                OrderEnum::New->value,
                OrderEnum::UnderProcess->value,
                OrderEnum::New->value,
                now(),
                OrderEnum::UnderProcess->value,
                now(),
                ...$completedStatuses,
                ...$notCompletedStatuses,
            ]
        )->first();
        $data['totalOrders'] = (int) ($statistics->total_count ?? 0);
        $data['externalOrders'] = (int) ($statistics->external_count ?? 0);
        $data['employeeOrders'] = (int) ($statistics->employee_count ?? 0);
        $data['newOrders'] = (int) ($statistics->new_count ?? 0);
        $data['underProcessOrders'] = (int) ($statistics->under_process_count ?? 0);
        $data['newOverdueOrders'] = (int) ($statistics->new_overdue_count ?? 0);
        $data['underProcessOverdueOrders'] = (int) ($statistics->process_overdue_count ?? 0);
        $data['completedOrders'] = (int) ($statistics->completed_count ?? 0);
        $data['notCompletedOrders'] = (int) ($statistics->not_completed_count ?? 0);
        $data['orderNewDeadlineHours'] = AutomationSettings::orderNewDeadlineHours();
        $data['orderUnderProcessDeadlineHours'] = AutomationSettings::orderUnderProcessDeadlineHours();

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

    private function applyEffectiveStatusFilter($query, string $status): void
    {
        $selectedStatuses = match ($status) {
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

        if (in_array($status, ['ended', 'completed'], true)) {
            $query->where(function ($query) use ($selectedStatuses) {
                $query->whereIn('status', $selectedStatuses)
                    ->orWhereHas('contracts');
            });

            return;
        }

        $query->whereIn('status', $selectedStatuses)
            ->whereDoesntHave('contracts');
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
        return $this->hasRecruitmentContract($order) || in_array($order->status, [
            OrderEnum::Completed->value,
            OrderEnum::Done->value,
        ], true);
    }

    private function effectiveOrderStatus(Order $order): string
    {
        return $this->hasRecruitmentContract($order)
            ? OrderEnum::Completed->value
            : $order->status;
    }

    private function completedOrderLockedResponse()
    {
        return jsonError('لا يمكن تعديل المسؤول أو حذف أو إلغاء الطلب المكتمل.', 403);
    }

    private function hasRecruitmentContract(Order $order): bool
    {
        if ($order->relationLoaded('contracts')) {
            return $order->contracts->isNotEmpty();
        }

        return $order->contracts()->exists();
    }

    private function requiresRecruitmentContract(string $status): bool
    {
        return in_array($status, [
            OrderEnum::Contracted->value,
            OrderEnum::ContractedWithoutContract->value,
            OrderEnum::Completed->value,
            OrderEnum::Done->value,
        ], true);
    }

    private function canAddRecruitmentContract(Order $order): bool
    {
        return !$this->hasRecruitmentContract($order)
            && in_array($order->status, [
                OrderEnum::UnderProcess->value,
                OrderEnum::Contracted->value,
                OrderEnum::ContractedWithoutContract->value,
                OrderEnum::Completed->value,
                OrderEnum::Done->value,
            ], true);
    }

    private function contractRequiredResponse()
    {
        return jsonError('لا يمكن تحويل الطلب إلى حالة منتهية بدون إنشاء عقد. الرجاء إضافة عقد أو رفض الطلب.', 422);
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
                $this->assertCanAccessBranchModel($obj, 'orders');

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
            $this->assertCanAccessBranchModel($order, 'orders');
            $newStatus = $validated['status'];

            if ($this->isCompletedOrder($order) && $newStatus !== $order->status) {
                return $this->completedOrderLockedResponse();
            }

            if ($this->requiresRecruitmentContract($newStatus) && !$this->hasRecruitmentContract($order)) {
                return $this->contractRequiredResponse();
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
        $this->assertCanAccessBranchModel($order, 'orders');
        abort_unless($order->adminCanDelete(auth('admin')->user()), 403);
        if ($this->isCompletedOrder($order)) {
            return $this->completedOrderLockedResponse();
        }
        DB::transaction(function () use ($order) {
            $order = Order::withTrashed()->lockForUpdate()->findOrFail($order->id);
            if ($order->contracts()->exists() || \App\Models\RentalContract::where('order_id', $order->id)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['order' => 'لا يمكن حذف طلب مرتبط بعقد.']);
            }
            if ($order->trashed()) {
                $order->forceDelete();
            } else {
                $order->delete();
            }
        });
        return jsonSuccess(['success' => true], __("ingaz.order_deleted_successfully"), 200);
    }

    public function restore($id)
    {
        $order = Order::withTrashed()->findOrFail($id);
        $this->assertCanAccessBranchModel($order, 'orders');
        abort_unless($order->adminCanDelete(auth('admin')->user()), 403);
        try {
            $order = Order::withTrashed()->findOrFail($id);
            $this->assertCanAccessBranchModel($order, 'orders');
            DB::transaction(function () use ($order) {
                $order = Order::withTrashed()->lockForUpdate()->findOrFail($order->id);
                if (!$order->trashed()) { return; }
                $client = \App\Models\ingaz\User::withTrashed()->find($order->user_id);
                if (!$client || $client->trashed()) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['order' => 'يجب استعادة العميل أولاً قبل استعادة الطلب.']);
                }
                app(\App\Services\OrderMaidReservationService::class)->reserveForRestore($order);
                $order->restore();
            });

            // Restore Notification
            Notification::create([
                'title' => __('ingaz.update_order_restored'),
                'description' => __('ingaz.order_restored_msg', ['order_id' => $order->id]),
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'type' => 'order_status',
            ]);

            return jsonSuccess(['success' => 'تم استعادة الطلب بنجاح']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
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
                'deadline_at' => $order->getEffectiveDeadline()?->format('Y-m-d H:i'),
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
            'contracts',
            'notes.admin'
        ])->find($orderId);

        if (!$data) {
            abort(404);
        }

        $this->assertCanAccessBranchModel($data, 'orders');

        $activities = $this->buildOrderActivitiesTimeline($data);

        $clientChanges = $this->orderClientChanges($data);
        return view('admin.ingaz.orders.show', compact('data', 'activities', 'clientChanges'));
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
            'contracts',
            'notes.admin'
        ])->find($orderId);

        if (!$data) {
            abort(404);
        }

        $this->assertCanAccessBranchModel($data, 'orders');

        $activities = $this->buildOrderActivitiesTimeline($data);

        $clientChanges = $this->orderClientChanges($data);
        return view('admin.ingaz.orders.show', compact('data', 'activities', 'clientChanges'));
    }

    public function storeNote(Request $request, $orderId)
    {
        abort_unless(@AdminGuardHasPerm(\App\PermKeys::ORDERS_EDIT), 403);

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ], [
            'note.required' => 'نص الملاحظة مطلوب.',
            'note.max' => 'يجب ألا تتجاوز الملاحظة 2000 حرف.',
        ]);

        $order = Order::withTrashed()->findOrFail($orderId);
        $this->assertCanAccessBranchModel($order, 'orders');

        $order->notes()->create([
            'admin_id' => auth('admin')->id(),
            'note' => trim($validated['note']),
        ]);

        return redirect(route('showSingleOrder', ['orderId' => $order->id]) . '#order-notes')
            ->with('success', 'تمت إضافة الملاحظة على الطلب بنجاح.');
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

    private function orderClientChanges(Order $order)
    {
        $changes = $order->activities()->where('description', 'تغيير العميل في الطلب')->with('causer')->latest('id')->get();
        $ids = $changes->flatMap(fn ($change) => [$change->properties['old_client_id'] ?? null, $change->properties['new_client_id'] ?? null])->filter()->unique();
        $clients = User::withTrashed()->whereIn('id', $ids)->get()->keyBy('id');
        return $changes->map(function ($change) use ($clients) {
            $properties = $change->properties;
            $result = ['date' => $change->created_at, 'actor' => $change->causer?->name ?? $properties['actor_name'] ?? '-', 'reason' => $properties['reason'] ?? '-'];
            foreach (['old', 'new'] as $side) {
                $client = $clients->get($properties[$side . '_client_id'] ?? null);
                if ($client && !app(\App\Services\BranchAccessService::class)->canAccessModel($client)) $client = null;
                $result[$side] = [
                    'id' => $properties[$side . '_client_id'] ?? null,
                    'name' => $properties[$side . '_client_name'] ?? $client?->name ?? '-',
                    'phone' => $properties[$side . '_client_phone'] ?? $client?->phone ?? '-',
                    'identity' => $properties[$side . '_client_identity'] ?? $client?->national_num ?? '-',
                ];
            }
            return $result;
        });
    }

    public function download($id)
    {
        $transfer = OrderTransfers::find($id); // Corrected the namespace

        if (!$transfer) {
            abort(404);
        }

        $storage = app(ManagedFileStorage::class);

        return $storage->downloadResponse(
            $transfer->file,
            basename($transfer->file),
            $storage->mimeType($transfer->file)
        );
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
            $oldMaid = $order->maid;

            $rules = [
                'maid_id' => 'required|exists:maids,id',
                'old_maid_status' => ($oldMaid ? 'required' : 'nullable') . '|in:available,backout',
                'reason' => 'required_if:old_maid_status,backout|nullable|string|max:1000'
            ];
            $request->validate($rules);

            $newMaidId = $request->maid_id;
            $oldMaidStatus = $request->old_maid_status;
            $reason = $request->reason;

            // Check if new maid is available
            $newMaid = \App\Models\ingaz\Maid::findOrFail($newMaidId);

            if ($newMaid->isHourly()) {
                return response()->json([
                    'success' => false,
                    'message' => __('rental_mode.errors.hourly_not_orderable'),
                ], 422);
            }

            if ($order->job_type_id == 2) {
                $order->loadMissing('details.package');

                if (! $newMaid->jobTypes()->where('job_type_id', 2)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'العاملة المختارة ليست متاحة لخدمة الإيجار.'
                    ], 422);
                }

                $packageNationalityId = $order->details?->package?->nationality_id;
                if ($packageNationalityId && (int) $newMaid->nationality_id !== (int) $packageNationalityId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'جنسية العاملة المختارة لا تطابق جنسية الباقة.'
                    ], 422);
                }
            }

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
                ->whereNotIn('status', Order::nonBlockingMaidAssignmentStatuses())
                ->first();

            if ($existingOrder) {
                return response()->json([
                    'success' => false,
                    'message' => 'العاملة المختارة محجوزة لطلب آخر'
                ], 400);
            }

            \DB::beginTransaction();

            try {
                // Save old maid info
                $oldMaidId = $oldMaid?->id;

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

                if ($oldMaid && $oldMaidStatus === 'backout') {
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
                } elseif ($oldMaid) {
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
                    'old_maid_new_status' => $oldMaid?->status,
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
                    'message' => $oldMaid ? 'تم تغيير العاملة بنجاح' : 'تم تعيين العاملة للطلب بنجاح',
                    'data' => [
                        'order_id' => $id,
                        'old_maid' => $oldMaid ? [
                            'id' => $oldMaid->id,
                            'name' => $oldMaid->name,
                            'status' => $oldMaid->status,
                            'available' => $oldMaid->available
                        ] : null,
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
                ->activeForOperations()
                ->where('admin_type', 'customer_service')
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

    public function searchChangeClient(Request $request)
    {
        abort_unless(\App\Support\AdminRoutePermissionMatrix::adminHasAnyPermission(auth('admin')->user(), [\App\PermKeys::ORDERS_CHANGE_CLIENT]), 403);
        $data = $request->validate(['q' => 'required|string|min:2|max:100', 'order_id' => 'required|integer']);
        $order = Order::findOrFail($data['order_id']);
        $this->assertCanAccessBranchModel($order, 'orders');
        $query = User::query()->where(fn ($query) => $query->where('is_blocked', 0)->orWhereNull('is_blocked'))->where(function ($query) use ($data) {
            foreach (['name', 'phone', 'national_num'] as $field) $query->orWhere($field, 'like', '%' . trim($data['q']) . '%');
        });
        $access = app(\App\Services\BranchAccessService::class);
        $branchId = (int) auth('admin')->user()->branch_id;
        if ($access->enabled() && $branchId && !$access->canViewAllBranches()) {
            $access->applyBranchIdToQuery($query, $branchId, 'users', null,
                app(\App\Services\BranchDataSharingService::class)->sharedSourceBranchIds(\App\Services\BranchDataSharingService::CLIENTS));
        }
        if ($order->user_id) $query->whereKeyNot($order->user_id);
        $clients = $query->limit(20)->get(['id', 'name', 'phone', 'national_num']);
        return response()->json(['results' => $clients->map(fn ($client) => [
            'id' => $client->id, 'text' => collect([$client->name, $client->phone, $client->national_num])->filter()->implode(' — '),
            'name' => $client->name, 'phone' => $client->phone, 'national_num' => $client->national_num,
        ])]);
    }

    public function changeClient(Request $request, $id)
    {
        abort_unless(\App\Support\AdminRoutePermissionMatrix::adminHasAnyPermission(auth('admin')->user(), [\App\PermKeys::ORDERS_CHANGE_CLIENT]), 403);
        $data = $request->validate(['client_id' => 'required|integer', 'expected_client_id' => 'present|nullable|integer', 'reason' => 'required|string|min:3|max:500']);
        return DB::transaction(function () use ($id, $data) {
            $order = Order::query()->lockForUpdate()->findOrFail($id);
            $this->assertCanAccessBranchModel($order, 'orders');
            if ($reason = $order->clientChangeBlockReason()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['order' => $reason]);
            }
            if (array_key_exists('expected_client_id', $data) && (int) $data['expected_client_id'] !== (int) $order->user_id) {
                throw \Illuminate\Validation\ValidationException::withMessages(['client_id' => 'تم تغيير العميل بالفعل؛ حدّث الصفحة وحاول مجددًا.']);
            }
            $client = User::query()->lockForUpdate()->find($data['client_id']);
            if (!$client || $client->is_blocked || !app(\App\Services\BranchAccessService::class)->canAccessModel($client)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['client_id' => 'العميل غير متاح أو خارج الفروع المسموحة.']);
            }
            if ((int) $order->user_id === (int) $client->id) {
                throw \Illuminate\Validation\ValidationException::withMessages(['client_id' => 'اختر عميلًا مختلفًا عن العميل الحالي.']);
            }
            $previous = User::withTrashed()->find($order->user_id);
            $oldId = $order->user_id;
            $oldAddress = $order->details?->address_id;
            $order->user_id = $client->id;
            $order->save();
            // An old customer's delivery address must never transfer to the new customer.
            if ($order->details && $oldAddress) DB::table('order_details')->where('id', $order->details->id)->update(['address_id' => null, 'updated_at' => now()]);
            activity()->causedBy(auth('admin')->user())->performedOn($order)->withProperties([
                'old_client_id' => $oldId, 'old_client_name' => $previous?->name,
                'new_client_id' => $client->id, 'new_client_name' => $client->name,
                'old_client_phone' => $previous?->phone, 'old_client_identity' => $previous?->national_num,
                'new_client_phone' => $client->phone, 'new_client_identity' => $client->national_num,
                'cleared_address_id' => $oldAddress, 'reason' => $data['reason'],
            ])->log('تغيير العميل في الطلب');
            return response()->json(['success' => true, 'message' => 'تم تغيير العميل في الطلب بنجاح.']);
        });
    }

    public function refuseReturn(Request $request, $id)
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in([OrderEnum::Refused->value, OrderEnum::Return->value])],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $order = Order::findOrFail($id);
        $this->assertCanAccessBranchModel($order, 'orders');
        try {
            $locked = DB::transaction(function () use (&$order, $validated) {
                $order = Order::lockForUpdate()->findOrFail($order->id);
                if ($this->isCompletedOrder($order)) {
                    return true;
                }

                $order->status = $validated['action'];
                $order->status_changed_at = now();
                $order->refuse_reason = $this->resolveRefuseReason($validated['reason'] ?? null, $validated['action']);
                $order->save();
                $this->releaseMaid($order);
                return false;
            });
            if ($locked) {
                return $this->completedOrderLockedResponse();
            }
        } catch (\Throwable $e) {
            report($e);
            return jsonError(['message' => 'تعذر تحديث حالة الطلب. لم يتم حفظ التغيير، يرجى المحاولة مرة أخرى.']);
        }

        // Notification failures must not leave a returned order reserving its maid.
        // Legacy orders may reference a customer permanently deleted in the past.
        try {
            // Send SMS
            if ($validated['action'] == "refused") {
                $body = "عزيزي العميل، تم رفض طلب الاستقدام رقم {$order->order_code}. لمزيد من التفاصيل يرجى التواصل مع خدمة العملاء.";
            } else if ($validated['action'] == "return") {
                $body = "عزيزي العميل، نود إشعاركم بأنه تم إرجاع طلب الاستقدام رقم {$order->order_code}. يرجى مراجعة الطلب عبر حسابكم.";
            }
            $user = $order->user;
            $userPhone = $user?->phone ? formatSaudiPhone($user->phone) : null;
            $customerPhone = $order->customer?->formatted_verification_phone;
            $recipients = array_values(array_filter([$userPhone, $customerPhone]));
            $result = !empty($recipients) ? $this->sendSMS($recipients, $body) : null;

            if ($user && isset($result['code']) && $result['code'] == "1") {
                $this->storeMessage('ارسال رسالة نصية الى العميل', $body, NotificationActionEnum::SMS->value, $user->id);
            }


            // Refuse/Return Notification
            if ($user) {
                Notification::create([
                    'title' => __('ingaz.update_order_status'),
                    'description' => __('ingaz.order_status_updated', ['order_id' => $order->id]) . " " . __('ingaz.' . $order->status),
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'type' => 'order_status',
                ]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
        return jsonSuccess(['success' => true], __('ingaz.status_updated'), 200);
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
        app(\App\Services\OrderMaidReservationService::class)->release($order);
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

        $deadline = $order->getEffectiveDeadline();
        $deadlineFormatted = $deadline?->format('Y-m-d H:i');
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
        if ($this->isCompletedOrder($order)) {
            return '';
        }

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
        $orders = objModel::with('user', 'maid', 'jobType', 'details.admin')
            ->recruitmentRequests()
            ->get();

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
        $orders = objModel::with('user', 'maid', 'jobType', 'details.admin')
            ->recruitmentRequests()
            ->get();

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

            if ($this->requiresRecruitmentContract((string) $request->status) && !$this->hasRecruitmentContract($order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تحويل الطلب إلى حالة منتهية بدون إنشاء عقد. الرجاء إضافة عقد أو رفض الطلب.'
                ], 422);
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
