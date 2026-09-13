<?php

namespace App\Http\Controllers\admin\auth;

use App\Helpers\Constant;
use App\Http\Actions\auth\AdminAction as ObjAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\auth\profile\StoreAdminRequest as StoreRequest;
use App\Http\Requests\auth\profile\UpdateAdminRequest as UpdateRequest;
use App\Models\Branch;
use App\Models\auth\Admin as objModel;
use App\Models\Contract;
use App\Models\ingaz\Order;
use App\Models\RentalContract;
use App\PermKeys;
use App\Support\AdminRoutePermissionMatrix;
use App\Support\SystemModuleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Mail\ResetPasswordMail;
use App\Models\Role;
use Yajra\DataTables\DataTables;


class AdminController extends Controller
{
    public $folderPath = "admin.auth.admin";
    public $postData = ["name", "username", "phone", "email", 'whatsapp', 'verification_phone', 'identity_number', 'identity_expiry_date', "password", "admin_type", 'sex_type', 'role_id', 'branch_id', 'sort', 'status', 'note'];
    public $mainRoute = "admins";
    public $suspensionReasons = ['violation', 'manual_review', 'fraud_suspect', 'other'];


    function __construct()
    {
        $this->middleware('permission:users_view,admin', ['only' => ['index']]);
        $this->middleware('permission:users_create,admin', ['only' => ['create', 'store']]);
        $this->middleware('permission:users_edit,admin', ['only' => ['edit', 'update', 'toggleStatus', 'terminateSession']]);
        $this->middleware('permission:users_delete,admin', ['only' => ['destroy']]);
        // trashed / restore / forceDelete: صلاحياتها المستقلة (users_trashed_view, users_restore,
        // users_force_delete أو users_control) تُفحص في AdminRoutePermissionMatrix عبر
        // ميدلوير admin.route.permission. لا نكررها هنا بصيغة "a|b" لأن PermissionCatalog
        // يقرأ middleware permission: كاسم صلاحية واحد ولا يفهم الفاصل |.
    }

    /**
     * صفحة الموظفين المحذوفين (soft-deleted) مع الاستعادة أو الحذف النهائي.
     * الطلب العادي يعرض الصفحة، وطلب AJAX يرجع صفوف الجدول.
     */
    public function trashed(Request $request, ObjAction $action)
    {
        $branchesEnabled = SystemModuleManager::isEnabled('branches');
        $adminUser = Auth::guard('admin')->user();
        $canRestore = AdminRoutePermissionMatrix::adminHasAnyPermission($adminUser, [PermKeys::USERS_RESTORE, PermKeys::USERS_CONTROL]);
        $canForceDelete = AdminRoutePermissionMatrix::adminHasAnyPermission($adminUser, [PermKeys::USERS_FORCE_DELETE, PermKeys::USERS_CONTROL]);

        $trashedQuery = ObjModel::onlyTrashed()
            ->notExternalOfficeAgents()
            ->with(['role', 'branch']);
        $this->applyRequestBranchFilter($trashedQuery, $request, 'admins');

        if ($request->ajax()) {
            $rows = $trashedQuery->orderByDesc('deleted_at')->get();

            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('name', function ($row) {
                    return '<div class="trashed-admin-name">'
                        . '<span class="trashed-admin-avatar">' . e(mb_substr((string) $row->name, 0, 1)) . '</span>'
                        . '<div><div class="fw-bold">' . e($row->name) . '</div>'
                        . '<div class="text-muted small">#' . (int) $row->id . '</div></div>'
                        . '</div>';
                })
                ->addColumn('username', fn ($row) => $row->username ? e($row->username) : '<span class="text-muted">—</span>')
                ->editColumn('email', fn ($row) => e($row->email))
                ->editColumn('phone', fn ($row) => e($row->phone ?: '—'))
                ->addColumn('role', fn ($row) => $row->role ? e($row->role->name) : '<span class="text-muted">—</span>')
                ->addColumn('branch', function ($row) use ($branchesEnabled) {
                    if (!$branchesEnabled) {
                        return '';
                    }

                    return $row->branch ? e($row->branch->name) : '<span class="text-muted">—</span>';
                })
                ->addColumn('deleted_at', function ($row) {
                    $deletedAt = $row->deleted_at;

                    return '<div class="trashed-admin-date">'
                        . '<div class="fw-bold">' . e($deletedAt->format('Y-m-d H:i')) . '</div>'
                        . '<div class="text-muted small">' . e($deletedAt->diffForHumans()) . '</div>'
                        . '</div>';
                })
                ->addColumn('actions', function ($row) use ($canRestore, $canForceDelete) {
                    $buttons = [];

                    if ($canRestore) {
                        $buttons[] = '<button type="button" class="btn btn-sm btn-success trashed-restore-button"'
                            . ' data-id="' . (int) $row->id . '"'
                            . ' data-name="' . e($row->name) . '"'
                            . ' data-restore-route="' . route($this->mainRoute . '.restore', $row->id) . '"'
                            . ' title="' . e(__('ingaz.restore')) . '">'
                            . '<i class="fa fa-undo"></i> <span>' . __('ingaz.restore') . '</span>'
                            . '</button>';
                    }

                    if ($canForceDelete) {
                        $buttons[] = '<button type="button" class="btn btn-sm btn-outline-danger trashed-force-delete-button"'
                            . ' data-id="' . (int) $row->id . '"'
                            . ' data-name="' . e($row->name) . '"'
                            . ' data-delete-route="' . route($this->mainRoute . '.force-delete', $row->id) . '"'
                            . ' title="' . e(__('admin.force_delete')) . '">'
                            . '<i class="fa fa-trash-o"></i> <span>' . __('admin.force_delete') . '</span>'
                            . '</button>';
                    }

                    if ($buttons === []) {
                        return '<span class="text-muted small">' . __('admin.no_actions') . '</span>';
                    }

                    return '<div class="trashed-admin-actions">' . implode('', $buttons) . '</div>';
                })
                ->rawColumns(['name', 'username', 'email', 'phone', 'role', 'branch', 'deleted_at', 'actions'])
                ->make(true);
        }

        $statsQuery = clone $trashedQuery;
        $data['bladeTitle'] = __('admin.trashed_admins.title');
        $data['dataTableRoute'] = route($this->mainRoute . '.trashed');
        $data['branchesEnabled'] = $branchesEnabled;
        $data['canRestore'] = $canRestore;
        $data['canForceDelete'] = $canForceDelete;
        $data['totalTrashed'] = (clone $statsQuery)->count();
        $data['trashedLast30Days'] = (clone $statsQuery)->where('deleted_at', '>=', now()->subDays(30))->count();
        $data['latestDeletion'] = (clone $statsQuery)->max('deleted_at');
        $data['initialSearch'] = trim((string) $request->get('search', ''));
        $branchSelector = $this->branchSelectorData($request);
        $data['branches'] = $branchSelector['branches'];

        $reassignmentAdmins = ObjModel::query()
            ->notExternalOfficeAgents()
            ->select('id', 'name')
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->orderBy('name');
        $this->applyRequestBranchFilter($reassignmentAdmins, $request, 'admins');
        $data['reassignmentAdmins'] = $reassignmentAdmins->get();

        return view($this->folderPath . '.trashed', $data);
    }


