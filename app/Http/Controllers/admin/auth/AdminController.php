<?php

namespace App\Http\Controllers\admin\auth;

use App\Helpers\Constant;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\App;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\auth\Admin as objModel;
use App\Models\Contract;
use App\Models\RentalContract;
use App\Models\ingaz\Order;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Http\Actions\auth\AdminAction as ObjAction;
use App\Http\Requests\auth\profile\StoreAdminRequest as StoreRequest;
use App\Http\Requests\auth\profile\UpdateAdminRequest as UpdateRequest;



class AdminController extends Controller
{
    public $folderPath = "admin.auth.admin";
    public $postData = ["name", "phone", "email", 'whatsapp', "password", "admin_type", 'sex_type', 'role_id', 'sort', 'status', 'note'];
    public $mainRoute = "admins";
    public $suspensionReasons = ['violation', 'manual_review', 'fraud_suspect', 'other'];


    function __construct()
    {
        $this->middleware('permission:users_view,admin', ['only' => ['index']]);
        $this->middleware('permission:users_create,admin', ['only' => ['create', 'store']]);
        $this->middleware('permission:users_edit,admin', ['only' => ['edit', 'update']]);
        $this->middleware('permission:users_delete,admin', ['only' => ['destroy', 'restore']]);
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
                    return $row->role?->name ?? '-';
                })
                ->editColumn('phone', function ($row) {
                    return $row->phone;
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
                    $editButton = '';
                    $deleteButton = '';
                    $suspendAction = '';
                    $restoreButton = '';

                    if ($row->trashed()) {
                        if (Auth::guard('admin')->user()->can('users_delete')) {
                            $restoreButton = '<button type="button" class="btn btn-sm btn-info restore-admin" data-id="' . $row->id . '">' .
                                __('ingaz.restore') .
                                '</button>';
                        }

                        return $restoreButton;
                    }

                    if (Auth::guard('admin')->user()->can('users_edit')) {
                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    }

                    if (Auth::guard('admin')->user()->can('users_delete')) {
                        if ($row->id != Auth::guard('admin')->user()->id) {
                            $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                        }
                    }

                    if (Auth::guard('admin')->user()->can('users_edit')) {
                        $suspendAction = '<a href="' . route("admins.toggle-status", $row->id) . '" class="btn btn-sm ' .
                            ($row->status === 'suspended' ? 'btn-success' : 'btn-warning') .
                            ' toggle-status" data-id="' . $row->id . '">' .
                            ($row->status === 'suspended' ? __('admin.activate') : __('admin.suspend')) .
                            '</a>';
                    }

                    return $editButton . " " . $deleteButton . " " . $suspendAction;
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
            $roles = Role::query();
            // if($currentUser->role_id == Constants::CLIENT_ROLE_ID){
            //     $roles = $roles->where('id', Constants::PARENT_ROLE_ID)
            //     ->orWhere('id', Constants::EMPLOYEE_ROLE_ID);
            // }
            $roles = $roles->pluck('name', 'id')->all();
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
            $roles = Role::query();
            // if($currentUser->role_id == Constants::CLIENT_ROLE_ID){
            //     $roles = $roles->where('id', Constants::PARENT_ROLE_ID)
            //     ->orWhere('id', Constants::EMPLOYEE_ROLE_ID);
            // }
            $roles = $roles->pluck('name', 'id')->all();
            $adminRole = $obj->roles->pluck('id')->toArray();
            $admin_typeis = $action->getAdminTypies();
            $returnHTML = view($this->folderPath . ".edit", compact('obj', 'roles', 'adminRole', 'admin_typeis'))->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function update(UpdateRequest $request, $id, ObjAction $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->updateAdmin($id, $dataInsert, $request);
        return jsonSuccess($data);
    }


    public function destroy($id, ObjAction $action)
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

        $assignmentCounts = $this->getActiveAssignmentCounts((int) $admin->id);
        if ($assignmentCounts['total'] > 0) {
            return $this->assignmentBlockedResponse($assignmentCounts);
        }

        $admin->delete();
        return jsonSuccess(null, __('admin.deletedSuccessfully'));
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
        $user = objModel::where('phone', $request->login)
            ->orWhere('email', $request->login)->first();
        if (isset($user->email)) {
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
