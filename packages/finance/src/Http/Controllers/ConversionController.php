<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Enums\ConversionStatus;
use Nami\Finance\Http\Requests\ConversionRequest as ObjRequest;
use Nami\Finance\Services\ConversionService as ObjService;
use Yajra\DataTables\DataTables;

class ConversionController extends Controller
{
    public string $folderPath = "finance::finance.conversions";
    public string $mainRoute = "finance.conversions";

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
            if ($request->filled('from_date') && $request->filled('to_date')) {
                $fromDate = $request->from_date;
                $toDate = $request->to_date;
                $dataTable = $dataTable->whereBetween('date', [$fromDate, $toDate]);
            }

            if ($request->filled('code')) {
                $code = $request->code;
                $dataTable = $dataTable->where('code', $code);
            }

            if ($request->filled('from_stock_id')) {
                $stock = $request->from_stock_id;
                $dataTable = $dataTable->where('from_stock_id', $stock);
            }

            if ($request->filled('to_stock_id')) {
                $stock = $request->to_stock_id;
                $dataTable = $dataTable->where('to_stock_id', $stock);
            }

            if ($request->filled('item_id')) {
                $item = $request->item_id;
                $dataTable = $dataTable->whereHas('conversionItems', function ($query) use ($item) {
                    $query->where('item_id', $item);
                });
            }
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('from_stock_id', function ($row) {
                    return $row->fromStock->name ?? "";
                })
                ->editColumn('to_stock_id', function ($row) {
                    return $row->toStock->name ?? "";
                })
                ->editColumn('status', function ($row) {
                    return ConversionStatus::from($row->status)->lang();
                })
                ->addColumn('stock_movement_details', function ($row) {
                    if ($row->add_movement_id != null) {
                        return showButton(route("finance.sarf-movement.show", $row->add_movement_id), __("finance.stock-movement-details"), "eye", "warning");
                    }
                    return __("finance.no_data");
                })

                ->addColumn('sarf_movement_details', function ($row) {
                    if ($row->stock_movement_id != null) {
                        return showButton(route("finance.sarf-movement.show", $row->stock_movement_id), __("finance.sarf-movement-details"), "eye", "danger");
                    }
                    return __("finance.no_data");
                })
                ->addColumn('actions', function ($row) {
                    $deleteButton = "";
                    $editButton = "";
                    $transferButton = "";
                    if ($row->status == "new") {
                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                        $transferButton = '<button type="button" class="btn btn-outline-warning transferButton"
                                model-route="' . route("finance.transfer-to-sarf-movement.show", $row->id) . '"
                                model-title="اصدار اذن صرف"
                                title="اصدار اذن صرف">
                            <i class="fa fa-reply"></i>
                        </button>';
                    }
                    $detailsButton = editButton(route($this->mainRoute . ".show", $row->id), $row->name,"eye",true);

                    return $detailsButton . " " . $editButton . " " . $deleteButton . " " . $transferButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.Transfer between stores");
        $data["addButtonText"] = __("finance::finance.Transfer between stores");
        $data["stocks"] = $service->getStocks();
        $data["items"] = $service->getItems();
        $data["show_price_in_conversions"] = $service->getShowPriceInConversions();
        $data["modalType"] = "modal-xl";
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
                'stocks' => $service->getStocks(),
                'show_price_in_conversions' => $service->getShowPriceInConversions(),
                'code' => $service->getCode(),
                'items' => $service->getItems(),
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
        $obj = $service->storeConversion($dataInsert);
        return jsonSuccess($obj);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return void
     */
    public function show(int $id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".show")->with([
                "obj" => $service->find($id),
                'stocks' => $service->getStocks(),
                'show_price_in_conversions' => $service->getShowPriceInConversions(),
                'items' => $service->getItems(),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
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
                'stocks' => $service->getStocks(),
                'show_price_in_conversions' => $service->getShowPriceInConversions(),
                'items' => $service->getItems(),
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
        $data = $service->updateConversion($id, $dataInsert);
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
