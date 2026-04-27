<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\StockRequest as ObjRequest;
use Nami\Finance\Services\CostCenterService;
use Nami\Finance\Services\StockService as ObjService;
use Yajra\DataTables\DataTables;

class StockController extends Controller
{
    public string $folderPath = "finance::finance.stocks";
    public string $mainRoute = "finance.stocks";

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|\Illuminate\Foundation\Application|JsonResponse|\Illuminate\View\View
     * @throws Exception
     */

    public function index(Request $request, ObjService $service, CostCenterService $costCenterService)
    {
        if ($request->ajax()) {
            $dataTable = $service->getDataTable();
            if ($request->notification == "true") {
                if ($request->has('stocks_has_cost_center')) {
                    if ($request->stocks_has_cost_center == 1) {
                        $dataTable = $costCenterService->getStocksWithCostCenter();
                    } else {
                        $dataTable = $costCenterService->getStocksWithNoCostCenter();
                    }
                } else {
                    $dataTable = $costCenterService->getStocks();
                }
            }
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('address', function ($row) {
                    return $row->address ?? "";
                })->editColumn('status', function ($row) {
                    return $row->status->lang() ?? "";
                })
                ->editColumn('stockKeeper', function ($row) {
                    return $row->storekeeper ?? "";
                })
                ->addColumn('actions', function ($row) use ($costCenterService, $request) {
                    $editButton = "";
                    $costCenter = "";
                    $stocks = $costCenterService->getWhere(['table_name' => 'stocks'])->pluck('table_related_id')->toArray();
                    if (!in_array($row->id, $stocks)) {
                        $costCenter = customButton(route("finance.cost-centers.create") . '?table_name=stocks&name=' . $row->name . '&table_related_id=' . $row->id, __("finance.fn-cost-centers"), 'fa fa-money-bill', 'warning');
                    }
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    if ($request->notification == "true") {
                        return $costCenter;
                    }
                    return $costCenter . " " . $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.stocks");
        $data["addButtonText"] = __("finance::finance.stocks");
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
                'storeRoute' => route($this->mainRoute . ".store"),
                "code" => $service->lastCode(),
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
        $obj = $service->store($dataInsert);
        return jsonSuccess($obj);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return void
     */
    public function show(Request $request, $id, ObjService $service)
    {
        //
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
        $dataInsert = $request->validated();
        $data = $service->updateStock($id, $dataInsert);
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
        return jsonSuccess();

    }
}