    public function index(Request $request, ObjAction $action)
    {
        if (! $request->ajax() && $request->get('filter_scope') === 'office') {
            return redirect()->route('offices.users.index');
        }

        if ($request->ajax()) {
            $branchesEnabled = SystemModuleManager::isEnabled('branches');
            $dataTable = ObjModel::query()
                ->notExternalOfficeAgents()
                ->with(['role', 'branch', 'agentOffices'])
                ->orderBy('id', 'asc');

            $this->applyRequestBranchFilter($dataTable, $request, 'admins');

            // Filters
            if ($request->filled('admin_type')) {
                $dataTable->where('admin_type', $request->admin_type);
            }
            if ($request->filled('sex_type')) {
                $dataTable->where('sex_type', $request->sex_type);
            }
            $filterScope = $request->get('filter_scope');
            if ($filterScope === 'branch') {
                $dataTable->whereNotNull('branch_id');
            } elseif ($filterScope === 'office') {
                $dataTable->whereHas('agentOfficeLinks');
            }

            $statusFilter = $request->get('status');
            if (!$statusFilter && $request->has('suspended') && $request->boolean('suspended')) {
                $statusFilter = 'suspended';
            }

            // المحذوفون لهم صفحة مستقلة (admins.trashed) بصلاحياتها الخاصة
            if (in_array($statusFilter, ['active', 'suspended'], true)) {
                $dataTable->where('status', $statusFilter);
            }

            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('name', function ($row) {
                    return $row->name;
                })
                ->addColumn('admin_type', function ($row) {
                    $key = $row->admin_type;
                    return Arr::get(Constant::globalArray['admin_typeis'], "{$key}." . App::getLocale(), __('admin.not_specified'));
                })
                ->addColumn('username', function ($row) {
                    return $row->username ?: '-';
                })
                ->addColumn('role', function ($row) {
                    if (!$row->role) {
                        return '-';
                    }

                    if ($row->role->guard_name !== 'admin') {
                        return '<span class="text-danger">' . e($row->role->name) . ' (' . e($row->role->guard_name) . ')</span>';
                    }

                    return e($row->role->name);
                })
                ->addColumn('branch', function ($row) use ($branchesEnabled) {
                    if (!$branchesEnabled) {
                        return '-';
                    }

                    return $row->branch ? e($row->branch->name) : '-';
                })
                ->addColumn('offices', function ($row) {
                    if ($row->agentOffices->isEmpty()) {
                        return '-';
                    }

                    $names = $row->agentOffices->map(function ($o) {
                        $name = optional($o->translate(app()->getLocale()))->title
                            ?: optional($o->translate('en'))->title
                            ?: optional($o->translate('ar'))->title
                            ?: $o->manager_name
                            ?: '#' . $o->id;
                        return '<span class="badge bg-info-subtle text-info me-1">' . e($name) . '</span>';
                    })->implode('');

                    return $names;
                })
                ->editColumn('phone', function ($row) {
                    return $row->contact_phone ?? '-';
                })
                ->editColumn('whatsapp', function ($row) {
                    return $row->whatsapp_phone ?? '-';
                })
                ->editColumn('verification_phone', function ($row) {
                    return $row->verification_phone ?? '-';
                })
                ->editColumn('sex_type', function ($row) {
                    return getSexType($row->sex_type);
                })
                ->editColumn('email', function ($row) {
                    return $row->email;
                })
                ->addColumn('note', function ($row) {
                    return $row->note ? e(Str::limit($row->note, 80)) : '-';
                })
                ->addColumn('status', function ($row) {
                    if ($row->trashed()) {
                        return '<span class="badge bg-secondary"> ' . __('ingaz.deleted') . ' </span>';
                    }

                    return $row->status === 'suspended'
                        ? '<span class="badge bg-danger"> ' . __('admin.suspended') . ' </span>'
                        : '<span class="badge bg-success"> ' . __('admin.active') . ' </span>';
                })
                ->addColumn('activity_summary', function ($row) {
                    return $this->renderAdminActivityButton($row, true);
                })
                ->addColumn('last_login_at', function ($row) {
                    return $row->last_login_at ? optional($row->last_login_at)->format('Y-m-d H:i') : '-';
                })
                ->addColumn('last_activity_at', function ($row) {
                    return $row->last_activity_at ? optional($row->last_activity_at)->format('Y-m-d H:i') : '-';
                })
                ->addColumn('current_session_ip', function ($row) {
                    return $row->current_session_ip ?: '-';
                })
                ->addColumn('current_session_device', function ($row) {
                    return $row->current_session_user_agent
                        ? '<span title="' . e($row->current_session_user_agent) . '">' . e(Str::limit($row->current_session_user_agent, 70)) . '</span>'
                        : '-';
                })
                ->addColumn('actions', function ($row) use ($branchesEnabled) {
                    $menuItems = [];

                    $menuItems[] = '<li>' . $this->renderAdminActivityButton($row, false) . '</li>';

                    $adminUser = Auth::guard('admin')->user();
                    $canViewEmployeeAnalytics = Route::has('dashboard.employees.show')
                        && (
                            $adminUser->can(\App\PermKeys::DASHBOARD_VIEW)
                            || $adminUser->can(\App\PermKeys::DASHBOARD_EMPLOYEE_PERFORMANCE)
                            || $adminUser->can(\App\PermKeys::DASHBOARD_MANAGE)
                            || $adminUser->can(\App\PermKeys::DASHBOARD_ANALYTICS)
                        );

                    if ($canViewEmployeeAnalytics) {
                        $menuItems[] = '<li><a href="' . route('dashboard.employees.show', ['employee' => $row->id, 'range' => 'all']) . '" class="dropdown-item text-primary">'
                            . '<i class="fa fa-line-chart"></i> ' . __('admin.analytics_profile')
                            . '</a></li>';
                    }

                    if (Route::has('employees.create') && Auth::guard('admin')->user()->can(\App\PermKeys::HR_MODULE)) {
                        $linkedEmployee = $this->findLinkedHrEmployee((int) $row->id);

                        if ($linkedEmployee && Route::has('employees.edit')) {
                            $menuItems[] = '<li><a href="' . route('employees.edit', $linkedEmployee->id) . '" class="dropdown-item text-success">'
                                . '<i class="fa fa-id-card"></i> ' . __('admin.employee_file')
                                . '</a></li>';
                        } else {
                            $menuItems[] = '<li><a href="' . route('employees.create', ['admin_id' => $row->id]) . '" class="dropdown-item text-success">'
                                . '<i class="fa fa-user-plus"></i> ' . __('admin.hire_user')
                                . '</a></li>';
                        }
                    }

                    if (Auth::guard('admin')->user()->can('users_edit')) {
                        $menuItems[] = '<li><button type="button" class="dropdown-item editButton" data-bs-toggle="modal" data-bs-target="#createModal"'
                            . ' model-route="' . route($this->mainRoute . ".edit", $row->id) . '"'
                            . ' model-title="' . e(__("buttons.edit") . ' ' . $row->name) . '">'
                            . '<i class="fa fa-edit"></i> ' . __('buttons.edit')
                            . '</button></li>';

                        $menuItems[] = '<li><button type="button" class="dropdown-item text-primary admin-quick-role-button"'
                            . ' data-id="' . $row->id . '"'
                            . ' data-name="' . e($row->name) . '"'
                            . ' data-role-id="' . ($row->role_id ?: '') . '"'
                            . ' data-assign-route="' . route($this->mainRoute . ".quick-assign-role", $row->id) . '">'
                            . '<i class="fa fa-shield"></i> ' . __('admin.role_id')
                            . '</button></li>';

                        if ($branchesEnabled) {
                            $menuItems[] = '<li><button type="button" class="dropdown-item text-success admin-quick-branch-button"'
                                . ' data-id="' . $row->id . '"'
                                . ' data-name="' . e($row->name) . '"'
                                . ' data-branch-id="' . ($row->branch_id ?: '') . '"'
                                . ' data-assign-route="' . route($this->mainRoute . ".quick-assign-branch", $row->id) . '">'
                                . '<i class="fa fa-building-o"></i> ' . __('admin.branches')
                                . '</button></li>';
                        }

                        $currentOfficeIds = $row->agentOffices->pluck('id')->toJson();
                        $menuItems[] = '<li><button type="button" class="dropdown-item text-warning admin-quick-office-button"'
                            . ' data-id="' . $row->id . '"'
                            . ' data-name="' . e($row->name) . '"'
                            . ' data-office-ids=\'' . $currentOfficeIds . '\''
                            . ' data-assign-route="' . route($this->mainRoute . ".quick-assign-office", $row->id) . '">'
                            . '<i class="fa fa-briefcase"></i> المكاتب الخارجية'
                            . '</button></li>';
                    }



                    if (Auth::guard('admin')->user()->can('users_edit')) {
                        $menuItems[] = '<li><button type="button" class="dropdown-item text-info admin-reset-password-button"'
                            . ' data-id="' . $row->id . '"'
                            . ' data-name="' . e($row->name) . '"'
                            . ' data-reset-route="' . route($this->mainRoute . ".reset-password", $row->id) . '">'
                            . '<i class="fa fa-key"></i> ' . __('admin.change_password')
                            . '</button></li>';

                        $menuItems[] = '<li><button type="button" class="dropdown-item text-primary admin-send-reset-link-button"'
                            . ' data-id="' . $row->id . '"'
                            . ' data-name="' . e($row->name) . '"'
                            . ' data-reset-link-route="' . route($this->mainRoute . ".send-reset-link", $row->id) . '">'
                            . '<i class="fa fa-envelope"></i> ' . __('admin.send_password_link')
                            . '</button></li>';

                        if ($row->id != Auth::guard('admin')->id()) {
                            $menuItems[] = '<li><button type="button" class="dropdown-item text-warning admin-terminate-session-button"'
                                . ' data-id="' . $row->id . '"'
                                . ' data-name="' . e($row->name) . '"'
                                . ' data-terminate-route="' . route($this->mainRoute . ".terminate-session", $row->id) . '">'
                                . '<i class="fa fa-sign-out"></i> ' . __('admin.terminate_session')
                                . '</button></li>';

                            $menuItems[] = '<li><button type="button" class="dropdown-item text-success admin-impersonate-button"'
                                . ' data-id="' . $row->id . '"'
                                . ' data-name="' . e($row->name) . '"'
                                . ' data-impersonate-route="' . route($this->mainRoute . ".impersonate", $row->id) . '">'
                                . '<i class="fa fa-user-secret"></i> ' . __('admin.impersonate_employee')
                                . '</button></li>';
                        }
  if (Auth::guard('admin')->user()->can('users_delete')) {
                        if ($row->id != Auth::guard('admin')->user()->id) {
                            $menuItems[] = '<li><button type="button" class="dropdown-item text-danger admin-delete-button"'
                                . ' data-id="' . $row->id . '"'
                                . ' data-name="' . e($row->name) . '"'
                                . ' data-delete-route="' . route($this->mainRoute . ".destroy", $row->id) . '"'
                                . ' data-requires-reassignment="0"'
                                . ' data-orders-count="0"'
                                . ' data-bookings-count="0"'
                                . ' data-contracts-count="0"'
                                . ' title="' . e(__('admin.delete')) . '">'
                                . '<i class="fa fa-trash-o"></i> ' . __('ingaz.delete')
                                . '</button></li>';
                        }
                    }
                        $isSuspended = $row->status === 'suspended';
                        $menuItems[] = '<li><a href="' . route("admins.toggle-status", $row->id) . '" class="dropdown-item '
                            . ($isSuspended ? 'text-success' : 'text-warning')
                            . ' toggle-status" data-id="' . $row->id . '"'
                            . ' data-name="' . e($row->name) . '"'
                            . ' data-current-status="' . e((string) $row->status) . '">'
                            . '<i class="fa ' . ($isSuspended ? 'fa-check-circle' : 'fa-ban') . '"></i> '
                            . ($isSuspended ? __('admin.activate') : __('admin.suspend'))
                            . '</a></li>';
                    }

                    return $this->renderAdminActionsDropdown($menuItems);
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("auth.admins");
        $data["canViewTrashed"] = AdminRoutePermissionMatrix::adminHasAnyPermission(
            Auth::guard('admin')->user(),
            [PermKeys::USERS_TRASHED_VIEW, PermKeys::USERS_CONTROL]
        );
        $data["trashedCount"] = $data["canViewTrashed"]
            ? ObjModel::onlyTrashed()->notExternalOfficeAgents()->count()
            : 0;
        $data["addButtonText"] = __("auth.admin");
        $data["canCreateUser"] = $this->canCreateUser();
        $data["userLimit"] = SystemModuleManager::userLimit();
        $data["branchesEnabled"] = SystemModuleManager::isEnabled('branches');
        $data["filters"] = [
            'admin_type' => Constant::globalArray['admin_typeis'],
            'sex_type' => [
                0 => ['ar' => 'ذكر', 'en' => 'Male'],
                1 => ['ar' => 'أنثى', 'en' => 'Female']
            ],
            'status' => [
                'active' => ['ar' => 'نشط', 'en' => 'Active'],
                'suspended' => ['ar' => 'معلق', 'en' => 'Suspended'],
            ],
            'suspension' => true
        ];
        $branchSelector = $this->branchSelectorData($request);
        $data["branches"] = $branchSelector['branches'];
        $reassignmentAdmins = ObjModel::query()
            ->notExternalOfficeAgents()
            ->select('id', 'name')
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->orderBy('name');
        $this->applyRequestBranchFilter($reassignmentAdmins, $request, 'admins');
        $data["reassignmentAdmins"] = $reassignmentAdmins->get();
        $data["roles"] = $this->adminRolesQuery()->get(['id', 'name']);
        $data["recruitmentOffices"] = DB::table('recruitment_offices')
            ->leftJoin('recruitment_office_translations as t', function ($j) {
                $j->on('t.rec_office_id', '=', 'recruitment_offices.id')
                  ->where('t.locale', app()->getLocale());
            })
            ->select('recruitment_offices.id', DB::raw('COALESCE(t.title, recruitment_offices.manager_name, CONCAT("#", recruitment_offices.id)) as label'))
            ->orderBy('recruitment_offices.id')
            ->get();
        return view($this->folderPath . '.index', $data);
    }

    public function toggleStatus($id, Request $request)
    {
        $admin = ObjModel::findOrFail($id);
        $newStatus = $admin->status === 'suspended' ? 'active' : 'suspended';

        $validator = Validator::make($request->all(), [
            'note' => [$newStatus === 'suspended' ? 'required' : 'nullable', 'string', 'max:1000'],
            'reassign_work' => [$newStatus === 'suspended' ? 'required' : 'nullable', 'boolean'],
            'reassigned_admin_id' => [
                'nullable',
                'required_if:reassign_work,1',
                'integer',
                function ($attribute, $value, $fail) use ($admin) {
                    if ($value === null || $value === '') {
                        return;
                    }

                    if ((int) $value === (int) $admin->id) {
                        $fail(__('admin.replacement_employee_must_be_different'));
                        return;
                    }

                    $exists = ObjModel::query()
                        ->whereKey($value)
                        ->whereNull('deleted_at')
                        ->where('status', 'active')
                        ->exists();

                    if (! $exists) {
                        $fail(__('admin.replacement_employee_invalid'));
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        DB::transaction(function () use ($admin, $request, $newStatus) {
            if ($newStatus === 'suspended' && $request->boolean('reassign_work')) {
                $this->reassignAdminActiveWork((int) $admin->id, (int) $request->reassigned_admin_id);
            }

            $admin->update([
                'status' => $newStatus,
                'note' => $request->note ?? $admin->note,
            ]);
        });

        return response()->json([
            'message' => __('admin.updated'),
            'status' => $newStatus,
        ]);
    }


    public function create(Request $request, ObjAction $action)
    {
        if (! $this->canCreateUser()) {
            return response()->json([
                'message' => $this->userLimitMessage(),
                'errors' => ['users_limit' => [$this->userLimitMessage()]],
            ], 422);
        }

        if ($request->ajax()) {
            $roles = $this->adminRolesQuery()->pluck('name', 'id')->all();
            $branchesEnabled = SystemModuleManager::isEnabled('branches');
            $branches = $branchesEnabled
                ? Branch::query()->active()->ordered()->get(['id', 'name_ar', 'name_en'])
                : collect();
            $admin_typeis = $action->getAdminTypies(); // Assuming this method exists in ObjAction
            $returnHTML = view($this->folderPath . ".create", compact('roles', 'branches', 'branchesEnabled', 'admin_typeis'))->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function store(StoreRequest $request, ObjAction $action)
    {
        if (! $this->canCreateUser()) {
            return response()->json([
                'message' => $this->userLimitMessage(),
                'errors' => ['users_limit' => [$this->userLimitMessage()]],
            ], 422);
        }

        $dataInsert = $request->only($this->postData);
        $data = $action->storeAdmin($dataInsert, $request);
        return jsonSuccess($data);
    }


    public function show($id)
    {
        //
    }


    public function edit($id, Request $request, ObjAction $action)
    {
        $obj = $action->find($id);
        $roles = $this->adminRolesQuery()->pluck('name', 'id')->all();
        $branchesEnabled = SystemModuleManager::isEnabled('branches');
        $branches = $branchesEnabled
            ? Branch::query()->active()->ordered()->get(['id', 'name_ar', 'name_en'])
            : collect();
        $adminRole = $obj->roles->pluck('id')->toArray();
        $admin_typeis = $action->getAdminTypies();
        $hasInvalidCurrentRole = $obj->role && $obj->role->guard_name !== 'admin';

        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".edit", compact('obj', 'roles', 'branches', 'branchesEnabled', 'adminRole', 'admin_typeis', 'hasInvalidCurrentRole'))->render();
            return jsonSuccess(["html" => $returnHTML]);
        }

        return view($this->folderPath . ".edit_page", compact('obj', 'roles', 'branches', 'branchesEnabled', 'adminRole', 'admin_typeis', 'hasInvalidCurrentRole'));
    }


    public function update(UpdateRequest $request, $id, ObjAction $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->updateAdmin($id, $dataInsert, $request);
        return jsonSuccess($data);
    }


    public function destroy($id, Request $request, ObjAction $action)
    {
        $admin = ObjModel::findOrFail($id);

        if ((int) $admin->id === (int) Auth::guard('admin')->id()) {
            return response()->json([
                'message' => __('admin.dontDeleteYourself'),
                'errors' => [
                    'admin' => [__('admin.dontDeleteYourself')],
                ],
            ], 422);
        }

        $assignmentCounts = $this->getReassignmentCounts((int) $admin->id);
        if ($assignmentCounts['total'] > 0) {
            $validator = Validator::make($request->all(), [
                'reassigned_admin_id' => [
                    'required',
                    'integer',
                    function ($attribute, $value, $fail) use ($admin) {
                        if ((int) $value === (int) $admin->id) {
                            $fail(__('admin.replacement_employee_must_be_different'));
                            return;
                        }

                        $exists = ObjModel::query()
                            ->whereKey($value)
                            ->whereNull('deleted_at')
                            ->where('status', 'active')
                            ->exists();

                        if (!$exists) {
                            $fail(__('admin.replacement_employee_invalid'));
                        }
                    },
                ],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => __('admin.replacement_required_before_delete'),
                    'requires_reassignment' => true,
                    'assignment_counts' => $assignmentCounts,
                    'errors' => $validator->errors(),
                ], 422);
            }
        }

        DB::transaction(function () use ($admin, $request, $assignmentCounts) {
            if ($assignmentCounts['total'] > 0) {
                $this->reassignAdminWork((int) $admin->id, (int) $request->reassigned_admin_id);
            }

            $admin->delete();
        });

        return jsonSuccess(null, __('admin.employee_deleted_restorable'));
    }

    public function forceDelete($id, Request $request)
    {
        $admin = ObjModel::withTrashed()->findOrFail($id);

        if ((int) $admin->id === (int) Auth::guard('admin')->id()) {
            return response()->json([
                'message' => __('admin.dontDeleteYourself'),
                'errors' => [
                    'admin' => [__('admin.dontDeleteYourself')],
                ],
            ], 422);
        }

        $assignmentCounts = $this->getReassignmentCounts((int) $admin->id);
        if ($assignmentCounts['total'] > 0) {
            $validator = Validator::make($request->all(), [
                'reassigned_admin_id' => [
                    'required',
                    'integer',
                    function ($attribute, $value, $fail) use ($admin) {
                        if ((int) $value === (int) $admin->id) {
                            $fail(__('admin.replacement_employee_must_be_different'));
                            return;
                        }

                        $exists = ObjModel::query()
                            ->whereKey($value)
                            ->whereNull('deleted_at')
                            ->where('status', 'active')
                            ->exists();

                        if (!$exists) {
                            $fail(__('admin.replacement_employee_invalid'));
                        }
                    },
                ],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => __('admin.replacement_required_before_force_delete'),
                    'requires_reassignment' => true,
                    'assignment_counts' => $assignmentCounts,
                    'errors' => $validator->errors(),
                ], 422);
            }
        }

        DB::transaction(function () use ($admin, $request, $assignmentCounts) {
            if ($assignmentCounts['total'] > 0) {
                $this->reassignAdminWork((int) $admin->id, (int) $request->reassigned_admin_id);
            }

            if (Schema::hasTable('model_has_roles')) {
                DB::table('model_has_roles')
                    ->where('model_id', $admin->id)
                    ->where('model_type', ObjModel::class)
                    ->delete();
            }

            if (Schema::hasTable('model_has_permissions')) {
                DB::table('model_has_permissions')
                    ->where('model_id', $admin->id)
                    ->where('model_type', ObjModel::class)
                    ->delete();
            }

            $admin->forceDelete();
        });

        return jsonSuccess(null, __('admin.employee_force_deleted_successfully'));
    }

    public function restore($id)
    {
        $admin = ObjModel::withTrashed()->findOrFail($id);
        if (!$admin->trashed()) {
            $message = __('admin.employee_not_deleted');
            return response()->json([
                'message' => $message,
                'errors' => [
                    'admin' => [$message],
                ],
            ], 422);
        }

        if (! $this->canCreateUser()) {
            return response()->json([
                'message' => $this->userLimitMessage(),
                'errors' => ['users_limit' => [$this->userLimitMessage()]],
            ], 422);
        }

        // لو أُنشئ حساب نشط بنفس البريد/اسم المستخدم بعد الحذف، فالاستعادة ستنتج حسابين متطابقين
        // ويصبح الدخول بالبريد ملتبساً؛ نوقفها برسالة واضحة بدل السماح بالتكرار.
        $conflicts = [];
        foreach (['email' => __('auth.email'), 'username' => __('admin.username')] as $column => $label) {
            if (blank($admin->{$column})) {
                continue;
            }

            $duplicate = ObjModel::query()
                ->where($column, $admin->{$column})
                ->whereKeyNot($admin->id)
                ->first(['id', 'name']);

            if ($duplicate) {
                $conflicts[] = __('admin.trashed_admins.restore_conflict', [
                    'field' => $label,
                    'value' => $admin->{$column},
                    'name' => $duplicate->name,
                ]);
            }
        }

        if ($conflicts !== []) {
            return response()->json([
                'message' => __('admin.trashed_admins.restore_blocked'),
                'errors' => ['admin' => $conflicts],
            ], 422);
        }

        $admin->restore();
        return jsonSuccess(['id' => $admin->id], __('admin.employee_restored_successfully', ['name' => $admin->name]));
    }

    private function canCreateUser(): bool
    {
        $limit = SystemModuleManager::userLimit();

        return $limit === null || ObjModel::query()->count() < $limit;
    }

    private function userLimitMessage(): string
    {
        $limit = SystemModuleManager::userLimit();

        return $limit === null
            ? 'لا يمكن إضافة مستخدم جديد حاليًا.'
            : "تم الوصول إلى الحد الأقصى لعدد المستخدمين في هذه النسخة ({$limit}).";
    }

    public function resetPassword($id, Request $request)
    {
        $this->authorize(PermKeys::USERS_EDIT);

        $admin = ObjModel::findOrFail($id);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $admin->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => null,
            'reset_password_token' => null,
            'current_session_id' => 'terminated:' . Str::random(40),
            'current_session_ip' => null,
            'current_session_user_agent' => null,
            'last_activity_at' => null,
        ])->save();

        return jsonSuccess(null, __('admin.password_changed_sessions_terminated'));
    }

    public function sendResetLink($id)
    {
        $admin = ObjModel::findOrFail($id);

        if (empty($admin->email)) {
            return response()->json(['message' => __('admin.employee_email_missing')], 422);
        }

        $token = Str::random(64);
        $admin->forceFill([
            'reset_password_token' => $token,
        ])->save();

        $resetUrl = route('admin.password.reset', [
            'token' => $token,
            'email' => $admin->email,
        ]);

        try {
            Mail::to($admin->email)->send(new ResetPasswordMail($resetUrl));
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(['message' => __('admin.reset_password_email_failed')], 422);
        }

        return jsonSuccess(null, __('admin.password_link_sent_successfully'));
    }

    public function terminateSession($id)
    {
        $admin = ObjModel::findOrFail($id);

        $admin->forceFill([
            'remember_token' => null,
            'current_session_id' => 'terminated:' . Str::random(40),
            'current_session_ip' => null,
            'current_session_user_agent' => null,
            'last_activity_at' => null,
        ])->save();

        return jsonSuccess(null, __('admin.employee_session_terminated'));
    }

    public function impersonate($id, Request $request)
    {
        $this->authorize(PermKeys::USERS_EDIT);

        $admin = ObjModel::findOrFail($id);

        if ((int) $admin->id === (int) Auth::guard('admin')->id()) {
            return response()->json(['message' => __('admin.already_logged_in_as_user')], 422);
        }

        if (($admin->status ?? 'active') === 'suspended') {
            return response()->json(['message' => __('admin.cannot_impersonate_suspended_employee')], 422);
        }

        activity('authentication')->causedBy(Auth::guard('admin')->user())
            ->performedOn($admin)->event('impersonated')
            ->withProperties(['impersonator_admin_id' => Auth::guard('admin')->id(), 'target_admin_id' => $admin->id])
            ->log('الدخول بحساب مستخدم آخر');

        $request->session()->put('impersonator_admin_id', Auth::guard('admin')->id());
        Auth::guard('admin')->login($admin);
        $this->startAdminSession($request, $admin);

        return jsonSuccess(['url' => $this->defaultAdminRedirectUrl($admin)], __('admin.impersonated_employee_successfully'));
    }

    private function renderAdminActionsDropdown(array $menuItems): string
    {
        if ($menuItems === []) {
            return '<span class="text-muted small">' . __('admin.no_actions') . '</span>';
        }

        return '<div class="admin-actions-dropdown">'
            . '<button class="btn btn-sm btn-primary admin-actions-trigger actions-btn" type="button" aria-haspopup="true" aria-expanded="false" title="' . e(__('admin.available_actions')) . '">'
            . '<i class="fas fa-cogs me-1"></i> ' . __("ingaz.actions")
            . '</button>'
            . '<ul class="admin-actions-template">'
            . implode('', $menuItems)
            . '</ul>'
            . '</div>';
    }

    private function renderAdminActivityButton($row, bool $compact = false): string
    {
        $isOnline = $this->isAdminSessionActive($row);
        $statusText = $isOnline ? __('admin.online_now') : __('admin.offline');
        $buttonClass = $compact
            ? 'btn btn-sm ' . ($isOnline ? 'btn-success' : 'btn-outline-secondary') . ' admin-activity-button admin-activity-icon'
            : 'dropdown-item text-secondary admin-activity-button';
        $label = $compact
            ? '<i class="fas fa-signal"></i>'
            : '<i class="fas fa-history"></i> ' . __('admin.activity');

        return '<button type="button" class="' . $buttonClass . '"'
            . ' data-name="' . e($row->name) . '"'
            . ' data-status="' . e($statusText) . '"'
            . ' data-online="' . ($isOnline ? 1 : 0) . '"'
            . ' data-last-login="' . e($row->last_login_at ? optional($row->last_login_at)->format('Y-m-d H:i') : '-') . '"'
            . ' data-last-activity="' . e($row->last_activity_at ? optional($row->last_activity_at)->format('Y-m-d H:i') : '-') . '"'
            . ' data-ip="' . e($row->current_session_ip ?: '-') . '"'
            . ' data-device="' . e($row->current_session_user_agent ?: '-') . '"'
            . ' title="' . e(__('admin.activity')) . '">'
            . $label
            . '</button>';
    }

    private function findLinkedHrEmployee(int $adminId)
    {
        if (!class_exists(\Nami\Hr\Models\Employee::class)
            || !Schema::hasTable('hr_employee')
            || !Schema::hasColumn('hr_employee', 'admin_id')) {
            return null;
        }

        return \Nami\Hr\Models\Employee::query()
            ->withAllCategories()
            ->where('admin_id', $adminId)
            ->first();
    }

    private function getReassignmentCounts(int $adminId): array
    {
        $ordersCount = Order::query()
            ->where(function ($query) use ($adminId) {
                if (Schema::hasColumn('orders', 'customer_id')) {
                    $query->where('customer_id', $adminId);
                }

                if (Schema::hasTable('order_details') && Schema::hasColumn('order_details', 'admin_id')) {
                    $query->orWhereHas('details', function ($detailsQuery) use ($adminId) {
                        $detailsQuery->where('admin_id', $adminId);
                    });
                }
            })
            ->count();

        $bookingsCount = Schema::hasTable('rental_contracts') && Schema::hasColumn('rental_contracts', 'marketer_id')
            ? RentalContract::query()->where('marketer_id', $adminId)->count()
            : 0;

        $contractsCount = Schema::hasTable('contracts') && Schema::hasColumn('contracts', 'marketer_id')
            ? Contract::query()->where('marketer_id', $adminId)->count()
            : 0;

        return [
            'orders' => $ordersCount,
            'bookings' => $bookingsCount,
            'contracts' => $contractsCount,
            'total' => $ordersCount + $bookingsCount + $contractsCount,
        ];
    }

    private function reassignAdminWork(int $fromAdminId, int $toAdminId): void
    {
        if (Schema::hasColumn('orders', 'customer_id')) {
            \App\Support\Audit\AuditedBulkUpdate::update(
                DB::table('orders')->where('customer_id', $fromAdminId),
                \App\Models\ingaz\Order::class,
                ['customer_id' => $toAdminId, 'updated_at' => now()]
            );
        }

        if (Schema::hasTable('order_details') && Schema::hasColumn('order_details', 'admin_id')) {
            \App\Support\Audit\AuditedBulkUpdate::update(
                DB::table('order_details')->where('admin_id', $fromAdminId),
                \App\Models\ingaz\OrderDetail::class,
                ['admin_id' => $toAdminId, 'updated_at' => now()]
            );
        }

        if (Schema::hasTable('contracts') && Schema::hasColumn('contracts', 'marketer_id')) {
            \App\Support\Audit\AuditedBulkUpdate::update(
                DB::table('contracts')->where('marketer_id', $fromAdminId),
                \App\Models\Contract::class,
                ['marketer_id' => $toAdminId, 'updated_at' => now()]
            );
        }

        if (Schema::hasTable('rental_contracts') && Schema::hasColumn('rental_contracts', 'marketer_id')) {
            \App\Support\Audit\AuditedBulkUpdate::update(
                DB::table('rental_contracts')->where('marketer_id', $fromAdminId),
                \App\Models\RentalContract::class,
                ['marketer_id' => $toAdminId, 'updated_at' => now()]
            );
        }
    }

    private function reassignAdminActiveWork(int $fromAdminId, int $toAdminId): void
    {
        if (Schema::hasColumn('orders', 'customer_id')) {
            \App\Support\Audit\AuditedBulkUpdate::update(
                DB::table('orders')
                    ->where('customer_id', $fromAdminId)
                    ->whereNotIn('status', ['done', 'refused', 'return', 'cancelled', 'canceled', 'completed']),
                \App\Models\ingaz\Order::class,
                ['customer_id' => $toAdminId, 'updated_at' => now()]
            );
        }

        if (Schema::hasTable('order_details') && Schema::hasColumn('order_details', 'admin_id')) {
            $activeOrderIds = DB::table('orders')
                ->whereNotIn('status', ['done', 'refused', 'return', 'cancelled', 'canceled', 'completed'])
                ->select('id');
            \App\Support\Audit\AuditedBulkUpdate::update(
                DB::table('order_details')->where('admin_id', $fromAdminId)->whereIn('order_id', $activeOrderIds),
                \App\Models\ingaz\OrderDetail::class,
                ['admin_id' => $toAdminId, 'updated_at' => now()]
            );
        }

        if (Schema::hasTable('contracts') && Schema::hasColumn('contracts', 'marketer_id')) {
            \App\Support\Audit\AuditedBulkUpdate::update(
                DB::table('contracts')->where('marketer_id', $fromAdminId)
                    ->whereNotIn('status', ['completed', 'back', 'closed']),
                \App\Models\Contract::class,
                ['marketer_id' => $toAdminId, 'updated_at' => now()]
            );
        }

        if (Schema::hasTable('rental_contracts') && Schema::hasColumn('rental_contracts', 'marketer_id')) {
            \App\Support\Audit\AuditedBulkUpdate::update(
                DB::table('rental_contracts')->where('marketer_id', $fromAdminId)
                    ->whereNotIn('status', ['completed', 'cancelled', 'closed']),
                \App\Models\RentalContract::class,
                ['marketer_id' => $toAdminId, 'updated_at' => now()]
            );
        }
    }

    private function adminRolesQuery()
    {
        return Role::query()
            ->where('guard_name', 'admin')
            ->orderByRaw("CASE WHEN name IN ('Administrator', 'super_admin') THEN 0 ELSE 1 END")
            ->orderBy('name');
    }

    /** login */
    public function formLogin()
    {
        if (Auth::guard('admin')->check()) {
            return redirect($this->defaultAdminRedirectUrl(Auth::guard('admin')->user()));
        }
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {

        $this->validate($request, [
            'login' => 'required',
            'password' => 'required'
        ]);
        $login = trim((string) $request->login);
        $usernameColumnExists = Schema::hasColumn('admins', 'username');

        $agentUser = objModel::query()
            ->whereHas('agentOfficeLinks')
            ->where(function ($query) use ($login, $usernameColumnExists) {
                $query->where('phone', $login)
                    ->orWhere('email', $login);

                if ($usernameColumnExists) {
                    $query->orWhere('username', $login);
                }
            })
            ->first();

        if ($agentUser && $agentUser->isExternalOfficeOnlyAgent()) {
            if (($agentUser->status ?? 'active') === 'suspended') {
                return jsonSuccess(null, 'Ù‡Ø°Ø§ Ø§Ù„Ø­Ø³Ø§Ø¨ Ù…ÙˆÙ‚ÙˆÙ Ø­Ø§Ù„ÙŠØ§Ù‹.', 403);
            }

            if (auth()->guard("agent")->attempt(["email" => $agentUser->email, "password" => $request->password], $request->boolean('rememberme'))) {
                $request->session()->regenerate();

                return jsonSuccess([
                    'url' => \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL(app()->getLocale(), route('agent.dashboard'), [], true),
                ], app()->getLocale() === 'ar' ? 'تم تسجيل الدخول للوحة الوكيل الخارجي' : 'Agent login successful');
            }

            return jsonSuccess(null, __("auth.password"), 402);
        }

        $user = objModel::query()
            ->where(function ($query) use ($login, $usernameColumnExists) {
                $query->where('phone', $login)
                    ->orWhere('email', $login);

                if ($usernameColumnExists) {
                    $query->orWhere('username', $login);
                }
            })
            ->first();
        if ($user) {
            if ($user->isExternalOfficeOnlyAgent()) {
                if (($user->status ?? 'active') === 'suspended') {
                    return jsonSuccess(null, __('admin.account_suspended_now'), 403);
                }

                if (auth()->guard("agent")->attempt(["email" => $user->email, "password" => $request->password], $request->boolean('rememberme'))) {
                    $request->session()->regenerate();

                    return jsonSuccess([
                        'url' => \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL(app()->getLocale(), route('agent.dashboard'), [], true),
                    ], app()->getLocale() === 'ar' ? 'تم تسجيل الدخول للوحة الوكيل الخارجي' : 'Agent login successful');
                }

                return jsonSuccess(null, __("auth.password"), 402);
            }

            if (Hash::check((string) $request->password, (string) $user->password)) {
                if (($user->status ?? 'active') === 'suspended') {
                    return jsonSuccess(null, __('admin.account_suspended_now'), 403);
                }

                Auth::guard('admin')->login($user, $request->boolean('rememberme'));
                $this->startAdminSession($request, $user);

                $redirectUrl = SystemModuleManager::defaultDashboardKey() !== 'finance'
                    && $user->admin_type === 'employee'
                    && Route::has('employeeProfile.index')
                    ? route('employeeProfile.index')
                    : $this->defaultAdminRedirectUrl($user);

                return jsonSuccess(['url' => $redirectUrl], __("auth.login successful"));
            }
            return jsonSuccess(null, __("auth.password"), 402);
        }
        return jsonSuccess(null, __("auth.failed email"), 401);
    }

    public function quickAssignRole(Request $request, $id)
    {
        $this->authorize('users_edit');
        $admin = ObjModel::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'role_id' => [
                'nullable',
                'integer',
                Rule::exists('roles', 'id')->where('guard_name', 'admin'),
            ],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $roleId = $request->filled('role_id') ? (int) $validator->validated()['role_id'] : null;
        $role = $roleId ? Role::query()->where('guard_name', 'admin')->findOrFail($roleId) : null;

        DB::transaction(function () use ($admin, $role) {
            // Spatie treats integers as role names in syncRoles(), so pass the
            // resolved Role model while keeping the legacy role_id in sync.
            $admin->syncRoles($role ? [$role] : []);
            $admin->update(['role_id' => $role?->id]);
        });

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return jsonSuccess(null, __('admin.updated_successfully'));
    }

    public function quickAssignOffice(Request $request, $id)
    {
        $this->authorize('users_edit');
        $admin = ObjModel::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'office_ids'   => ['nullable', 'array'],
            'office_ids.*' => ['integer'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $ids = collect($request->input('office_ids', []))->filter()->unique()->values();

        DB::table('admin_offices')->where('admin_id', $admin->id)->delete();

        if ($ids->isNotEmpty()) {
            $rows = $ids->map(fn ($oid) => [
                'admin_id'   => $admin->id,
                'office_id'  => $oid,
                'created_at' => now(),
                'updated_at' => now(),
            ])->toArray();
            DB::table('admin_offices')->insert($rows);
        }

        return jsonSuccess(null, __('admin.updated_successfully'));
    }

    public function quickAssignBranch(Request $request, $id)
    {
        $this->authorize('users_edit');
        $admin = ObjModel::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $admin->update(['branch_id' => $request->filled('branch_id') ? $request->branch_id : null]);

        return jsonSuccess(null, __('admin.updated_successfully'));
    }

    private function defaultAdminRedirectUrl($admin): string
    {
        if (SystemModuleManager::defaultDashboardKey() === 'finance') {
            return SystemModuleManager::defaultDashboardUrl();
        }

        return AdminRoutePermissionMatrix::firstAccessibleUrlForAdmin($admin)
            ?? route('admin.no-permissions');
    }

    private function startAdminSession(Request $request, $admin): void
    {
        $request->session()->regenerate();
        $request->session()->put('admin_session_checked_at', time());

        if (! $admin || ! Schema::hasColumn('admins', 'current_session_id')) {
            return;
        }

        $admin->forceFill([
            'current_session_id' => $request->session()->getId(),
            'current_session_ip' => $request->ip(),
            'current_session_user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'last_login_at' => now(),
            'last_activity_at' => now(),
        ])->save();
    }

    private function isAdminSessionActive($admin): bool
    {
        if (
            ! $admin
            || ! Schema::hasColumn('admins', 'current_session_id')
            || empty($admin->current_session_id)
            || Str::startsWith((string) $admin->current_session_id, 'terminated:')
        ) {
            return false;
        }

        $lastActivity = Schema::hasColumn('admins', 'last_activity_at')
            ? ($admin->last_activity_at ?: $admin->last_login_at)
            : $admin->last_login_at;

        if (! $lastActivity) {
            return false;
        }

        return $lastActivity->greaterThanOrEqualTo(now()->subMinutes((int) config('session.lifetime', 120)));
    }

    public function logout()
    {
        $admin = Auth::guard('admin')->user();
        if ($admin && Schema::hasColumn('admins', 'current_session_id') && $admin->current_session_id === request()->session()->getId()) {
            $admin->forceFill([
                'current_session_id' => null,
                'current_session_ip' => null,
                'current_session_user_agent' => null,
                'last_activity_at' => null,
            ])->save();
        }

        Session::flush();

        Auth::guard('admin')->logout();

        return redirect(route('admin.form.login'));
    }
}
