<?php

namespace App\Http\Controllers\admin\auth;

use App\Helpers\Constant;
use App\Http\Actions\auth\AdminAction as ObjAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\auth\profile\StoreAdminRequest as StoreRequest;
use App\Http\Requests\auth\profile\UpdateAdminRequest as UpdateRequest;
use App\Models\auth\Admin as objModel;
use App\Models\Contract;
use App\Models\ingaz\Order;
use App\Models\RentalContract;
use App\Support\AdminRoutePermissionMatrix;
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
use App\Mail\ResetPasswordMail;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\DataTables;


class AdminController extends Controller
{
    public $folderPath = "admin.auth.admin";
    public $postData = ["name", "username", "phone", "email", 'whatsapp', 'verification_phone', 'identity_number', 'identity_expiry_date', "password", "admin_type", 'sex_type', 'role_id', 'sort', 'status', 'note'];
    public $mainRoute = "admins";
    public $suspensionReasons = ['violation', 'manual_review', 'fraud_suspect', 'other'];


    function __construct()
    {
        $this->middleware('permission:users_view,admin', ['only' => ['index']]);
        $this->middleware('permission:users_create,admin', ['only' => ['create', 'store']]);
        $this->middleware('permission:users_edit,admin', ['only' => ['edit', 'update']]);
        $this->middleware('permission:users_delete,admin', ['only' => ['destroy', 'restore', 'forceDelete']]);
    }


