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

class CostCenterController extends Controller
{
    public string $folderPath = "finance::finance.cost_centers";
    public string $mainRoute = "finance.cost-centers";

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|\Illuminate\Foundation\Application|JsonResponse|\Illuminate\View\View
     * @throws Exception
     */

    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = $service->getDataTable();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('image', function ($row) {
                    return getImgTag($row->image);
                })
                ->editColumn('parent_id', function ($row) {
                    return $row->parent->title ?? "";
                })
                ->addColumn('actions', function ($row) {
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["accountTree"] = $service->getAccountTree()['costs'];
        $data["constraints"] = $service->getAccountTree()['constraintDetails'];
//        return $service->getAccountTree()['costs'][0]->allChildrenIDs;
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.Cost Center Operations");
        $data["addButtonText"] = __("finance::finance.fn-cost-centers");
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
            $returnHTML = view($this->folderPath . ".create")->with([
                "cost_centers" => $service->get(),
                "parent_id" => @$request->parent_id,
                'stocks' => $service->getStocksWithNoCostCenter(),
//                'employees' => $service->getEmployeesWithNoCostCenter(),
                'types' => \Nami\Finance\Enums\CostCenterTypeEnum::cases(),
                'storeRoute' => route($this->mainRoute . ".store"),
                'request' => $request->all(),
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
        $dataInsert['created_by_id'] = auth('admin')->user()->id ;
        $obj = $service->store($dataInsert);
        return jsonSuccess($obj,'',201);
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
                'stocks' => $service->getStocksWithNoCostCenter(),
//                'employees' => $service->getEmployeesWithNoCostCenter(),
                'types' => \Nami\Finance\Enums\CostCenterTypeEnum::cases(),
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
        return jsonSuccess([],'',201);
    }
}
