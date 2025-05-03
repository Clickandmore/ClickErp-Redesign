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
use Illuminate\Support\Facades\Session;
use App\Http\Actions\auth\AdminAction as ObjAction;
use App\Http\Requests\auth\profile\StoreAdminRequest as StoreRequest;
use App\Http\Requests\auth\profile\UpdateAdminRequest as UpdateRequest;



class AdminController extends Controller
{
    public $folderPath = "admin.auth.admin";
    public $postData = ["name", "phone", "email", 'whatsapp', "password", "admin_type", 'sex_type', 'role_id', 'sort'];
    public $mainRoute = "admins";


    function __construct()
    {
        // $this->middleware('permission:المستخدمون-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:المستخدمون-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:المستخدمون-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:المستخدمون-حذف,admin', ['only' => ['destroy']]);
    }


    public function index(Request $request, ObjAction $action)
    {
        if ($request->ajax()) {
            $dataTable = ObjModel::query()->latest();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('name', function ($row) {
                    return $row->name;
                })
                ->addColumn('admin_type', function ($row) {
                    return Constant::globalArray['admin_typeis'][$row->admin_type][app::getLocale()];
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
                /* ->addColumn('actions', function ($row) {
                    return editButton(route($this->mainRoute . ".edit", $row->id), $row->name) . " " .
                        deleteButton(route($this->mainRoute . ".destroy", $row->id));
                }) */
                ->addColumn('actions', function ($row) {
                    $editButton = '';
                    $deleteButton = '';

                    // if (Auth::guard('admin')->user()->hasPermission('المستخدمون-تعديل')) {
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    // }

                    // if (Auth::guard('admin')->user()->hasPermission('المستخدمون-حذف')) {
                    if ($row->id != Auth::guard('admin')->user()->id) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }
                    // }

                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("auth.admins");
        $data["addButtonText"] = __("auth.admin");
        return view($this->folderPath . '.index', $data);
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
        $action->delete($id);
        return jsonSuccess();
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