    public function index(Request $request, ObjAction $action)
    {
        if ($request->ajax()) {
            $dataTable = ObjModel::query()->with('role')->orderBy('id', 'asc');

            // Filters
            if ($request->filled('admin_type')) {
                $dataTable->where('admin_type', $request->admin_type);
            }
            if ($request->filled('sex_type')) {
                $dataTable->where('sex_type', $request->sex_type);
            }

            $statusFilter = $request->get('status');
            if (!$statusFilter && $request->has('suspended') && $request->boolean('suspended')) {
                $statusFilter = 'suspended';
            }

            if ($statusFilter === 'deleted') {
                $dataTable->onlyTrashed();
            } elseif (in_array($statusFilter, ['active', 'suspended'], true)) {
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
                ->addColumn('actions', function ($row) {
                    $menuItems = [];

                    if ($row->trashed()) {
                        if (Auth::guard('admin')->user()->can('users_delete')) {
                            $menuItems[] = '<li><button type="button" class="dropdown-item restore-admin" data-id="' . $row->id . '">'
                                . '<i class="fa fa-undo"></i> ' . __('ingaz.restore')
                                . '</button></li>';
                            $reassignmentCounts = $this->getReassignmentCounts((int) $row->id);
                            $menuItems[] = '<li><button type="button" class="dropdown-item text-danger admin-force-delete-button"'
                                . ' data-id="' . $row->id . '"'
                                . ' data-name="' . e($row->name) . '"'
                                . ' data-delete-route="' . route($this->mainRoute . ".force-delete", $row->id) . '"'
                                . ' data-requires-reassignment="' . ($reassignmentCounts['total'] > 0 ? 1 : 0) . '"'
                                . ' data-orders-count="' . $reassignmentCounts['orders'] . '"'
                                . ' data-bookings-count="' . $reassignmentCounts['bookings'] . '"'
                                . ' data-contracts-count="' . $reassignmentCounts['contracts'] . '"'
                                . ' title="' . e(__('admin.force_delete')) . '">'
                                . '<i class="fa fa-trash-o"></i> ' . __('admin.force_delete')
                                . '</button></li>';
                        }

                        return $this->renderAdminActionsDropdown($menuItems);
                    }

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
                            . '<i class="fa fa-pencil"></i> ' . __('buttons.edit')
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
                            $reassignmentCounts = $this->getReassignmentCounts((int) $row->id);
                            $menuItems[] = '<li><button type="button" class="dropdown-item text-danger admin-delete-button"'
                                . ' data-id="' . $row->id . '"'
                                . ' data-name="' . e($row->name) . '"'
                                . ' data-delete-route="' . route($this->mainRoute . ".destroy", $row->id) . '"'
                                . ' data-requires-reassignment="' . ($reassignmentCounts['total'] > 0 ? 1 : 0) . '"'
                                . ' data-orders-count="' . $reassignmentCounts['orders'] . '"'
                                . ' data-bookings-count="' . $reassignmentCounts['bookings'] . '"'
                                . ' data-contracts-count="' . $reassignmentCounts['contracts'] . '"'
                                . ' title="' . e(__('admin.delete')) . '">'
                                . '<i class="fa fa-trash-o"></i> ' . __('ingaz.delete')
                                . '</button></li>';
                        }
                    }
                        $isSuspended = $row->status === 'suspended';
                        $menuItems[] = '<li><a href="' . route("admins.toggle-status", $row->id) . '" class="dropdown-item '
                            . ($isSuspended ? 'text-success' : 'text-warning')
                            . ' toggle-status" data-id="' . $row->id . '">'
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
        $data["addButtonText"] = __("auth.admin");
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
        $data["reassignmentAdmins"] = ObjModel::query()
            ->select('id', 'name')
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        return view($this->folderPath . '.index', $data);
    }

    public function toggleStatus($id, Request $request)
    {
        $admin = ObjModel::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $newStatus = $admin->status === 'suspended' ? 'active' : 'suspended';

        if ($newStatus === 'suspended') {
            $assignmentCounts = $this->getActiveAssignmentCounts((int) $admin->id);
            if ($assignmentCounts['total'] > 0) {
                return $this->assignmentBlockedResponse($assignmentCounts);
            }
        }

        $admin->update([
            'status' => $newStatus,
            'note' => $request->note ?? $admin->note,
        ]);

        return response()->json([
            'message' => __('admin.updated'),
            'status' => $newStatus,
        ]);
    }


    public function create(Request $request, ObjAction $action)
    {
        if ($request->ajax()) {
            $roles = $this->adminRolesQuery()->pluck('name', 'id')->all();
            $admin_typeis = $action->getAdminTypies(); // Assuming this method exists in ObjAction
            $returnHTML = view($this->folderPath . ".create", compact('roles', 'admin_typeis'))->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function store(StoreRequest $request, ObjAction $action)
    {
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
        if ($request->ajax()) {
            $obj = $action->find($id);
            $roles = $this->adminRolesQuery()->pluck('name', 'id')->all();
            $adminRole = $obj->roles->pluck('id')->toArray();
            $admin_typeis = $action->getAdminTypies();
            $hasInvalidCurrentRole = $obj->role && $obj->role->guard_name !== 'admin';
            $returnHTML = view($this->folderPath . ".edit", compact('obj', 'roles', 'adminRole', 'admin_typeis', 'hasInvalidCurrentRole'))->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
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

        $admin->restore();
        return jsonSuccess(['id' => $admin->id], __('admin.employee_restored_successfully'));
    }

    public function resetPassword($id, Request $request)
    {
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
        ])->save();

        return jsonSuccess(null, __('admin.employee_session_terminated'));
    }

    public function impersonate($id, Request $request)
    {
        $admin = ObjModel::findOrFail($id);

        if ((int) $admin->id === (int) Auth::guard('admin')->id()) {
            return response()->json(['message' => __('admin.already_logged_in_as_user')], 422);
        }

        if (($admin->status ?? 'active') === 'suspended') {
            return response()->json(['message' => __('admin.cannot_impersonate_suspended_employee')], 422);
        }

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

    private function getActiveAssignmentCounts(int $adminId): array
    {
        $ordersQuery = Order::query()
            ->whereNotIn('status', ['done', 'refused', 'return', 'cancelled', 'canceled', 'completed'])
            ->where(function ($query) use ($adminId) {
                if (Schema::hasColumn('orders', 'customer_id')) {
                    $query->where('customer_id', $adminId)
                        ->orWhereHas('details', function ($detailsQuery) use ($adminId) {
                            $detailsQuery->where('admin_id', $adminId);
                        });
                    return;
                }

                $query->whereHas('details', function ($detailsQuery) use ($adminId) {
                    $detailsQuery->where('admin_id', $adminId);
                });
            });

        $ordersCount = $ordersQuery->count();

        $bookingsCount = Schema::hasTable('rental_contracts')
            ? RentalContract::query()
                ->where('marketer_id', $adminId)
                ->whereNotIn('status', ['completed', 'cancelled', 'closed'])
                ->count()
            : 0;

        $contractsCount = Schema::hasTable('contracts')
            ? Contract::query()
                ->where('marketer_id', $adminId)
                ->whereNotIn('status', ['completed', 'back', 'closed'])
                ->count()
            : 0;

        return [
            'orders' => $ordersCount,
            'bookings' => $bookingsCount,
            'contracts' => $contractsCount,
            'total' => $ordersCount + $bookingsCount + $contractsCount,
        ];
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
            DB::table('orders')
                ->where('customer_id', $fromAdminId)
                ->update([
                    'customer_id' => $toAdminId,
                    'updated_at' => now(),
                ]);
        }

        if (Schema::hasTable('order_details') && Schema::hasColumn('order_details', 'admin_id')) {
            DB::table('order_details')
                ->where('admin_id', $fromAdminId)
                ->update([
                    'admin_id' => $toAdminId,
                    'updated_at' => now(),
                ]);
        }

        if (Schema::hasTable('contracts') && Schema::hasColumn('contracts', 'marketer_id')) {
            DB::table('contracts')
                ->where('marketer_id', $fromAdminId)
                ->update([
                    'marketer_id' => $toAdminId,
                    'updated_at' => now(),
                ]);
        }

        if (Schema::hasTable('rental_contracts') && Schema::hasColumn('rental_contracts', 'marketer_id')) {
            DB::table('rental_contracts')
                ->where('marketer_id', $fromAdminId)
                ->update([
                    'marketer_id' => $toAdminId,
                    'updated_at' => now(),
                ]);
        }
    }

    private function assignmentBlockedResponse(array $assignmentCounts)
    {
        $message = __('admin.reassignment_required_before_action', [
            'orders' => $assignmentCounts['orders'],
            'bookings' => $assignmentCounts['bookings'],
            'contracts' => $assignmentCounts['contracts'],
        ]);

        return response()->json([
            'message' => $message,
            'errors' => [
                'admin' => [$message],
            ],
        ], 422);
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
        $user = objModel::query()
            ->where('email', $login)
            ->orWhere('phone', $login)
            ->when(Schema::hasColumn('admins', 'username'), function ($query) use ($login) {
                $query->orWhere('username', $login);
            })
            ->first();
        if ($user) {
            if ($user->isExternalOfficeOnlyAgent()) {
                if (($user->status ?? 'active') === 'suspended') {
                    return jsonSuccess(null, __('admin.account_suspended_now'), 403);
                }

                if (auth()->guard("agent")->attempt(["email" => $user->email, "password" => $request->password], $request->boolean('rememberme'))) {
                    $request->session()->regenerate();

                    return jsonSuccess(['url' => route('agent.dashboard')], __('admin.external_agent_logged_in'));
                }

                return jsonSuccess(null, __("auth.password"), 402);
            }

            if (Hash::check((string) $request->password, (string) $user->password)) {
                if (($user->status ?? 'active') === 'suspended') {
                    return jsonSuccess(null, __('admin.account_suspended_now'), 403);
                }

                Auth::guard('admin')->login($user, $request->boolean('rememberme'));
                $this->startAdminSession($request, $user);

                $redirectUrl = $user->admin_type === 'employee' && Route::has('employeeProfile.index')
                    ? route('employeeProfile.index')
                    : $this->defaultAdminRedirectUrl($user);

                return jsonSuccess(['url' => $redirectUrl], __("auth.login successful"));
            }
            return jsonSuccess(null, __("auth.password"), 402);
        }
        return jsonSuccess(null, __("auth.failed email"), 401);
    }

    private function defaultAdminRedirectUrl($admin): string
    {
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
        ])->save();
    }

    public function logout()
    {
        $admin = Auth::guard('admin')->user();
        if ($admin && Schema::hasColumn('admins', 'current_session_id') && $admin->current_session_id === request()->session()->getId()) {
            $admin->forceFill([
                'current_session_id' => null,
                'current_session_ip' => null,
                'current_session_user_agent' => null,
            ])->save();
        }

        Session::flush();

        Auth::guard('admin')->logout();

        return redirect(route('admin.form.login'));
    }
}
