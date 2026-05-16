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
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\DataTables;


class AdminController extends Controller
{
    public $folderPath = "admin.auth.admin";
    public $postData = ["name", "phone", "email", 'whatsapp', 'verification_phone', 'identity_number', 'identity_expiry_date', "password", "admin_type", 'sex_type', 'role_id', 'sort', 'status', 'note'];
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
                                . ' title="حذف نهائي">'
                                . '<i class="fa fa-trash-o"></i> حذف نهائي'
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
                            . '<i class="fa fa-line-chart"></i> الملف التحليلي'
                            . '</a></li>';
                    }

                    if (Route::has('employees.create') && Auth::guard('admin')->user()->can(\App\PermKeys::HR_MODULE)) {
                        $linkedEmployee = $this->findLinkedHrEmployee((int) $row->id);

                        if ($linkedEmployee && Route::has('employees.edit')) {
                            $menuItems[] = '<li><a href="' . route('employees.edit', $linkedEmployee->id) . '" class="dropdown-item text-success">'
                                . '<i class="fa fa-id-card"></i> ملف الموظف'
                                . '</a></li>';
                        } else {
                            $menuItems[] = '<li><a href="' . route('employees.create', ['admin_id' => $row->id]) . '" class="dropdown-item text-success">'
                                . '<i class="fa fa-user-plus"></i> توظيف المستخدم'
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
                                . ' title="حذف">'
                                . '<i class="fa fa-trash-o"></i> ' . __('ingaz.delete')
                                . '</button></li>';
                        }
                    }

                    if (Auth::guard('admin')->user()->can('users_edit')) {
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
                            $fail('يجب اختيار موظف آخر غير الموظف المراد حذفه.');
                            return;
                        }

                        $exists = ObjModel::query()
                            ->whereKey($value)
                            ->whereNull('deleted_at')
                            ->where('status', 'active')
                            ->exists();

                        if (!$exists) {
                            $fail('الموظف البديل غير صالح أو غير نشط.');
                        }
                    },
                ],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'اختر موظفاً بديلاً لإسناد الطلبات والعقود قبل حذف الموظف.',
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

        return jsonSuccess(null, 'تم حذف الموظف ويمكن استعادته من فلتر المحذوفين.');
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
                            $fail('يجب اختيار موظف آخر غير الموظف المراد حذفه.');
                            return;
                        }

                        $exists = ObjModel::query()
                            ->whereKey($value)
                            ->whereNull('deleted_at')
                            ->where('status', 'active')
                            ->exists();

                        if (!$exists) {
                            $fail('الموظف البديل غير صالح أو غير نشط.');
                        }
                    },
                ],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'اختر موظفاً بديلاً لإسناد الطلبات والعقود قبل الحذف النهائي.',
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

        return jsonSuccess(null, 'تم حذف الموظف نهائياً.');
    }

    public function restore($id)
    {
        $admin = ObjModel::withTrashed()->findOrFail($id);
        if (!$admin->trashed()) {
            $message = 'الموظف غير محذوف.';
            return response()->json([
                'message' => $message,
                'errors' => [
                    'admin' => [$message],
                ],
            ], 422);
        }

        $admin->restore();
        return jsonSuccess(['id' => $admin->id], 'تم استرجاع الموظف بنجاح.');
    }

    private function renderAdminActionsDropdown(array $menuItems): string
    {
        if ($menuItems === []) {
            return '<span class="text-muted small">لا توجد إجراءات</span>';
        }

        return '<div class="dropdown actions-dropdown admin-actions-dropdown">'
            . '<button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="الإجراءات المتاحة">'
            . '<i class="fas fa-cogs me-1"></i> ' . __("ingaz.actions")
            . '</button>'
            . '<ul class="dropdown-menu dropdown-menu-end shadow-lg">'
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
        $message = "لا يمكن تنفيذ العملية قبل إعادة إسناد الأعمال إلى موظف آخر. "
            . "الطلبات: {$assignmentCounts['orders']}، الحجوزات: {$assignmentCounts['bookings']}، العقود: {$assignmentCounts['contracts']}.";

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
            return redirect()->route("dashboard.index");
        }
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {

        $this->validate($request, [
            'login' => 'required',
            'password' => 'required'
        ]);
        $agentUser = objModel::query()
            ->whereHas('agentOfficeLinks')
            ->where(function ($query) use ($request) {
                $query->where('phone', $request->login)
                    ->orWhere('email', $request->login);
            })
            ->first();

        if ($agentUser && $agentUser->isExternalOfficeOnlyAgent()) {
            if (($agentUser->status ?? 'active') === 'suspended') {
                return jsonSuccess(null, 'Ù‡Ø°Ø§ Ø§Ù„Ø­Ø³Ø§Ø¨ Ù…ÙˆÙ‚ÙˆÙ Ø­Ø§Ù„ÙŠØ§Ù‹.', 403);
            }

            if (auth()->guard("agent")->attempt(["email" => $agentUser->email, "password" => $request->password], $request->boolean('rememberme'))) {
                $request->session()->regenerate();

                return jsonSuccess([
                    'url' => \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL('en', route('agent.dashboard'), [], true),
                ], 'Agent login successful');
            }

            return jsonSuccess(null, __("auth.password"), 402);
        }

        $user = objModel::where('phone', $request->login)
            ->orWhere('email', $request->login)->first();
        if (isset($user->email)) {
            if ($user->isExternalOfficeOnlyAgent()) {
                if (($user->status ?? 'active') === 'suspended') {
                    return jsonSuccess(null, 'هذا الحساب موقوف حالياً.', 403);
                }

                if (auth()->guard("agent")->attempt(["email" => $user->email, "password" => $request->password], $request->boolean('rememberme'))) {
                    $request->session()->regenerate();

                    return jsonSuccess(['url' => route('agent.dashboard')], 'تم تسجيل الدخول للوحة الوكيل الخارجي');
                }

                return jsonSuccess(null, __("auth.password"), 402);
            }

            if (auth()->guard("admin")->attempt(["email" => $user->email, "password" => $request->password, "admin_type" => "employee"])) {
                return jsonSuccess(['url' => route('employeeProfile.index')], __("auth.login successful"));
            } elseif (auth()->guard("admin")->attempt(["email" => $user->email, "password" => $request->password])) {
                return jsonSuccess(['url' => route('dashboard.index')], __("auth.login successful"));
            }
            return jsonSuccess(null, __("auth.password"), 402);
        }
        return jsonSuccess(null, __("auth.failed email"), 401);
    }

    public function logout()
    {
        Session::flush();

        Auth::logout();

        return redirect(route('admin.form.login'));
    }
}
