<?php

namespace Nami\AccessControl\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\AccessControl\Services\RoleService as ObjService;
use Yajra\DataTables\DataTables;

class PackageRolePermissionController extends RolePermissionController
{
    public string $folderPath = 'access-control::role-permissions';
    public string $mainRoute = 'packages.access-control.role-permissions';
    public string $packageFolderPath = 'access-control::role-permissions';

    /**
     * @throws Exception
     */
    public function index(Request $request, ObjService $service): \Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application|JsonResponse
    {
        if ($request->ajax()) {
            $dataTable = $service->getDataTable();

            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('name', fn ($row) => $row->name)
                ->addColumn('actions', function ($row) {
                    $deleteButton = '';
                    $editButton = editButton(route($this->mainRoute . '.edit', $row->id), $row->name);

                    if ($row->id != auth('admin')->id()) {
                        $deleteButton = deleteButton(route($this->mainRoute . '.destroy', $row->id));
                    }

                    return $editButton . ' ' . $deleteButton;
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        $data['createRoute'] = route($this->mainRoute . '.create');
        $data['dataTableRoute'] = route($this->mainRoute . '.index');
        $data['bladeTitle'] = __('access-control::sidebar.package_permissions');
        $data['addButtonText'] = __('access-control::sidebar.package_permissions');
        $data['modalType'] = 'modal-fullscreen';

        return view($this->packageFolderPath . '.index-package', $data);
    }
}
