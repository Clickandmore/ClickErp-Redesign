<?php

namespace Nami\AccessControl\Http\Controllers;

use App\Enums\IgnoreSystemPermission;
use App\Http\Controllers\admin\Controller;
use App\Services\Nami\AdminService;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Nami\AccessControl\Http\Requests\RolePermissionRequest as ObjRequest;
use Nami\AccessControl\Services\RoleService as ObjService;
use Throwable;
use Yajra\DataTables\DataTables;

class RolePermissionController extends Controller
{
    public string $folderPath = 'access-control::role-permissions';
    public string $mainRoute = 'role-permissions';

    public function __construct()
    {
        $this->middleware('permission:roles_view,admin', ['only' => ['index']]);
        $this->middleware('permission:roles_create,admin', ['only' => ['create', 'store']]);
        $this->middleware('permission:roles_edit,admin', ['only' => ['edit', 'update']]);
        $this->middleware('permission:roles_delete,admin', ['only' => ['destroy']]);
    }

    /**
     * @return Application|Factory|View|\Illuminate\Foundation\Application|JsonResponse|\Illuminate\View\View
     * @throws Exception
     */
    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = $service->getDataTable();

            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('name', function ($row) {
                    return $row->name;
                })
                ->addColumn('actions', function ($row) {
                    $deleteButton = '';
                    $editButton = editButton(route($this->mainRoute . '.edit', $row->id), $row->name);

                    if ($row->id != Auth::guard('admin')->user()->id) {
                        $deleteButton = deleteButton(route($this->mainRoute . '.destroy', $row->id));
                    }

                    return $editButton . ' ' . $deleteButton;
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        $data['createRoute'] = route($this->mainRoute . '.create');
        $data['dataTableRoute'] = route($this->mainRoute . '.index');
        $data['bladeTitle'] = __('charities.role-permissions');
        $data['addButtonText'] = __('charities.role-permissions');
        $data['modalType'] = 'modal-fullscreen';

        return view($this->folderPath . '.index', $data);
    }

    /**
     * @throws Throwable
     */
    public function create(Request $request, ObjService $service, AdminService $adminService)
    {
        $permissions = $service->getModels($adminService);
        $maps = array_column(IgnoreSystemPermission::cases(), 'value');

        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . '.create')->with([
                'permissions' => $permissions,
                'maps' => $maps,
                'storeRoute' => route($this->mainRoute . '.store'),
            ])->render();

            return jsonSuccess(['html' => $returnHTML]);
        }
    }

    public function store(ObjRequest $request, ObjService $service): JsonResponse
    {
        $dataInsert = $request->validated();
        $data = $service->storePermission($dataInsert);

        return jsonSuccess($data);
    }

    public function show(int $id)
    {
    }

    /**
     * @throws Throwable
     */
    public function edit(int $id, Request $request, ObjService $service, AdminService $adminService)
    {
        $permissions = $service->getModels($adminService);
        $maps = array_column(IgnoreSystemPermission::cases(), 'value');

        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . '.edit')->with([
                'obj' => $service->find($id),
                'permissions' => $permissions,
                'maps' => $maps,
                'updateRoute' => route($this->mainRoute . '.update', $id),
            ])->render();

            return jsonSuccess(['html' => $returnHTML]);
        }
    }

    public function update(ObjRequest $request, int $id, ObjService $service): JsonResponse
    {
        $dataInsert = $request->validated();
        $data = $service->updatePermission($id, $dataInsert);

        return jsonSuccess($data);
    }

    public function destroy(int $id, ObjService $service): JsonResponse
    {
        $service->delete($id);

        return jsonSuccess();
    }
}
