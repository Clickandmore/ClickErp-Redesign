<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\CostCenterRequest as ObjRequest;
use Nami\Finance\Services\CostCenterService as ObjService;
use Throwable;
use Yajra\DataTables\DataTables;

class CostCenterManagementController extends Controller
{
    public string $folderPath = "finance::finance..cost_center_management";
    public string $mainRoute = "finance.cost-center-management";
    public string $costMainRoute = "finance.cost-centers";

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|\Illuminate\Foundation\Application|JsonResponse|\Illuminate\View\View
     * @throws Exception
     */

    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = $service->getDatatableData();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('parent_id', function ($row) {
                    return $row->parent->title ?? "";
                })
                ->editColumn('code', function ($row) {
                    return $row->code ?? "";
                })
                ->addColumn('actions', function ($row) {
                    $addButton = '<button type="button" class="btn btn-success addButton ms-1" data-bs-toggle="modal" data-bs-target="#createModal"
                        model-route="' . route($this->costMainRoute . '.create', ['parent_id' => $row->id]) . '" model-title="' . $row->name . '">
                        <i class="fa fa-plus-circle"></i>
                        </button>';
                    $editButton = editButton(route($this->costMainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->costMainRoute . ".destroy", $row->id));

                    return $addButton . " " . $editButton . " " . $deleteButton;
                })
                ->filter(function ($query) use ($request) {
                    if ($request->filled('parent_id')) {
                        $query->where('parent_id', $request->parent_id);
                    }
                })
                ->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->costMainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.Cost center management");
        $data["addButtonText"] = __("finance::finance.cost-center");
        $data["main_accounts"] = $service->getMainAccounts();
        return view($this->folderPath . '.index', $data);
    }

    /**
     * @param Request $request
     * @param ObjService $service
     * @return JsonResponse|void
     * @throws Throwable
     */
    public function create(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            dd('sd');
            $returnHTML = view($this->folderPath . ".create")->with([
                "cost_centers" => $service->get(),
                "parent_id" => @$request->parent_id,
                'storeRoute' => route($this->mainRoute . ".store"),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ObjRequest $request
     * @param ObjService $service
     * @return JsonResponse
     */
    public function store(ObjRequest $request, ObjService $service)
    {
        $dataInsert = $request->validated();
        $dataInsert['created_by_id'] = auth('admin')->user()->id;
        $obj = $service->store($dataInsert);
        return jsonSuccess($obj, '', 201);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @param Request $request
     * @param ObjService $service
     * @return JsonResponse|void
     * @throws Throwable
     */
    public function edit(int $id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".edit")->with([
                "obj" => $service->find($id),
                "cost_centers" => $service->getWithoutId($id),
                'updateRoute' => route($this->mainRoute . ".update", $id),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ObjRequest $request
     * @param int $id
     * @param ObjService $service
     * @return JsonResponse
     */
    public function update(ObjRequest $request, int $id, ObjService $service)
    {
        $dataUpdate = $request->validated();
        $data = $service->update($id, $dataUpdate);
        return jsonSuccess($data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @param ObjService $service
     * @return JsonResponse
     */
    public function destroy(int $id, ObjService $service)
    {
        $service->delete($id);
        return jsonSuccess([], '', 201);
    }
}
