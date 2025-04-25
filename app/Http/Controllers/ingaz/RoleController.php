<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\StoreRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class RoleController extends Controller
{
    public $folderPath = "admin.ingaz.roles";
    public $mainRoute = "roles";


    function __construct()
    {
        // $this->middleware('permission:الأدوار-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:الأدوار-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:الأدوار-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:الأدوار-حذف,admin', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Role::query()->latest();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('actions', function ($row) {
                    $id = $row->id;
                    $url = route('roles.edit', $row->id);
                    $canEdit = true; //perm was here
                    $canEdit = $canEdit;
                    $editButton = $canEdit
                        ? '<a href="' . route($this->mainRoute . ".edit", $row->id) . '" class="btn btn-primary shadow btn-xs sharp me-1" title="' . helperTrans('admin.edit') . '" data-id="' . $id . '">
                            <i class="fa fa-pencil"></i>
                            </a>'
                        : '';
                    $canDelete = true; //perm was here
                    $canDelete = $canDelete;
                    $deleteButton = $canDelete
                        ? deleteButton(route($this->mainRoute . ".destroy", $row->id))
                        : '';
                    return $editButton . $deleteButton;
                })
                ->escapeColumns([])->make(true);
        }
        $oneObjectTitle = helperTrans('ingaz.role');
        return view($this->folderPath . '.index', compact('oneObjectTitle'));
    }

    public function create()
    {
        $permissions = Permission::get();
        return view($this->folderPath . '.create', compact('permissions'));
    }

    public function store(StoreRoleRequest $request)
    {
        $role = Role::create([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
//            'guard_name' => 'admin'
        ]);
        $permissions = Permission::whereIn('id', $request->permission)->get();
        $role->syncPermissions($permissions);
        return response()->json([
            'code' => 200,
            'message' => helperTrans('ingaz.added successfully'),
            'url' => route('roles.index'),
        ]);
    }

    public function edit($id)
    {
        $role = Role::find($id);
        $permissions = Permission::get();
        $rolePermissions = DB::table("permission_role")->where("permission_role.role_id", $id)
            ->pluck('permission_role.permission_id', 'permission_role.permission_id')
            ->all();
        return view($this->folderPath . '.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(StoreRoleRequest $request, $id)
    {
        $role = Role::find($id);
        $role->name = $request->input('name');
        $role->display_name = $request->input('display_name');
        $role->description = $request->input('description');
        $role->save();
        $permissions = Permission::whereIn('id', $request->permission)->get();
        $role->syncPermissions($permissions);
        return response()->json(['code' => 200, 'message' => helperTrans('ingaz.added successfully'), 'url' => route('roles.index')]);

    }


    public function destroy($id)
    {
        DB::table("roles")->where('id', $id)->delete();
        return jsonSuccess();
    }
}
