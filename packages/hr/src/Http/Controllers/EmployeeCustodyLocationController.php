<?php

namespace Nami\Hr\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Hr\Http\Requests\EmployeeCustodyLocation\EmployeeCustodyLocationRequest as ObjRequest;
use Nami\Hr\Services\EmployeeCustodyLocationService as ObjService;
use Yajra\DataTables\DataTables;

class EmployeeCustodyLocationController extends Controller
{
    public string $folderPath = 'hr::hr.employeeCustodyLocations';
    public string $mainRoute = 'employee-custody-locations';

    /**
     * @throws Exception
     */
    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            return DataTables::of($service->getDataTable())
                ->addIndexColumn()
                ->editColumn('is_active', fn ($row) => $row->is_active ? __('hr::hr.Enable') : __('hr::hr.Disable'))
                ->addColumn('actions', function ($row) {
                    return editButton(route($this->mainRoute . '.edit', $row->id), __('hr::hr.custody_location'))
                        . ' '
                        . deleteButton(route($this->mainRoute . '.destroy', $row->id));
                })
                ->escapeColumns([])
                ->make(true);
        }

        return view($this->folderPath . '.index', [
            'createRoute' => route($this->mainRoute . '.create'),
            'dataTableRoute' => route($this->mainRoute . '.index'),
            'bladeTitle' => __('hr::hr.custody_locations'),
            'addButtonText' => __('hr::hr.custody_location'),
        ]);
    }

    public function create(Request $request)
    {
        if ($request->ajax()) {
            return jsonSuccess([
                'html' => view($this->folderPath . '.create', [
                    'storeRoute' => route($this->mainRoute . '.store'),
                ])->render(),
            ]);
        }
    }

    public function store(ObjRequest $request, ObjService $service): JsonResponse
    {
        $data = $request->validated();
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $service->store($data);

        return jsonSuccess();
    }

    public function edit(int $id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            return jsonSuccess([
                'html' => view($this->folderPath . '.edit', [
                    'obj' => $service->find($id),
                    'updateRoute' => route($this->mainRoute . '.update', $id),
                ])->render(),
            ]);
        }
    }

    public function update(ObjRequest $request, int $id, ObjService $service): JsonResponse
    {
        $data = $request->validated();
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $service->update($id, $data);

        return jsonSuccess();
    }

    public function destroy(int $id, ObjService $service): JsonResponse
    {
        $service->delete($id);

        return jsonSuccess();
    }
}
