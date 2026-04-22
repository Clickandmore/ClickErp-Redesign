<?php

namespace Nami\AccessControl\Http\Controllers;

use App\Enums\AdminTypeisEnum;
use App\Enums\UserModules;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Nami\AccessControl\Http\Requests\AdminRequest as ObjRequest;
use Nami\AccessControl\Services\AdminService as ObjService;
use Throwable;
use Yajra\DataTables\DataTables;

class AdminController extends Controller
{
    public string $folderPath = 'access-control::admins';
    public string $mainRoute = 'packages.access-control.admins';

    public function __construct()
    {
        $this->middleware('permission:users_view,admin', ['only' => ['index']]);
        $this->middleware('permission:users_create,admin', ['only' => ['create', 'store']]);
        $this->middleware('permission:users_edit,admin', ['only' => ['edit', 'update']]);
        $this->middleware('permission:users_delete,admin', ['only' => ['destroy']]);
    }

    /**
     * @return Application|Factory|View|\Illuminate\Foundation\Application|JsonResponse|\Illuminate\View\View
     * @throws Exception
     */
    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = $service->getAdminsDataTable();

            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('name', fn ($row) => $row->name)
                ->editColumn('phone', fn ($row) => $row->phone)
                ->editColumn('email', fn ($row) => $row->email)
                ->editColumn('role', fn ($row) => $row->roles->first()?->name ?? __('charities.no_data'))
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
        $data['bladeTitle'] = __('access-control::sidebar.package_users');
        $data['addButtonText'] = __('auth.admin');
        $data['modalType'] = 'modal-fullscreen';

        return view($this->folderPath . '.index', $data);
    }

    /**
     * @throws Throwable
     */
    public function create(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            if (Auth::guard('admin')->user()->admin_type == AdminTypeisEnum::Developer->value) {
                $models = Config::get('laratrust_seeder.roles_structure.superadministrator');
            } else {
                $settingPlans = $service->getSettingPlans();
                $allPermissions = Config::get('laratrust_seeder.roles_structure.superadministrator');
                $models = [];

                foreach ($allPermissions as $plan => $permissions) {
                    if (in_array($plan, $settingPlans)) {
                        $models[$plan] = $permissions;
                    }
                }
            }

            $maps = ['1' => 'create', '2' => 'read', '3' => 'update', '4' => 'delete'];
            $returnHTML = view($this->folderPath . '.create')->with([
                'models' => $models,
                'modules' => UserModules::cases(),
                'maps' => $maps,
                'roles' => $service->getRoles(),
                'storeRoute' => route($this->mainRoute . '.store'),
            ])->render();

            return jsonSuccess(['html' => $returnHTML]);
        }
    }

    public function store(ObjRequest $request, ObjService $service): JsonResponse
    {
        $dataInsert = $request->validated();
        $data = $service->storeAdmin($dataInsert, $request->permissions);

        return jsonSuccess($data);
    }

    public function show(int $id)
    {
    }

    /**
     * @throws Throwable
     */
    public function edit(int $id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            if (Auth::guard('admin')->user()->admin_type == AdminTypeisEnum::Developer->value) {
                $models = Config::get('laratrust_seeder.roles_structure.superadministrator');
            } else {
                $settingPlans = $service->getSettingPlans();
                $allPermissions = Config::get('laratrust_seeder.roles_structure.superadministrator');
                $models = [];

                foreach ($allPermissions as $plan => $permissions) {
                    if (in_array($plan, $settingPlans)) {
                        $models[$plan] = $permissions;
                    }
                }
            }

            $maps = ['1' => 'create', '2' => 'read', '3' => 'update', '4' => 'delete'];
            $returnHTML = view($this->folderPath . '.edit')->with([
                'obj' => $service->find($id),
                'models' => $models,
                'modules' => UserModules::cases(),
                'roles' => $service->getRoles(),
                'maps' => $maps,
                'updateRoute' => route($this->mainRoute . '.update', $id),
            ])->render();

            return jsonSuccess(['html' => $returnHTML]);
        }
    }

    public function update(ObjRequest $request, int $id, ObjService $service): JsonResponse
    {
        $dataInsert = $request->validated();
        $data = $service->updateAdmin($id, $dataInsert, $request->permissions);

        return jsonSuccess($data);
    }

    public function destroy(int $id, ObjService $service): JsonResponse
    {
        $service->delete($id);

        return jsonSuccess();
    }
}
